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

$obj = json_decode(file_get_contents('http://localhost:25081/api.php?label=valgprotokoll_2021'));
foreach($obj->matchingThreads as  $thread) {
    foreach ($thread->emails as $email) {
        if (!isset($email->attachments)) {
            continue;
        }
        foreach($email->attachments as $att) {
            if ($att->filetype != 'pdf') {
                continue;
            }
            $url[] = $att->link;
        }
    }
}

sort($url);

file_put_contents(__DIR__ . '/docs/data-store/email-engine-result/urls.txt', implode("\n", $url));
