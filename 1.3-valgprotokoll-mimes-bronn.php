<?php
/**
 * This script downloads all FOI requests from Mimes Brønn for a certain search.
 *
 * Based on code from Norske-postlister.no
 *
 * @author Hallvard Nygård, @hallny
 */

set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});


// :: Download data from Mimes Brønn
$cacheTimeSeconds = 60 * 60 * 4;
$cache_location = __DIR__ . '/data-store/mimesbronn-cache';

$additional_urls = array(// Also fetch these FOI requests
);

$a = getMimesBronn2($cacheTimeSeconds, $cache_location, 'valgprotokoll_2019', $additional_urls);
ksort($a);


// :: Read manual file
// Format:
// # <mimes brønn URL>
// Tekst
// Tekst
//
// # Next item

$svar = file(__DIR__ . '/data-store/mimesbronn-result/svar.txt');
$answers_to_questions = array();
$current_svar = '';
$current_url = '';
foreach ($svar as $line) {
    $line = trim($line);
    if (str_starts_with($line, '#')) {
        if (!empty($current_svar)) {
            $answers_to_questions[$current_url] = trim($current_svar);
        }
        $current_svar = '';
        $current_url = trim(substr($line, 1));
        $current_url = explode('#incoming', $current_url)[0];
        $current_url = explode('?nocache', $current_url)[0];
    }
    else {
        $current_svar .= $line . "\n";
    }
}
if (!empty($current_svar)) {
    $answers_to_questions[$current_url] = trim($current_svar);
}


$urls = '';
foreach ($a as $entityId => $array) {
    foreach ($array as $obj) {
        $display_status = $obj->display_status;
        $display_status = str_replace('<span class="innsyn-success"><i class="fas fa-check"></i> ', '', $display_status);
        $display_status = str_replace('<span class="innsyn-failed"><i class="fas fa-window-close"></i> ', '', $display_status);
        $display_status = str_replace('<span class="innsyn-waiting"><i class="far fa-clock"></i> ', '', $display_status);
        $display_status = str_replace('<i class="fas fa-question" title="not_held"></i> ', '', $display_status);
        $display_status = str_replace('<i class="fas fa-question" title="internal_review"></i> ', '', $display_status);
        $display_status = str_replace('.</span>', '', $display_status);
        $display_status = str_replace('</span>', '', $display_status);
        $display_status = str_replace('&aring;', 'aa', $display_status);
        $display_status = str_replace('&aelig;', 'ae', $display_status);

        logInfo(str_pad($entityId, 25) . ' - ' . $display_status . ' - ' . $obj->url);


        $urls .= '# ---- ' . $entityId . ' ---- ' . $obj->url . "\n";
        foreach ($obj->files as $file) {
            $file->url = 'https://www.mimesbronn.no' . str_replace(' ', '%20', $file->baseUrl);
            unset($file->baseUrl);

            if ($file->fileType == 'image/jpeg' || $file->fileType == 'image/png') {
                continue;
            }
            logInfo('   File: ' . $file->fileName);
            $urls .= '# File - ' . $file->fileName . "\n";
            $urls .= $file->url . "\n\n";
        }
        $urls .= "\n";

        if (isset($answers_to_questions[$obj->url])) {
            $obj->answerToQuestions = $answers_to_questions[$obj->url];
            unset($answers_to_questions[$obj->url]);
        }
    }
}
file_put_contents(__DIR__ . '/data-store/mimesbronn-result/urls.txt', $urls);
file_put_contents(__DIR__ . '/data-store/mimesbronn-result/result.json', json_encode($a, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));

if (!empty($answers_to_questions)) {
    var_dump($answers_to_questions);
    throw new Exception('URL mismatch on ' . count($answers_to_questions) . '!');
}

function getMimesBronn2($cacheTimeSeconds, $cache_location, $mimesbronn_tag, $additional_urls = array()) {
    $mimesBronn = getMimesBronnListHack($cacheTimeSeconds, $cache_location, $additional_urls, $mimesbronn_tag);

    $postjournal = array();
    foreach ($mimesBronn as $innsynsrequest) {
        if (!isset($innsynsrequest->info_request->tags)) {
            throw new Exception('No tags: ' . print_r($innsynsrequest, true));
        }
        $url = 'https://www.mimesbronn.no/request/' . urlencode($innsynsrequest->info_request->url_title);
        $obj = getMimesBronnBaseObject($innsynsrequest, $url);
        $obj->cache_key = $innsynsrequest->cache_key;
        if (!isset($postjournal[$obj->mimes_bronn_entity_url])) {
            $postjournal[$obj->mimes_bronn_entity_url] = array();
        }
        $postjournal[$obj->mimes_bronn_entity_url][] = $obj;
    }
    return $postjournal;
}


