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

<h2>Spørsmål stilt</h2>
<pre>
1): Vil kommunen foreta maskinell eller manuell endelig telling av valgresultatet?

2): I hvilken politisk sak ble dette vedtatt?

3): Ved foreløpig opptelling (manuell opptelling), blir opptellingen gjort i valgkretsene? Hvis det er opptelling i valgkretsene, hvordan overfører
kommunen resultatet fra valgkretsene inn til valgstyret/valgansvarlig/EVA Admin?

4): Ved foreløpig opptelling (manuell opptelling), hvordan lagrer/arkiverer kommunen resultatet fra opptellingen utenom EVA Admin? Papir? Digitalt
dokument? SMS? Blir resultatet journalført?

5): Har kommunen rutiner for å kontrollere resultatet av foreløpig opptelling opp mot det som er synlig på valgresultat-siden til Valgdirektoratet
(valgresultat dått no), i valgprotokoll, i medier og lignende?
En slik kontroll vil f.eks. oppdage tastefeil (kommunen legger inn feil resultat i EVA Admin) samt feil i Valgdirektoratets håndtering av resultatet.

6): Tilsvarende som 4) for endelig opptelling (maskinell eller manuell).
Hvordan lagrer kommunen resultatet fra endelig opptelling utenom i EVA Admin/EVA Skanning? Blir resultatet journalført?

7): Tilsvarende som 5) for endelig opptelling (maskinell eller manuell).
Har kommunen rutiner for kontroll av endelig opptelling mot resultat som blir publisert?
</pre>

<h3>GPT3.5 har svart på følgende om svarene</h3>
<pre>
final_counting_type - Enum: I spørsmål 1, var endelig opptelling "maskinell opptelling" eller "manuell opptelling"?

counting_type_decision_date - Date: I spørsmål 2, hent ut dato for eventuelt vedtak.
counting_type_decision_case_number - String: I spørsmål 2, hent ut saksnummer for eventuelt vedtak.

prelimitary_counting__any_counting_in_valgkrets - boolean: I spørsmål 3, er det opptelling i valgkretsene?
prelimitary_counting__transfer_to_eva__do_they_answer - boolean: I spørsmål 3, svarer de på hvordan resultatet overføres til valgstyret/valgansvarlig/EVA Admin?
prelimitary_counting__transfer_to_eva__type - string: I spørsmål 3, hvordan overfører de resultatet til valgstyret/valgansvarlig/EVA Admin?

prelimitary_counting__is_stored - boolean: I spørsmål 4, lagrer de noe i arkivet?
prelimitary_counting__what_is_stored - string: I spørsmål 4, hva lagrer de?
prelimitary_counting__storage_method - string: I spørsmål 4, hvordan lagres det?

prelimitary_counting__process_for_control - boolean: I spørsmål 5, har de rutiner for å kontrollere resultatet?
prelimitary_counting__process_for_control_type - string: I spørsmål 5, oppsummer eventuelle rutiner for å kontrollere resultatet?

final_counting__is_stored - boolean: I spørsmål 6, lagrer de noe i arkivet?
final_counting__what_is_stored - string: I spørsmål 6, hva lagrer de?
final_counting__storage_method - string: I spørsmål 6, hvordan lagres det?

final_counting__process_for_control - boolean: I spørsmål 7, har de rutiner for å kontrollere resultatet?
final_counting__process_for_control_type - string: I spørsmål 7, oppsummer eventuelle rutiner for å kontrollere resultatet?
</pre>


<h2>Oppsummering av svar</h2>
TABLE_SUMMARY

<h2>Data fra kommunene</h2>

<table>

    <tr>
        <th>Kommune og epostID</th>
        <td>Output fra <a href="https://github.com/HNygard/valgprotokoller/blob/master/openai-docker-python/src/main.py">GPT3.5 prompt</a> for å hente ut svar av innkommende epost</td>
        <td>Output fra <a href="https://github.com/HNygard/valgprotokoller/blob/master/openai-docker-python/src/main2.py">GPT3.5 prompt</a> for analysere svarene</td>
    </tr>

