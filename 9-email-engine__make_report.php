<?php

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});


$election_year = '2023';


$html = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Spørsmål om valggjennomføring - GPT3.5 output</title>
</head>
<body>
<style>
table th {
text-align: left;
max-width: 300px;
border: 1px solid lightgrey;
padding: 2px;
}
table td {
border: 1px solid lightgrey;
padding: 2px;

}
table {
border-collapse: collapse;
}
</style>

<h1>Spørsmål om valggjennomføring - GPT3.5 output</h1>

<table>

    <tr>
        <th>Kommune og epostID</th>
        <td>Output fra <a href="https://github.com/HNygard/valgprotokoller/blob/master/openai-docker-python/src/main.py">GPT3.5 prompt</a> for å hente ut svar av innkommende epost</td>
        <td>Output fra <a href="https://github.com/HNygard/valgprotokoller/blob/master/openai-docker-python/src/main2.py">GPT3.5 prompt</a> for analysere svarene</td>
    </tr>

';

$files = getDirContents(__DIR__ . '/email-engine-data-store/raw-' . $election_year);
foreach ($files as $file) {
    if (str_ends_with($file, ' - IN.openaitxt')) {
        $obj = readOpenaiDockerOutput($file);

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

        $answer_file2 = str_replace('/raw-', '/answer-', $file . '.extract.analyze');
        if (file_exists($answer_file2)) {
            $obj2 = readOpenaiDockerOutput($answer_file2);
            $answers2 = $obj2->choices[0]->message->content;
        }
        else {
            $answers2 = '<i>Not available. Rerun scripts.</i>';
        }

        $html .= '
        
        <tr>
            <th style="">' . str_replace('/', "<br>\n", str_replace(__DIR__ . '/email-engine-data-store/answer-2023/', '', $answer_file)) . '</th>
            <td style=""><pre style="max-width: 900px;  overflow-x: scroll">' . file_get_contents($answer_file) . '</pre></td>
            <td style=""><pre style="max-width: 900px;  overflow-x: scroll">' . $answers2 . '</pre></td>
        </tr>
        
        ';
    }
}
$html .= '</table>';

file_put_contents(__DIR__ . '/docs/valggjenomforing-sporreundersokelse-' . $election_year . '.html', $html);


function readOpenaiDockerOutput($file) {
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
    return $obj;
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