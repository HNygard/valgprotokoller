<?php
/**
 * HTML report from the JSON
 *
 * @author Hallvard Nygård, @hallny
 */


set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$files = getDirContents(__DIR__ . '/data-store/json');
$html = "<!DOCTYPE html>
<html>
<head>
  <meta charset=\"UTF-8\">
  <title>Valgprotokoller</title>
</head>
<body>
<style>
table th {
text-align: left;
}
table td {
text-align: right;
}
</style>

<h1>Election protocol (\"Valgprotokoller\" / \"Valgstyrets møtebok\")</h1>\n";
$html .= "Created by <a href='https://twitter.com/hallny'>@hallny</a> / <a href='https://norske-postlister.no'>Norske-postlister.no</a><br>\n";
$html .= "<a href='https://github.com/HNygard/valgprotokoller/blob/master/data-store/urls.txt'>Source - URL list</a> -\n";
$html .= "<a href='https://github.com/elections-no/elections-no.github.io/tree/master/docs/2019'>Source - Elections.no</a> -\n";
$html .= "<a href='https://github.com/HNygard/valgprotokoller'>Source code for this report</a> (Github)<br>\n";
$html .= '<h2>Summary</h2>
<ul>-----SUMMARY-----HERE-----</ul>

<h2>D1.4 Discrepancy between initial and final counting of pre-election-day votes ("Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler")</h2>
<i>Summary lines for each election in each municipality below.</i>
----D1.4-TABLE---

<h2>D2.4 Discrepancy between initial and final counting of ordinary votes ("Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler")</h2>
<i>Summary lines for each election in each municipality below.</i>
----D2.4-TABLE---

';
$summary_html = '';

$html_d1_4 = '<table>
<tr>
<th>Election - Municipality</th>
<td>Initial count ("Foreløpig")</td>
<td>Final count ("Endelig")</td>
<td>Discrepancy ("Avvik")</td>
</tr>
';
$html_d2_4 = $html_d1_4;

$html .= "<h2>Merknader (Comments to discrepancy)</h2>
<ul>\n";
foreach ($files as $file) {

    // => Parse this file. Line by line.
    logInfo('Parsing [' . str_replace(__DIR__ . '/', '', $file) . '].');

    if ($file) {
        $obj = new stdClass();
    }
    $obj = json_decode(file_get_contents($file));

    if ($obj->error || $obj->documentType != 'valgprotokoll' || !isset($obj->election) || !isset($obj->municipality)) {
        continue;
    }

    $summary_html .= '<li>' . $obj->election . ' - ' . $obj->municipality . "</li>\n";

    $html .= '<li>' . $obj->election . ' - ' . $obj->municipality . "\n"
        . "<ul>\n";
    foreach ($obj->comments as $commentType => $comments) {
        $html .= "<li><b>$commentType: </b>" . implode("<br>", $comments) . "</li>\n";
    }
    $html .= "</ul></li>\n";


    $html_d1_4 .= '<tr>
    <th>' . $obj->election . ' - ' . $obj->municipality . '</th>
    <td>' . $obj->numbers->{'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'}->{'Foreløpig'} . '</td>
    <td>' . $obj->numbers->{'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'}->{'Endelig'} . '</td>
    <td>' . $obj->numbers->{'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'}->{'Avvik'} . '</td>
</tr>
';
    $html_d2_4 .= '<tr>
    <th>' . $obj->election . ' - ' . $obj->municipality . '</th>
    <td>' . $obj->numbers->{'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'}->{'Foreløpig'} . '</td>
    <td>' . $obj->numbers->{'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'}->{'Endelig'} . '</td>
    <td>' . $obj->numbers->{'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'}->{'Avvik'} . '</td>
</tr>
';
}

$html .= "</ul>\n\n";

$html_d1_4 .= '</table>';
$html_d2_4 .= '</table>';

$html = str_replace('-----SUMMARY-----HERE-----', $summary_html, $html);
$html = str_replace('----D1.4-TABLE---', $html_d1_4, $html);
$html = str_replace('----D2.4-TABLE---', $html_d2_4, $html);
file_put_contents(__DIR__ . '/docs/index.html', $html);

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

function getDirContents($dir) {
    $command = 'find "' . $dir . '"';
    logDebug('Exec: ' . $command);
    exec($command, $find);
    logDebug('- Found [' . count($find) . ']');
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