';
$summary = array(
    "final_counting_type" => array(),
    "counting_type_decision_date" => array(),
    "counting_type_decision_case_number" => array(),
    "prelimitary_counting__any_counting_in_valgkrets" => array(),
    "prelimitary_counting__transfer_to_eva__do_they_answer" => array(),
    "prelimitary_counting__transfer_to_eva__type" => array(),
    "prelimitary_counting__is_stored" => array(),
    "prelimitary_counting__what_is_stored" => array(),
    "prelimitary_counting__storage_method" => array(),
    "prelimitary_counting__process_for_control" => array(),
    "prelimitary_counting__process_for_control_type" => array(),
    "final_counting__is_stored" => array(),
    "final_counting__what_is_stored" => array(),
    "final_counting__storage_method" => array(),
    "final_counting__process_for_control" => array(),
    "final_counting__process_for_control_type" => array(),
    "answer1" => array(),
    "answer2" => array(),
    "answer3" => array(),
    "answer4" => array(),
    "answer5" => array(),
    "answer6" => array(),
    "answer7" => array(),

    # TODO clean up and remove - AI answers on wrong JSON
    'questions' => array(),
    'prelimitary_counting__is_storage__type' => array(),
    'prelimitary_counting_any_counting_in_valgkrets' => array(),
    'prelimitary_counting_transfer_to_eva_do_they_answer' => array(),
    'prelimitary_counting_transfer_to_eva_type' => array(),
    'prelimitary_counting_is_stored' => array(),
    'prelimitary_counting_what_is_stored' => array(),
    'prelimitary_counting_storage_method' => array(),
    'prelimitary_counting_process_for_control' => array(),
    'prelimitary_counting_process_for_control_type' => array(),
    'final_counting_is_stored' => array(),
    'final_counting_what_is_stored' => array(),
    'final_counting_storage_method' => array(),
    'final_counting_process_for_control' => array(),
    'final_counting_process_for_control_type' => array(),
);
$files = getDirContents(__DIR__ . '/email-engine-data-store/openai_txt_extract-' . $election_year);
foreach ($files as $file) {
    if (str_ends_with($file, ' - IN.openaitxt')) {
        $obj = readOpenaiDockerOutput($file);

        #var_dump($obj->choices[0]->message->content);

        $answer_file = str_replace('/openai_txt_extract-', '/answer-', $file . '.extract');
        if (!file_exists($answer_file)) {
            $dir = dirname($answer_file);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $txt = $obj->choices[0]->message->content;
            if (json_decode($txt) != null) {
                $txt = json_encode(json_decode($txt), JSON_PRETTY_PRINT ^ JSON_UNESCAPED_UNICODE ^ JSON_UNESCAPED_SLASHES);
            }
            file_put_contents($answer_file, $txt);
        }
        else {
            // Format existing files.
            $json = json_decode(file_get_contents($answer_file));
            if ($json != null) {
                file_put_contents($answer_file, json_encode($json, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_UNICODE ^ JSON_UNESCAPED_SLASHES));
            }
        }

        $lines = explode("\n", file_get_contents($answer_file));
        foreach ($lines as $line) {
            if (trim($line) == '') {
                continue;
            }
            if (str_starts_with(trim($line), '{')
                || str_starts_with(trim($line), '"any_answers_found":')
                || str_starts_with(trim($line), '"answer1":')
                || str_starts_with(trim($line), '"answer2":')
                || str_starts_with(trim($line), '"answer3":')
                || str_starts_with(trim($line), '"answer4":')
                || str_starts_with(trim($line), '"answer5":')
                || str_starts_with(trim($line), '"answer6":')
                || str_starts_with(trim($line), '"answer7":')
                || str_starts_with(trim($line), '}')) {
                continue;
            }
            var_dump($line);
            var_dump($obj->choices[0]->message->content);
            throw new Exception('Buggy JSON: ' . $answer_file);
        }
        //exit;

        $answer_file2 = str_replace('/openai_txt_extract-', '/answer-', $file . '.extract.analyze');
        if (file_exists($answer_file2)) {
            $obj2 = readOpenaiDockerOutput($answer_file2);
            if (json_decode($obj2->choices[0]->message->content) != null) {
                $answer_obj = json_decode($obj2->choices[0]->message->content);
                $answers2 = json_encode($answer_obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE);
                foreach($answer_obj as $key => $value) {
                    if (
                        $key == 'answer1'
                        || $key == 'answer2'
                        || $key == 'answer3'
                        || $key == 'answer4'
                        || $key == 'answer5'
                        || $key == 'answer6'
                        || $key == 'answer7'
                    ) {
                        //continue;
                    }

                    if (!isset($summary[$key])) {
                        var_dump($answer_obj);
                        var_dump($key);
                        throw new Exception('Unknown key');
                    }
                    if ($value === false) {
                        $value = 'false';
                    }
                    if ($value === true) {
                        $value = 'true';
                    }
                    if (is_object($value)) {
                        $value = 'object answer - buggy...';
                    }
                    if (is_array($value)) {
                        $value = 'array answer - buggy...';
                    }
                    if (!isset($summary[$key][$value])) {
                        $summary[$key][$value] = 0;
                    }
                    $summary[$key][$value]++;
                }
            }
            else {
                $answers2 = $obj2->choices[0]->message->content;
            }
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


$summary_html = "<table>\n";
foreach($summary as $question => $answers) {
    $summary_html .= "<tr>\n";
    $summary_html .= "<th>$question</th>\n";
    $summary_html .= "<td>\n";
    foreach($answers as $answer => $num) {
        $summary_html .= "$answer: $num<br>\n";
    }
    $summary_html .= "</td>\n";
    $summary_html .= "</tr>\n";

}
$summary_html .= "</table>\n";
$html = str_replace('TABLE_SUMMARY', $summary_html, $html);

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