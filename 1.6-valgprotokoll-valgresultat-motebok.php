<?php

# Example of present:
# curl -v https://valgresultat.no/meeting-book/3442_st_2025
#     Response (200 OK):
#     https://valgmedarbeiderportalen.valg.no/umbraco/Surface/MeetingBook/downloadByName/?meetingBookFolderId=23814&meetingBookName=3442_st_2025

# Example not present (as of 2025-09-10):
# curl -v https://valgresultat.no/meeting-book/1108_st_2025
#     Response (200 OK):
#     (empty)



set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$election_year = '2025';

$entities = file_get_contents('https://raw.githubusercontent.com/HNygard/valgresultat.no-copy/refs/heads/main/data/config/entities.json');
$entities = json_decode($entities);
$municipalitities = array();
$urls = array();
foreach ($entities->{$election_year}->kommune as $entity) {
    // "kommune-03-0301-oslo"
    // "kommune-04-3401-kongsvinger"
    // "kommune-04-3403-hamar"
    // "kommune-04-3411-ringsaker"

    $parts = explode('-', $entity);
    $municipal_id = $parts[2];
    $urls[] = 'https://valgresultat.no/meeting-book/' . $municipal_id . '_st_' . $election_year;
}

$urls_to_download = array();
foreach ($urls as $url) {
    $response = getUrlUsingCurl($url);
    if (!empty(($response['body']))) {
        $urls_to_download[] = trim($response['body']);
    }
}

file_put_contents(__DIR__ . '/docs/data-store/urls-election-'. $election_year . '.txt',  implode("\n", $urls_to_download));





function logDebug($string) {
    //logLine($string, 'DEBUG');
}

function logInfo($string) {
    logLine($string, 'INFO');
}

function logError($string) {
    logLine($string, 'ERROR');
}

/**
 * @param $string
 * @param Exception $e
 */
function logErrorWithStacktrace($string, $e) {
    logLine($string . chr(10)
        . $e->getMessage() . chr(10)
        . $e->getTraceAsString(), 'ERROR');
}

function logLine($string, $log_level) {
    global $run_key;
    echo date('Y-m-d H:i:s') . ' ' . $log_level . ' --- ' . $string . chr(10);

    if (isset($run_key) && !empty($run_key)) {
        // -> Download runner
        global $entity, $argv, $download_logs_directory;
        global $last_method;
        $line = new stdClass();
        $line->timestamp = time();
        $line->level = $log_level;
        $line->downloader = $argv[2];
        if (isset($entity) && isset($entity->entityId)) {
            $line->entity_id = $entity->entityId;
        }
        $line->last_method = $last_method;
        $line->message = $string;
        // Disabled.
        //file_put_contents($download_logs_directory . '/' . $run_key . '.json', json_encode($line) . chr(10), FILE_APPEND);
    }
}

function getUrlUsingCurl($url, $usepost = false, $post_data = array(), $headers = array(), $followredirect = true) {
    logInfo('---------------------------------------------');

    $ch = curl_init($url);

    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_USERAGENT, 'Valgprotokoll downloader. Created by @hallny.');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt ($ch, CURLOPT_COOKIESESSION, TRUE);
    //curl_setopt ($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    if ($followredirect) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }
    if ($usepost) {
        logInfo('   POST ' . $url);
        curl_setopt($ch, CURLOPT_POST, true);
        $query_string = array();
        foreach ($post_data as $input_value) {
            $query_string[] = urlencode($input_value[0]) . '=' . urlencode($input_value[1]);
        }
        $query_string = implode('&', $query_string);
        //logInfo(  '    '.$query_string.);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);

    }
    else {
        logInfo('   GET ' . $url);
    }
    if (count($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
//    throw new Exception('No curl.');
    $res = curl_exec($ch);

    if ($res === false) {
        throw new Exception(curl_error($ch), curl_errno($ch));
    }

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($res, 0, $header_size);
    $body = substr($res, $header_size);

    logInfo('   Response size: ' . strlen($body));

    //$info = curl_getinfo($ch);
    //var_dump($info);
    curl_close($ch);

    //logInfo('   strlen: '.strlen($res));
    return array('headers' => $header, 'body' => $body);
}