function getMimesBronnBaseObject($innsynsrequest, $url) {
    $obj = new stdClass();
    $obj->mimes_bronn_entity_url = $innsynsrequest->info_request->public_body->url_name;
    $obj->display_status = htmlentities($innsynsrequest->info_request->display_status, ENT_QUOTES);
    $obj->tags = $innsynsrequest->info_request->tags;
    if ($innsynsrequest->info_request->described_state == 'successful'
        || $innsynsrequest->info_request->described_state == 'partially_successful'
    ) {
        $obj->display_status = '<span class="innsyn-success"><i class="fas fa-check"></i> ' . $obj->display_status . '</span>';
    }
    elseif ($innsynsrequest->info_request->described_state == 'waiting_response') {
        $obj->display_status = '<span class="innsyn-waiting"><i class="far fa-clock"></i> ' . $obj->display_status . '</span>';
    }
    elseif ($innsynsrequest->info_request->described_state == 'rejected') {
        $obj->display_status = '<span class="innsyn-failed"><i class="fas fa-window-close"></i> ' . $obj->display_status . '</span>';
    }
    else {
        $obj->display_status = '<i class="fas fa-question" title="'
            . htmlentities($innsynsrequest->info_request->described_state, ENT_QUOTES)
            . '"></i> ' . $obj->display_status;
    }
    $obj->url = $url;
    $obj->events = array();
    $obj->files = $innsynsrequest->files;
    $obj->last_updated = (isset($innsynsrequest->info_request->updated_at) ? $innsynsrequest->info_request->updated_at : null);
    return $obj;
}


function getMimesBronnListHack($cacheTimeSeconds, $cache_location, $additional_urls, $mimesbronn_tag) {
    // https://www.mimesbronn.no/list/all?query=tag%3Amt-postliste-v2
    /*
*<div class="request_listing">
  <div class="request_left">
    <span class="head">
        <a href="/request/17092017_bekymringsmelding_bekym_2#incoming-2401">17.09.2017 - Bekymringsmelding - Bekymringsmelding - 48408</a>
    </span>

    <div class="requester">
      Svar fra <a href="https://www.mimesbronn.no/body/mattilsynet">Mattilsynet</a> til <a href="https://www.mimesbronn.no/user/hallvard_nygard">Hallvard Nygård</a> $
    </div>

    <span class="bottomline icon_successful">
      <strong>
        Vellykket.
      </strong><br>
    </span>
  </div>

  <div class="request_right">
    <span class="desc">
      Sakstittel: Bekymringsmelding
 Saksnr: 2017/190168

 Hallvard Nygård - Svar på innsynshenvendelse av 02102017 - Delvis avslag

 Vi viser til din henven...
    </span>
  </div>
</div>
*/
    // Getting:
    // - Link
    // - Status (successful, waiting_response
    // - Label

    //$completeSearch = file_get_contents(__DIR__ . '/cache-mimesbronn.html');
    $completeSearch = getMimesBronnCachedUsingCurl($cacheTimeSeconds, $cache_location . '/search-all-' . $mimesbronn_tag . '.html',
        'https://www.mimesbronn.no/list/all?query=tag%3A' . $mimesbronn_tag,
        'text/html');

    preg_match_all('/<h2 class="foi_results">([0-9]*) innsynshenvendelser funnet<\/h2>/', $completeSearch, $matches2);

    $foiRequestsInTotal = $matches2[1][0];
    logInfo('foiRequestsInTotal: ' . $foiRequestsInTotal);

    $urls = array();
    logInfo('Mimes Brønn download - Page 1');
    $urls = array_merge($urls, getMimesBronnItemsFromHtmlHack($completeSearch));
    $totalPages = (int)(1 + ($foiRequestsInTotal / 25));
    for ($i = 2; $i <= $totalPages; $i++) {
        logInfo('Mimes Brønn download - Page ' . $i);
        $completeSearch = getMimesBronnCachedUsingCurl($cacheTimeSeconds, $cache_location . '/search-all-' . $mimesbronn_tag . '-page-' . $i . '.html',
            'https://www.mimesbronn.no/list/all?query=tag%3A' . $mimesbronn_tag . '&page=' . $i,
            'text/html');
        $urls = array_merge($urls, getMimesBronnItemsFromHtmlHack($completeSearch));
    }

    if (count($urls) != $foiRequestsInTotal) {
        throw new Exception('count($urls) (' . count($urls) . ') != $foiRequestsInTotal (' . $foiRequestsInTotal . ')');
    }

    foreach ($additional_urls as $url) {
        $urls[str_replace('https://www.mimesbronn.no', '', $url)] = $url . '.json';
    }

    // :: Get all single items
    $hackedMimesBronn = array();
    foreach ($urls as $baseUrl => $url) {
        if (!str_ends_with($url, '.json')) {
            throw new Exception('URL does not end in .json: ' . $url);
        }
        $url_html = substr($url, 0, strlen($url) - strlen('.json'));
        $json = getMimesBronnCachedUsingCurl($cacheTimeSeconds, $cache_location . '/' . str_replace('/', '_', $baseUrl) . '.json', $url);
        /*if (empty($json)) {
            echo $baseUrl . chr(10) . $url . chr(10) . '=> NOT FOUND' . chr(10) . chr(10);
            continue;
        }*/
        $obj = json_decode($json);
        $obj2 = new stdClass();
        $obj2->cache_key = str_replace('/', '_', $baseUrl);
        $obj2->info_request = $obj;
        $obj2->files = array();

        /**
         * <li class="attachment">
         *
         * <a href="/request/1198/response/5555/attach/5/2017.pdf"><img alt="Attachment" class="attachment__image" src="/assets/icon_application_pdf_large-5ff7b47cebc4693cf729f854025d099f.png"></a>
         *
         * <p class="attachment__name">2017.pdf</p>
         *
         * <p class="attachment__meta">
         * 3.4M
         * <a href="/request/1198/response/5555/attach/5/2017.pdf">Download</a>
         * <a href="/request/1198/response/5555/attach/html/5/2017.pdf.html">View as HTML</a>
         * <!-- (application/pdf) -->
         *
         * </p>
         * </li>
         */
        $html = getMimesBronnCachedUsingCurl($cacheTimeSeconds, $cache_location . '/cache-html/' . $obj2->cache_key, $url_html);
        $regex = '/<li class="attachment">\s*<a href="(\S*)">([<a-zA-Z ="_\/\-0-9\.>]*)<\/a>\s*'
            . '<p class="attachment__name">(.*)<\/p>\s*'
            . '<p class="attachment__meta">\s*([0-9\.]*[A-Z])\s*([\s\S]*)'
            . '<!-- \((.*)\) -->\s*'
            . '<\/p>\s*'
            . '<\/li>/U';
        preg_match_all($regex, $html, $matches);
        if (count($matches[0]) != substr_count($html, '<li class="attachment">')) {
            throw new Exception('Regex did not work for ' . $obj->url);
        }
        foreach ($matches[0] as $i => $match) {
            $arr = array();
            foreach ($matches as $part) {
                $arr[] = $part[$i];
            }
            unset($arr[0]);

            if (empty($arr[1])) {
                throw new Exception('Issue with match.');
            }

            $fileObj = new stdClass();
            $fileObj->baseUrl = urldecode($arr[1]);
            $fileObj->fileName = $arr[3];
            $fileObj->fileSize = $arr[4];
            $fileObj->fileType = $arr[6];
            $obj2->files[] = $fileObj;
        }

        $hackedMimesBronn[] = $obj2;
    }
    return $hackedMimesBronn;
}


