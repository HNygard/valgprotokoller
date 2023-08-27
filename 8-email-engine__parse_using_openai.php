<?php

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});


$election_year = '2023';


$files = getDirContents(__DIR__ . '/email-engine-data-store/raw-' . $election_year);
foreach ($files as $file) {
    if (str_ends_with($file, ' - IN.txt')) {
        $file_txt = str_replace('.txt', '.openaitxt', $file);
        if (!file_exists($file_txt)) {
            echo file_get_contents($file);
            echo chr(10);
            echo chr(10);
            echo chr(10);
            echo chr(10);
            echo chr(10);


            echo 'TEXT-parse-OpenAI: ' . $file . chr(10);
            $output = parseUsingOpenAI($file);
            file_put_contents($file_txt, $output);

            echo $output.chr(10);
            exit;
        }

        // Extracted by 9-email-engine__make_report.php
        $answer_file = str_replace('/raw-', '/answer-', $file_txt . '.extract');
        $answer_file2 = str_replace('/raw-', '/answer-', $file_txt . '.extract.analyze');
        if (file_exists($answer_file) && !file_exists($answer_file2)) {
            echo file_get_contents($answer_file);
            echo chr(10);
            echo chr(10);
            echo chr(10);
            echo chr(10);
            echo chr(10);


            echo 'TEXT-parse-OpenAI: ' . $answer_file . chr(10);
            $output = analyzeUsingOpenAI($answer_file);
            file_put_contents($answer_file2, $output);

            echo $output.chr(10);
            exit;
        }

    }
}


function parseUsingOpenAI($file) {
    $command = 'docker run '
        . '-e PROMPT=email-to-answers '
        . '-v $(pwd)/openai-docker-python/openai-api-key.txt:/api-key/openai-api-key.txt '
        . '-v "' . $file . '":/input.txt '
        . '-v $(pwd)/openai-docker-python/src:/app openai-docker-python';
    echo '  cmd: ' . $command . chr(10);
    exec($command, $lines);

    $output = '';
    foreach ($lines as $line) {
        $output .= $line . chr(10);
    }

    return $output;
}

function analyzeUsingOpenAI($file) {
    $command = 'docker run '
        . '-e PROMPT=answers-to-result '
        . '-v $(pwd)/openai-docker-python/openai-api-key.txt:/api-key/openai-api-key.txt '
        . '-v "' . $file . '":/input.txt '
        . '-v $(pwd)/openai-docker-python/src:/app openai-docker-python';
    echo '  cmd: ' . $command . chr(10);
    exec($command, $lines);

    $output = '';
    foreach ($lines as $line) {
        $output .= $line . chr(10);
    }

    return $output;
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