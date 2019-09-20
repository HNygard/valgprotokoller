<?php
/**
 * Read the git submodule https://github.com/elections-no/elections-no.github.io/tree/master/docs
 *
 * @author Hallvard Nygård, @hallny
 */

set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});


// TODO: git submodule update
$command = 'git submodule update --remote elections-no.github.io';
logInfo('Exec: ' . $command);
exec($command, $find);
logInfo("Command output\n" . implode("\n", $find));

$data_dir = __DIR__ . '/elections-no.github.io/docs/2019/';
$cache_dir_pdfs = __DIR__ . '/data-store/pdfs/';

$files = getDirContents($data_dir);
foreach ($files as $file) {
    if (!str_ends_with(strtolower($file), '.pdf')) {
        logInfo('Ignoring [' . str_replace(__DIR__ . '/', '', $file) . ']');
        continue;
    }

    $cache_name = str_replace(__DIR__ . '/', '', $file);
    logInfo('Reading [' . $cache_name . ']');
    $cache_name = str_replace('/', '-', $cache_name);
    $cache_name = __DIR__ . '/data-store/pdfs/' . $cache_name;

    // :: Read PDF into TXT file
    // Keeping layout as this is important for tables.
    if (!file_exists($cache_name . '.layout.txt')) {
        $pdfLines = '';
        exec('pdftotext -layout "' . $file . '" -', $pdfLines);
        file_put_contents($cache_name . '.layout.txt', implode(chr(10), $pdfLines));
    }
    else {
        $pdfLines = file($cache_name . '.layout.txt', FILE_IGNORE_NEW_LINES);
    }

    if (!file_exists($cache_name . '.pdfinfo.txt')) {
        $pdfinfoOutput = '';
        exec('pdfinfo "' . $file . '"', $pdfinfoOutput);
        file_put_contents($cache_name . '.pdfinfo.txt', implode(chr(10), $pdfinfoOutput));
    }
}


function getDirContents($dir) {
    $command = 'find "' . $dir . '"';
    logInfo('Exec: ' . $command);
    exec($command, $find);
    logInfo('- Found [' . count($find) . ']');
    $data_store_files = array();
    foreach ($find as $line) {
        if (is_dir($line)) {
            // -> Find already got all recursively
            continue;
        }
        $data_store_files[] = $line;
    }
    return $data_store_files;
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
