<?php

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});


$election_year = '2023';


$files = getDirContents(__DIR__ . '/email-engine-data-store/raw-' . $election_year);
foreach ($files as $file) {
    if (str_ends_with($file, ' - IN.openaitxt')) {
        $lines = explode("\n", file_get_contents($file));
        $output_started = false;
        $output = '';
        foreach($lines as $line) {
            if ($line == '--------------- OUTPUT') {
                $output_started = true;
            }
            elseif($line == '---------------') {
                $output_started = false;
            }
            elseif($output_started) {
                $output .= "\n" . $line;
            }
        }

        $obj = json_decode($output);
        if (!isset($obj->choices) || $obj->choices[0]->finish_reason != 'stop') {
            var_dump($obj);
            throw new Exception('Finish reason not stop: ' . $file);
        }
        var_dump($obj->choices[0]->message->content);
        //exit;
    }
}


function getDirContents($dir) {
    $command = 'find "' . $dir . '"';
    exec($command, $find);
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