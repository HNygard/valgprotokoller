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

function htmlHeading($title = 'Valgprotokoller') {
    return "<!DOCTYPE html>
<html>
<head>
  <meta charset=\"UTF-8\">
  <title>$title</title>
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
text-align: right;
border: 1px solid lightgrey;
padding: 2px;

}
table {
border-collapse: collapse;
}
</style>";
}

$html = htmlHeading() . "

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

$d1_4_heading = '<table>
<tr>
<th>Election - Municipality</th>
<td>Initial count ("Foreløpig")</td>
<td>Final count ("Endelig")</td>
<td>Discrepancy ("Avvik")</td>
<td>Discrepancy %<br>(final - initial) / initial</td>
</tr>
';
$html_d1_4 = $d1_4_heading;
$html_d2_4 = $html_d1_4;

$html .= "<h2>Merknader (Comments to discrepancy)</h2>
<ul>\n";

$number_if_large_diff = function ($numbers, $text) {
    global $partyNameShorten;
    if (str_contains($text, 'Totalt antall partifordelte stemmesedler')) {
        return '';
    }

    if ($numbers->{'Foreløpig'} != 0) {
        $diff_percent = 100 * (($numbers->{'Endelig'} - $numbers->{'Foreløpig'}) / $numbers->{'Foreløpig'});
        $formattedNumber = number_format($diff_percent, 2);

        if (($diff_percent >= 1 || $diff_percent <= -1)) {
            return "\n<span style='color: red;'>" . $formattedNumber . ' %</span> ' . $partyNameShorten($text);
        }
        return '';
    }
    elseif ($numbers->{'Endelig'} != 0) {
        return "\n<span style='color: red;'>∞</span> " . $partyNameShorten($text);
    }
};

$d1_4_d2_4_row = function ($numbers, $text, $append = '') {

    if ($numbers->{'Foreløpig'} != 0) {
        $diff_percent = 100 * (($numbers->{'Endelig'} - $numbers->{'Foreløpig'}) / $numbers->{'Foreløpig'});
        $formattedNumber = number_format($diff_percent, 2);
    }
    else {
        $diff_percent = 0;
        $formattedNumber = '<i>N/A</i>';
    }

    return '<tr>
    <th>' . $text . '</th>
    <td>' . $numbers->{'Foreløpig'} . '</td>
    <td>' . $numbers->{'Endelig'} . '</td>
    <td>' . $numbers->{'Avvik'} . '</td>
    <td style="' . (($diff_percent >= 1 || $diff_percent <= -1) ? 'color: red;' : '') . '">' . $formattedNumber . ' %</td>
' . $append . '
</tr>
';
};

$partyNameShorten = function ($text) {
    $party = array(
        'Liberalistene' => 'Lib',
        'Kristelig Folkeparti' => 'KRF',
        'SV - Sosialistisk Venstreparti' => 'SV',
        'Venstre' => 'V',
        'Rødt' => 'R',
        'Fremskrittspartiet' => 'FRP',
        'Pensjonistpartiet' => 'PP',
        'Demokratene' => 'Dem',
        'Miljøpartiet De Grønne' => 'MDG',
        'Folkeaksjonen Nei til mer bompenger' => 'FNB',
        'Norges Kommunistiske Parti' => 'NKP',
        'Piratpartiet' => 'Piratp',
        'Arbeiderpartiet' => 'AP',
        'Nei til bompenger i Tromsø' => 'Bom Trømsø'
    );

    if (isset($party[$text])) {
        return $party[$text];
    }
    return $text;
};

