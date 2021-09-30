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


$url = array();

$entityStatus = array();
$entityFinished = array();
$entityMarkOk = array();
$entityOnlyOneOut = array();
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

    foreach ($thread->emails as $email) {
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

    if ($out == 1 && $in == 0 && $thread->emails[0]->timestamp_received + 432000 < time()) {
        $entityOnlyOneOut[$thread->entity_id] = $thread->entity_id;
    }
}

sort($url);

file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/urls.txt', implode("\n", $url));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-status-sent.txt', implode("\n", $entityStatus));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-status-finished.txt', implode("\n", $entityFinished));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-set-success-sent.txt', implode("\n", $entityMarkOk));
file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-only-one-email-outgoing.txt', implode("\n", $entityOnlyOneOut));
