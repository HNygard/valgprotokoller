<?php
/**
 * This script downloads all FOI requests from Email Engine
 *
 * @author Hallvard Nygård, @hallny
 */

set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});


$additional_urls = array(// Also fetch these FOI requests
);


function str_starts_with($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) == $needle;
}

function str_ends_with($haystack, $needle) {
    $length = strlen($needle);
    return $length === 0 || substr($haystack, -$length) === $needle;
}

function str_contains($stack, $needle) {
    return (strpos($stack, $needle) !== FALSE);
}


$url = array();

$entityStatus = array();
$entityFinished = array();
$entityMarkOk = array();
$entityOnlyOneOut = array();
$entityFirstAction = array();
$entityLastAction = array();
$entityEmails = array();
$obj = json_decode(file_get_contents('http://localhost:25081/api.php?label=valgprotokoll_2021'));
foreach ($obj->matchingThreads as $thread) {
    if ($thread->sent) {
        $entityStatus[$thread->entity_id] = $thread->entity_id;
    }
    if ($thread->archived) {
        $entityFinished[$thread->entity_id] = $thread->entity_id;
    }

    $out = 0;
    $in = 0;

    $timeNow = time();
    $min = $timeNow;
    $max = 0;
    foreach ($thread->emails as $email) {
        if (!isset($entityEmails[$thread->entity_id])) {
            $entityEmails[$thread->entity_id] = new stdClass();
            $entityEmails[$thread->entity_id]->threadCount = 0;
            $entityEmails[$thread->entity_id]->emailsSummary = array();
            $entityEmails[$thread->entity_id]->emails = array();
        }
        $entityEmails[$thread->entity_id]->emailsSummary[] = '- ' . date('Y-m-d H:i:s', $email->timestamp_received) .
            ($email->email_type == 'IN' ? ' epost fra dere' : ' epost til dere');

        $email2 = new stdClass();
        $email2->datetime_received = $email->datetime_received;
        $email2->timestamp_received = $email->timestamp_received;
        $email2->email_type = $email->email_type;
        $entityEmails[$thread->entity_id]->emails[] = $email2;

        if ($email->email_type == 'IN') {
            $in++;
        }
        else if ($email->email_type == 'OUT') {
            $out++;
        }
        else {
            throw new Exception('Unknown type: ' .$email->email_type);
        }

        if (!isset($email->attachments)) {
            continue;
        }
        foreach ($email->attachments as $att) {
            if ($att->filetype != 'pdf') {
                continue;
            }
            $url[] = $att->link;
            $entityMarkOk[] = $thread->entity_id . ':' . $att->linkSetSuccess;
        }
    }

    $entityEmails[$thread->entity_id]->threadCount++;

    if ($out == 1 && $in == 0 && $thread->emails[0]->timestamp_received + 432000 < time()) {
        $entityOnlyOneOut[$thread->entity_id] = $thread->entity_id;
    }
}

sort($url);

foreach($entityEmails as $entity_id => $entityEmail) {
    $max = 0;
    $min = $timeNow;

    foreach($entityEmail->emails as $email) {
        $max = max($max, $email->timestamp_received);
        $min = min($min, $email->timestamp_received);
    }
    $entityLastAction[$entity_id] = $entity_id . ':' . $max;
    if ($timeNow != $min) {
        $entityFirstAction[$entity_id] = $entity_id . ':' . $min;
    }

}

file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/urls.txt', implode("\n", $url));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-status-sent.txt', implode("\n", $entityStatus));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-status-finished.txt', implode("\n", $entityFinished));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-set-success-sent.txt', implode("\n", $entityMarkOk));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-only-one-email-outgoing.txt', implode("\n", $entityOnlyOneOut));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-first-action.txt', implode("\n", $entityFirstAction));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-last-action.txt', implode("\n", $entityLastAction));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-emails.json', json_encode($entityEmails, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_UNICODE ^ JSON_UNESCAPED_SLASHES));






$klageKommune = array();
$klageFylkeskommune = array();
$klageStortinget = array();
$obj = json_decode(file_get_contents('http://localhost:25081/api.php?label=valgklage_2021'));
foreach ($obj->matchingThreads as $thread) {
    $klage = new stdClass();

    if (isset($thread->emails[0])) {
        $firstEmail = $thread->emails[0];
        $klage->klageSent = $firstEmail->timestamp_received;
    }
    else {
        $klage->klageSent = 0;
    }

    foreach($thread->labels as $label) {
        if ($label == 'valgklage_2021_kommune') {
            $klageKommune[$thread->entity_id] = $klage;
        }
        if (str_starts_with($label, 'valgklage_2021_fylkeskommune:')) {
            $klageFylkeskommune[str_replace('valgklage_2021_fylkeskommune:', '', $label)] = $klage;
        }
        if (str_starts_with($label, 'valgklage_2021_stortinget:')) {
            $klageStortinget[str_replace('valgklage_2021_stortinget:', '', $label)] = $klage;
        }
    }
}

file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/klage-sendt-kommune.json', json_encode($klageKommune, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/klage-sendt-fylkeskommune.json', json_encode($klageFylkeskommune, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/klage-sendt-stortinget.json', json_encode($klageStortinget, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));