function getMimesBronnCachedUsingCurl($cacheTimeSeconds, $cache_file, $baseUri, $acceptContentType = '') {
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cacheTimeSeconds) {
        return file_get_contents($cache_file);
    }
    logInfo('   - GET ' . $baseUri);
    $ci = curl_init();
    curl_setopt($ci, CURLOPT_URL, $baseUri);
    curl_setopt($ci, CURLOPT_TIMEOUT, 200);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
    curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ci, CURLOPT_HEADER, 1);
    if ($acceptContentType != '') {
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Accept: ' . $acceptContentType));
    }
    $response = curl_exec($ci);
    if ($response === false) {
        throw new Exception(curl_error($ci), curl_errno($ci));
    }

    $header_size = curl_getinfo($ci, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ci);

    logInfo('   Response size: ' . strlen($body));

    if (!str_starts_with($header, 'HTTP/1.1 200 OK')) {
        if (str_starts_with($header, 'HTTP/1.1 404 Not Found') && file_exists($cache_file)) {
            logInfo('  -> 404 Not Found. Using cache.');
            return file_get_contents($cache_file);
        }
        logInfo('--------------------------------------------------------------' . chr(10)
            . $body . chr(10) . chr(10)
            . '--------------------------------------------------------------' . chr(10)
            . $header . chr(10) . chr(10)
            . '--------------------------------------------------------------');
        throw new Exception('Server did not respond with 200 OK.' . chr(10)
            . 'URL ...... : ' . $baseUri . chr(10)
            . 'Status ... : ' . explode(chr(10), $header)[0]
        );
    }

    if (trim($body) == '') {
        throw new Exception('Empty response.');
    }
    file_put_contents($cache_file, $body);
    return $body;
}

function getMimesBronnItemsFromHtmlHack($completeSearch) {
    preg_match_all('/<div class="request_listing">\s*[<a-z\s="_>]*<a href="(\/request\/[0-9a-zA-Z_]*)/', $completeSearch, $matches);

    // :: Remove duplicates
    $urls = array();
    foreach ($matches[1] as $match) {
        if (!str_starts_with($match, '/request/')) {
            throw new Exception('Unknown URL: ' . $match);
        }
        $urls[$match] = 'https://www.mimesbronn.no' . $match . '.json';
    }
    return $urls;
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
