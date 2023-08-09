<?php
/**
 * This script downloads all FOI requests from Email Engine
 *
 * @author Hallvard Nygård, @hallny
 */

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$election_year = '2023';

$additional_urls = array(// Also fetch these FOI requests
);


$url = array();

$entityStatus = array();
$entityFinished = array();
$entityMarkOk = array();
$entityOnlyOneOut = array();
$entityFirstAction = array();
$entityLastAction = array();
$entityEmails = array();
$obj = json_decode(file_get_contents('http://localhost:25081/api.php?label=valgprotokoll_' . $election_year));
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
            if ($att->filetype != 'pdf' && $att->filetype != 'UNKNOWN') {
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

$entityLastActionSummary = array();
for ( $i = strtotime('2023-08-07'); $i <= time(); $i = $i + 86400 ) {
    $entityLastActionSummary[date('Y-m-d', $i)] = 0;
}

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

    $entityLastActionSummary[date('Y-m-d', $max)] = isset($entityLastActionSummary[date('Y-m-d', $max)])
        ? $entityLastActionSummary[date('Y-m-d', $max)] + 1
        : 1;
}


file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/urls.txt', implode("\n", $url));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/entity-status-sent.txt', implode("\n", $entityStatus));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/entity-status-finished.txt', implode("\n", $entityFinished));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/entity-set-success-sent.txt', implode("\n", $entityMarkOk));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/entity-only-one-email-outgoing.txt', implode("\n", $entityOnlyOneOut));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/entity-first-action.txt', implode("\n", $entityFirstAction));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/entity-last-action.txt', implode("\n", $entityLastAction));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year.'/entity-emails.json', json_encode($entityEmails, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_UNICODE ^ JSON_UNESCAPED_SLASHES));

ksort($entityLastActionSummary);
$entityLastActionSummary2 = array();
foreach($entityLastActionSummary as $date => $num) {
    $entityLastActionSummary2[] = $date .';'.$num;
}
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/entity-summary-last-action.csv', implode("\n", $entityLastActionSummary2));





$klageKommune = array();
$klageFylkeskommune = array();
$klageStortinget = array();
$obj = json_decode(file_get_contents('http://localhost:25081/api.php?label=valgklage_' . $election_year));
foreach ($obj->matchingThreads as $thread) {
    $klage = new stdClass();

    if (isset($thread->emails[0])) {
        $firstEmail = $thread->emails[0];
        $klage->klageSent = $firstEmail->timestamp_received;
    }
    else {
        $klage->klageSent = 0;
    }

    foreach ($thread->labels as $label) {
        if ($label == 'valgklage_' . $election_year . '_kommune') {
            $klageKommune[$thread->entity_id] = $klage;
        }
        if (str_starts_with($label, 'valgklage_' . $election_year . '_fylkeskommune:')) {
            $klageFylkeskommune[str_replace('valgklage_' . $election_year . '_fylkeskommune:', '', $label)] = $klage;
        }
        if (str_starts_with($label, 'valgklage_' . $election_year . '_stortinget:')) {
            $klageStortinget[str_replace('valgklage_' . $election_year . '_stortinget:', '', $label)] = $klage;
        }
    }
}

file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/klage-sendt-kommune.json', json_encode($klageKommune, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/klage-sendt-fylkeskommune.json', json_encode($klageFylkeskommune, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result-' . $election_year . '/klage-sendt-stortinget.json', json_encode($klageStortinget, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));
