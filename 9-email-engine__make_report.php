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
        foreach ($lines as $line) {
            if ($line == '--------------- OUTPUT') {
                $output_started = true;
            }
            elseif ($line == '---------------') {
                $output_started = false;
            }
            elseif ($output_started) {
                $output .= "\n" . $line;
            }
        }

        $obj = json_decode($output);
        if (!isset($obj->choices) || $obj->choices[0]->finish_reason != 'stop') {
            var_dump($obj);
            throw new Exception('Finish reason not stop: ' . $file);
        }
        #var_dump($obj->choices[0]->message->content);

        $answer_file = str_replace('/raw-', '/answer-', $file . '.extract');
        if (!file_exists($answer_file)) {
            $dir = dirname($answer_file);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($answer_file, $obj->choices[0]->message->content);
        }

        $lines = explode("\n", file_get_contents($answer_file));
        foreach ($lines as $line) {
            if (str_starts_with($line, '{')
                || str_starts_with(trim($line), '"any_answers_found":')
                || str_starts_with(trim($line), '"answer1":')
                || str_starts_with(trim($line), '"answer2":')
                || str_starts_with(trim($line), '"answer3":')
                || str_starts_with(trim($line), '"answer4":')
                || str_starts_with(trim($line), '"answer5":')
                || str_starts_with(trim($line), '"answer6":')
                || str_starts_with(trim($line), '"answer7":')
                || str_starts_with($line, '}')) {
                continue;
            }
            var_dump($line);
            var_dump($obj->choices[0]->message->content);
            throw new Exception('Buggy JSON: ' . $answer_file);
        }
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