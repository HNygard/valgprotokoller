<?php
/**
 * Download 'valgprotokoll' from
 *
 * @author Hallvard Nygård, @hallny
 */

$cache_dir_pdfs = __DIR__ . '/data-store/pdfs/';
$lines = file(__DIR__ . '/data-store/urls.txt');
foreach ($lines as $line) {
    if (str_starts_with($line, '#')) {
        continue;
    }
    $line = trim($line);

    $cache_name = str_replace('https://', '', $line);
    $cache_name = str_replace('/', '-', $cache_name);
    $cache_name = $cache_dir_pdfs . $cache_name . '.pdf';
    if (!file_exists($cache_name)) {
        $data = getUrlUsingCurl($line);
        file_put_contents($cache_name, $data);

        // :: Same some meta data
        $obj = new stdClass();
        $obj->url = $line;
        $obj->downloadTime = date('Y-m-d H:i:s');
        file_put_contents($cache_name . '.json', json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_UNICODE ^ JSON_UNESCAPED_SLASHES));
    }


}


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

function logDebug($string) {
    //logLine($string, 'DEBUG');
}

function logInfo($string) {
    logLine($string, 'INFO');
}

function logError($string) {
    logLine($string, 'ERROR');
}

$last_method = null;
function setLastMethod($method) {
    global $last_method;
    $last_method = $method;
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

function getUrlUsingCurl($url, $usepost = false, $post_data = array(), $headers = array(), $followredirect = false) {
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