foreach ($files as $file) {

    if ($file) {
        $obj = new stdClass();
    }
    $obj = json_decode(file_get_contents($file));

    if ($obj->error || $obj->documentType != 'valgprotokoll' || !isset($obj->election) || !isset($obj->municipality)) {
        continue;
    }
    logInfo('Using [' . str_replace(__DIR__ . '/', '', $file) . '].');

    $summary_html .= '<li>' . $obj->election . ' - ' . $obj->municipality . "</li>\n";

    $new_path = str_replace('.json', '.html', str_replace('data-store/json/', '', str_replace(__DIR__ . '/', '', $file)));
    $electionHtml = htmlHeading($obj->municipality . ' - ' . $obj->election . ' - Valgprotokoll') . '
<a href="../../">Tilbake</a>

<h1>' . $obj->election . ' - ' . $obj->municipality . "</h1>\n";

    if (isset($obj->url)) {
        $electionHtml .= 'Kilde: <a href="' . $obj->url . '">' . $obj->url . '</a>';
    }
    elseif (str_contains()) {
        $html .= '<li><a href="' . $new_path . '">' . $obj->election . ' - ' . $obj->municipality . "</a>\n"
            . "<ul>\n";
    }
    foreach ($obj->comments as $commentType => $comments) {
        $html .= "<li><b>$commentType: </b>" . implode("<br>", $comments) . "</li>\n";
    }
    $html .= "</ul></li>\n";


    $partyLargeDiscrepancies_D1_4 = '';
    $electionHtml .= "<h2>D1.4 Discrepancy between initial and final counting of pre-election-day votes (\"Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler\")</h2>\n";
    $electionHtml .= $d1_4_heading;
    foreach ($obj->numbers->{'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'} as $party => $numbers) {
        $electionHtml .= $d1_4_d2_4_row($numbers, $party);
        $partyLargeDiscrepancies_D1_4 .= $number_if_large_diff($numbers, $party);
    }
    $electionHtml .= "</table>\n\n";
    $electionHtml .= "<h3>Comments to D1.4 ('D1.5 Merknad')</h3>\n";
    $electionHtml .= "<div style='background-color: lightgray; margin-left: 0.5em; padding: 1em;'>"
        . str_replace("\n", '<br>', implode("<br><br>", $obj->comments->{'D1.5 Merknad'})) . "</div>\n\n";

    $partyLargeDiscrepancies_D2_4 = '';
    $electionHtml .= "<h2>D2.4 Discrepancy between initial and final counting of ordinary votes (\"Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler\")</h2>\n";
    $electionHtml .= $d1_4_heading;
    foreach ($obj->numbers->{'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'} as $party => $numbers) {
        $electionHtml .= $d1_4_d2_4_row($numbers, $party);
        $partyLargeDiscrepancies_D2_4 .= $number_if_large_diff($numbers, $party);
    }
    $electionHtml .= "</table>\n\n";
    $electionHtml .= "<h3>Comments to D2.4 ('D2.5 Merknad')</h3>\n";
    $electionHtml .= "<div style='background-color: lightgray; margin-left: 0.5em; padding: 1em;'>"
        . str_replace("\n", '<br>', implode("<br><br>", $obj->comments->{'D2.5 Merknad'})) . "</div>\n\n";


    $d1_4_numbers = $obj->numbers->{'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'};
    $html_d1_4 .= $d1_4_d2_4_row($d1_4_numbers,
        '<a href="' . $new_path . '">' . $obj->election . ' - ' . $obj->municipality . '</a>',
        '<td style="text-align: left;">' . str_replace("\n", ",\n", trim($partyLargeDiscrepancies_D1_4)) . '</td>');
    $d2_4_numbers = $obj->numbers->{'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'};
    $html_d2_4 .= $d1_4_d2_4_row($d2_4_numbers,
        '<a href="' . $new_path . '">' . $obj->election . ' - ' . $obj->municipality . '</a>',
        '<td style="text-align: left;">' . str_replace("\n", ",\n", trim($partyLargeDiscrepancies_D2_4)) . '</td>');


    $new_file = __DIR__ . '/docs/' . $new_path;
    if (!file_exists(dirname($new_file))) {
        echo '-- Creating [' . dirname($new_file) . "\n";
        mkdir(dirname($new_file), 0777, true);
    }

    file_put_contents($new_file, $electionHtml);
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

