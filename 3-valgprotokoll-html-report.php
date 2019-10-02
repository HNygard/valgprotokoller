<?php
/**
 * HTML report from the JSON
 *
 * @author Hallvard Nygård, @hallny
 */


set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$files = getDirContents(__DIR__ . '/docs/data-store/json');

$entitiesArray = json_decode(file_get_contents(__DIR__ . '/entities.json'))->entities;
$entitiesArray2 = json_decode(file_get_contents(__DIR__ . '/entitiesNonExisting.json'))->entities;
$entity_id__to__obj = array();
$entity_name__to__entity_id = array();
foreach ($entitiesArray as $entity) {
    $entity_id__to__obj[$entity->entityId] = $entity;
    $entity_name__to__entity_id[$entity->name] = $entity->entityId;
}
foreach ($entitiesArray2 as $entity) {
    $entity_id__to__obj[$entity->entityId] = $entity;
    $entity_name__to__entity_id[$entity->name] = $entity->entityId;
}

$mimesBronnStatus = (array)json_decode(file_get_contents(__DIR__ . '/docs/data-store/mimesbronn-result/result.json'));

$entity_merging = array(
    'Skedsmo kommune' => 'Lillestrøm kommune',
    'Spydeberg kommune' => 'Indre Østfold kommune',
    'Hobøl kommune' => 'Indre Østfold kommune',
    'Trøgstad kommune' => 'Indre Østfold kommune',
    'Tysfjord kommune' => array('Nye Narvik', 'Nye Hamarøy'),
    'Snillfjord kommune' => array('Orkland kommune', 'Heim kommune', 'Hitra kommune'),
    'Ballangen kommune' => 'Narvik kommune',
    'Hemne kommune' => 'Heim kommune',
    'Rømskog kommune' => 'Aurskog-Høland kommune'
);


$klager = array();

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
$html .= "<a href='https://github.com/HNygard/valgprotokoller/blob/master/docs/data-store/urls.txt'>Source - URL list</a> -\n";
$html .= "<a href='https://github.com/elections-no/elections-no.github.io/tree/master/docs/2019'>Source - Elections.no</a> -\n";
$html .= "<a href='https://github.com/HNygard/valgprotokoller'>Source code for this report</a> (Github)<br>\n";
$html .= "<br><br><a href='ballot-stuffing.html'>Ballot stuffing</a>\n\n";
$html .= '<h2>Summary</h2>
<ul>-----SUMMARY-----HERE-----</ul>

<h2>D1.4 Discrepancy between initial and final counting of pre-election-day votes ("Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler")</h2>
<i>Summary lines for each election in each municipality below.</i>
----D1.4-TABLE---

<h2>D2.4 Discrepancy between initial and final counting of ordinary votes ("Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler")</h2>
<i>Summary lines for each election in each municipality below.</i>
----D2.4-TABLE---

';
$summaryData = array(
    'Fylkestingsvalget 2019' => 0,
    'Kommunestyrevalget 2019' => 0
);

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

$html_BallotStuffing = htmlHeading('Ballot stuffing - Norwegian elections 2019') . "
<a href='./'>Back to overview page</a>
<h1>Ballot stuffing</h1>
<i>Ballot stuffing is to put more than one ballot in the ballot box OR people monitoring or handling ballots/ballot boxes
adds ballots.. Since the Norwegian elections in 2019 had two election in one, the only control number is the number of
total people that voted. Most people vote in the local election (municipality) and not the regional election (county).
By blindly adding extra ballots in the regional election, you would have a good chance of adding extra votes without 
being noticed.</i><br><br>

<i>This overview will show you data on the issue to check if this might happen to the Norwegian elections.</i><br><br>

<i>The election in Evenes did notice:<br>
- <a href='https://www.evenes.kommune.no/startsiden/nyhetsarkiv/5596-pressemelding.html'>(Norwegian) Evenes municipality - Press release - Election board recommands not approving the election for municipality</a></i><br>
- <a href='https://www.vg.no/nyheter/innenriks/i/GGKOBJ/anbefaler-at-kommunevalg-ikke-godkjennes'>(Norwegian) VG.no - Anbefaler at kommunevalg ikke godkjennes</a></i>

<table>
";

$number_if_large_diff = function ($numbers, $text) {
    global $partyNameShorten;
    if (str_contains($text, 'Totalt antall partifordelte stemmesedler')) {
        return '';
    }

    $diff = $numbers->{'Endelig'} - $numbers->{'Foreløpig'};
    $diffSign = $diff > 0 ? '+' : '';
    $diffStyle = abs($diff) > 40 ? " style='color: blue;'" : '';
    $diffHtml = " (<span$diffStyle>$diffSign$diff votes</span>)\n";
    if ($numbers->{'Foreløpig'} != 0) {
        $diff_percent = 100 * (($diff) / $numbers->{'Foreløpig'});
        $formattedNumber = number_format($diff_percent, 2);

        if (($diff_percent >= 1 || $diff_percent <= -1)) {
            return "\n<span style='color: red;'>" . $formattedNumber . ' %</span> '
                . $partyNameShorten($text)
                . $diffHtml;
        }
        if (abs($diff) >= 40) {
            return "\n<span style='color: #5b5700;'>" . $formattedNumber . ' %</span> '
                . $partyNameShorten($text)
                . $diffHtml;
        }
        return '';
    }
    elseif ($numbers->{'Endelig'} != 0) {
        return "\n<span style='color: red;'>∞</span> " . $partyNameShorten($text) . $diffHtml;
    }
};
$number_if_large_diff_klage = function ($numbers, $text) {
    global $partyNameShorten;

    $diff = $numbers->{'Endelig'} - $numbers->{'Foreløpig'};
    $diffSign = $diff > 0 ? '+' : '';
    if ($numbers->{'Foreløpig'} != 0) {
        $diff_percent = 100 * (($diff) / $numbers->{'Foreløpig'});
        $formattedNumber = number_format($diff_percent, 2);

        if (($diff_percent >= 1 || $diff_percent <= -1)) {
            return $partyNameShorten($text) . ': Stort prosentavvik mellom foreløpig og endelig. Avvik på ' . $diffSign . $formattedNumber . '% (' . $diffSign . $diff . ' stemmer).';
        }
        if (abs($diff) >= 40) {
            return $partyNameShorten($text) . ': Stort antall stemmer mellom foreløpig og endelig. Avvik på ' . $diffSign . $diff . ' stemmer.';
        }
    }
    return '';
};

$d1_4_d2_4_row = function ($numbers, $text, $append = '') {

    $diff = $numbers->{'Endelig'} - $numbers->{'Foreløpig'};
    if ($numbers->{'Foreløpig'} != 0) {
        $diff_percent = 100 * (($diff) / $numbers->{'Foreløpig'});
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
    <td' . (abs($diff) > 40 ? ' style="color: blue;"' : '') . '>'
        . (($numbers->{'Avvik'} > 0) ? '+' : '')
        . $numbers->{'Avvik'} . '</td>
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

function getNewPath($file) {
    return str_replace('.json', '.html', str_replace('docs/data-store/json/', '', str_replace(__DIR__ . '/', '', $file)));
}

foreach ($files as $file) {

    if ($file) {
        $obj = new stdClass();
    }
    $obj = json_decode(file_get_contents($file));

    if ($obj->error || $obj->documentType != 'valgprotokoll' || !isset($obj->election) || !isset($obj->municipality)) {
        continue;
    }
    logInfo('Using [' . str_replace(__DIR__ . '/', '', $file) . '].');

    $name = $obj->municipality;
    $name = str_replace('Aurskog -Høland', 'Aurskog-Høland', $name);
    $name = str_replace('Unjárga - Nesseby', 'Nesseby', $name);
    $name = str_replace('Porsanger - Porságu - Porsanki', 'Porsanger', $name);
    $obj->file = $file;
    $entity_id__to__obj[$entity_name__to__entity_id[$name . ' kommune']]->elections[] = $obj;

    $summaryData[$obj->election]++;

    $new_path = getNewPath($file);
    $electionHtml = htmlHeading($obj->municipality . ' - ' . $obj->election . ' - Valgprotokoll') . '
<a href="../../">Back to overview page</a>

<h1>' . $obj->election . ' - ' . $obj->municipality . "</h1>\n";

    $urlLocal = '../../data-store/pdfs/' . str_replace('docs/data-store/pdfs/', '', $obj->localSource);
    if (isset($obj->url) && $obj->url != '<missing>') {
        $obj->url2 = $obj->url;
        $pdfLink = str_replace('.layout.txt', '', $urlLocal);
        $pdfName = htmlentities(urldecode(basename($obj->url2)), ENT_QUOTES);
        $pdfOriginal = $obj->url;
    }
    elseif (str_contains($obj->localSource, 'elections-no.github.io')) {
        // Example localSource
        // docs/data-store/pdfs/elections-no.github.io-docs-2019-Agder-Bykle kommune, Agder fylke - kommunestyrevalget4222_2019-09-10.pdf.layout.txt
        $link = $obj->localSource;
        foreach (array(
                     'Agder',
                     'Innlandet',
                     'Viken',
                     'Vestland',
                     'Rogaland',
                     'More_og_Romsdal',
                     'Nordland',
                     'Trondelag',
                     'Troms_og_Finnmark',
                     'Vestfold_og_Telemark'
                 ) as $county) {
            $link = str_replace(
                'docs/data-store/pdfs/elections-no.github.io-docs-2019-' . $county . '-',
                'https://github.com/elections-no/elections-no.github.io/blob/master/docs/2019/' . $county . '/',
                $link
            );
        }
        $link = str_replace('https://github.com/elections-no/elections-no.github.io/blob/master/docs', 'https://elections.no/docs', $link);
        $link = str_replace('.layout.txt', '', $link);
        if (!str_starts_with($link, 'https://')) {
            throw new Exception('Unknown link: ' . $link);
        }
        $pdfLink = $link;
        $pdfName = htmlentities(urldecode(basename($link)), ENT_QUOTES);
        $pdfOriginal = null;
    }
    else {
        throw new Exception('Unknown source: ' . $obj->localSource);
    }
    $obj->url2 = $pdfLink;

    $electionHtml .= 'Source: <a rel="nofollow" href="'
        . $pdfLink . '">'
        . $pdfName
        . '</a> (click to view PDF)' . chr(10)
        . ' [<a href="' . $urlLocal . '" rel="nofollow">as text</a>]' . chr(10)
        . ' [<a href="' . str_replace(__DIR__ . '/docs/', '../../', $file) . '" rel="nofollow">as JSON</a>]<br>' . chr(10);
    if ($pdfOriginal != null) {
        $electionHtml .= 'Original source: <a href="' . $obj->url . '" rel="nofollow">' . $obj->url . '</a>';
    }

    foreach ($obj->comments as $commentType => $comments) {
        $html .= "<li><b>$commentType: </b>" . implode("<br>", $comments) . "</li>\n";
    }
    $html .= "</ul></li>\n";

    // :: Individual election pages
    $partyLargeDiscrepancies_D1_4 = array();
    $partyLargeDiscrepancies_D1_4_klage = array();
    $electionHtml .= "<h2>D1.4 Discrepancy between initial and final counting of pre-election-day votes (\"Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler\")</h2>\n";
    $electionHtml .= $d1_4_heading;
    foreach ($obj->numbers->{'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'} as $party => $numbers) {
        $electionHtml .= $d1_4_d2_4_row($numbers, $party);
        $partyLargeDiscrepancies_D1_4[] = $number_if_large_diff($numbers, $party);
        $klageNum = $number_if_large_diff_klage($numbers, $party);
        if (!empty($klageNum)) {
            $partyLargeDiscrepancies_D1_4_klage[] = $klageNum;
            $avvik_forelopig_endelig_comments['D1.5 Merknad'] = $obj->comments->{'D1.5 Merknad'};
        }
    }
    $electionHtml .= "</table>\n\n";
    $electionHtml .= "<h3>Comments to D1.4 ('D1.5 Merknad')</h3>\n";
    $electionHtml .= "<div style='background-color: lightgray; margin-left: 0.5em; padding: 1em;'>"
        . str_replace("\n", '<br>', implode("<br><br>", $obj->comments->{'D1.5 Merknad'})) . "</div>\n\n";

    $partyLargeDiscrepancies_D2_4 = array();
    $partyLargeDiscrepancies_D2_4_klage = array();
    $electionHtml .= "<h2>D2.4 Discrepancy between initial and final counting of ordinary votes (\"Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler\")</h2>\n";
    $electionHtml .= $d1_4_heading;
    foreach ($obj->numbers->{'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'} as $party => $numbers) {
        $electionHtml .= $d1_4_d2_4_row($numbers, $party);
        $partyLargeDiscrepancies_D2_4[$party] = $number_if_large_diff($numbers, $party);
        $klageNum = $number_if_large_diff_klage($numbers, $party);
        if (!empty($klageNum)) {
            $partyLargeDiscrepancies_D2_4_klage[] = $klageNum;
            $avvik_forelopig_endelig_comments['D2.5 Merknad'] = $obj->comments->{'D2.5 Merknad'};
        }
    }
    $electionHtml .= "</table>\n\n";
    $electionHtml .= "<h3>Comments to D2.4 ('D2.5 Merknad')</h3>\n";
    $electionHtml .= "<div style='background-color: lightgray; margin-left: 0.5em; padding: 1em;'>"
        . str_replace("\n", '<br>', implode("<br><br>", $obj->comments->{'D2.5 Merknad'})) . "</div>\n\n";

    // :: Mandates based on D1.4 and D2.4
    $partyLargeDiscrepancies_E1_1 = array();
    $partyLargeDiscrepancies_E1_1_klage = array();
    $electionHtml .= "<h2>E1.1 Seats to each political party (\"Beregning av listestemmetall og antall mandater til listene\") vs simulated seats based on initial counting (D1.4)</h2>\n";
    if (!isset($obj->e1_mandatPerParty)) {
        $electionHtml .= '<i>Not available in this election.</i>';
    }
    else {
        $electionHtml .= '<table>
<tr>
<th>Party</th>
<td>Simulated seats - Initial count ("Foreløpig")</td>
<td>Actual seats - Final count ("Endelig")</td>
<td>Discrepancy ("Avvik")</td>
</tr>';
        foreach ($obj->e1_mandatPerParty as $partyName => $partySeats) {
            $current_paarty = null;
            foreach ($obj->e1_1_listestemmetall_og_mandater as $party) {
                if ($party->name == $partyName) {
                    $current_paarty = $party;
                }
            };

            $diff = $partySeats - $obj->e1_mandatPerParty_simulated_initial_counting->{$partyName};
            $diffColor = $diff > 0 ? ' style="color: green;"' : '';
            $diffColor = $diff < 0 ? ' style="color: red;"' : $diffColor;

            $diff_votes = $current_paarty->stemmesedler - $current_paarty->stemmesedler_initial_counting;
            $diff_percent_votes = $diff_votes / $current_paarty->stemmesedler_initial_counting;

            $electionHtml .= '<tr>
    <th>' . $partyName . '</th>
    <td>
    ' . $obj->e1_mandatPerParty_simulated_initial_counting->{$partyName} . ' seats<br>
    (' . $current_paarty->stemmesedler_initial_counting . ' votes)
    </td>
    <td>
    ' . $partySeats . ' seats<br>
    (' . $current_paarty->stemmesedler . ' votes)
    </td>
    <td> <span' . ($diffColor) . '>'
                . (($diff > 0) ? '+' : '')
                . $diff . ($diff != 0 ? ' seats' : '') . '</span><br>
      <span' . (abs($diff_votes) > 40 ? ' style="color: blue;"' : '') . '>'
                . (($diff_votes > 0) ? '+' : '')
                . $diff_votes . ' votes</span>, 
    <span style="' . (($diff_percent_votes >= 1 || $diff_percent_votes <= -1) ? 'color: red;' : '') . '">' . number_format($diff_percent_votes, 2) . ' %</span>
    
    </td>
</tr>
';
            if ($diff != 0) {
                $partyLargeDiscrepancies_E1_1[] = $partyNameShorten($partyName) . ': ' . '<span ' . $diffColor . '>' . (($diff > 0) ? '+' : '') . $diff . ' seats changed from initial to final counting.</span>';
                $partyLargeDiscrepancies_E1_1_klage[] = $partyNameShorten($partyName) . ': ' . (($diff > 0) ? '+' : '') . $diff . ' mandat i endring fra foreløig til endelig opptelling.';
            }
        }
        $electionHtml .= "</table>\n\n";
    }
    $avvik_forelopig_endelig = count($partyLargeDiscrepancies_D1_4_klage) > 0
        || count($partyLargeDiscrepancies_D2_4_klage) > 0
        || count($partyLargeDiscrepancies_E1_1_klage) > 0;

    // :: D1.4 and D2.4 summary page

    $partyLargeDiscrepancies_E1_1 = (count($partyLargeDiscrepancies_E1_1) > 0)
        ? chr(10) . '<br>Pre+Main votes:<br>' . implode("\n<br>", $partyLargeDiscrepancies_E1_1)
        : '';

    $d1_4_numbers = $obj->numbers->{'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'};
    $html_d1_4 .= $d1_4_d2_4_row($d1_4_numbers,
        '<a href="' . $new_path . '">' . $obj->election . ' - ' . $obj->municipality . '</a>',
        '<td style="text-align: left;">' . str_replace("\n", ",\n", trim(implode('', $partyLargeDiscrepancies_D1_4))) . $partyLargeDiscrepancies_E1_1 . '</td>');
    $d2_4_numbers = $obj->numbers->{'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'};
    $html_d2_4 .= $d1_4_d2_4_row($d2_4_numbers,
        '<a href="' . $new_path . '">' . $obj->election . ' - ' . $obj->municipality . '</a>',
        '<td style="text-align: left;">' . str_replace("\n", ",\n", trim(implode('', $partyLargeDiscrepancies_D2_4))) . $partyLargeDiscrepancies_E1_1 . '</td>');


    // :: Ballot stuffing
    // Ballot count contain:
    // - Kryss i manntall - Number of voters
    // - Ant. sedler - Number of ballots after election
    $ballotsPreOrdinary = $obj->numbers->{'B2.1.1 Behandlede ordinære forhåndsstemmesedler'}->{'Total antall behandlede forhåndsstemmesedler'};
    $ballotsPreLate = $obj->numbers->{'B2.2.1 Behandlede sent innkomne forhåndsstemmesedler'}->{'Total antall sent innkomne forhåndsstemmesedler'};
    if ($obj->foretattForeløpigOpptellingHosStemmestyrene) {
        $ballotsMainFirstCounting = $obj->numbers->{'C2.1 Antall valgtingsstemmesedler i urne'}->{'Total antall valgtingstemmesedler i urne'};
    }
    else {
        $ballotsMainFirstCounting = null;
    }

    $ballotStuffingErrors = array();
    $ballotStuffingErrorsComments = array();
    $ballotStuffingRow = function ($text, $textNor, $ballots, $comments) {
        $checksum = $ballots->{'Kryss i manntall'} - $ballots->{'Ant. sedler'};

        $color = (str_contains($text, 'Main-votes') || str_contains($text, 'Key figur'))
            ? 'red'
            : '';
        $extraText = $color == 'red' ? ' This should not happen.' : ' Pre-votes: OK.';

        if ($comments != null) {
            global $obj;
            $comments2 = $comments . ":\n" . implode("\n", $obj->comments->$comments);
            $textNor .= ' (avvik skal forklares i ' . $comments . ')';
        }
        else {
            $comments2 = '';
        }

        if ($checksum < 0 && $color == 'red') {
            global $ballotStuffingErrors, $ballotStuffingErrorsComments;
            $ballotStuffingErrors[$textNor] = 'Flere antall stemmesedler (' . $ballots->{'Ant. sedler'} . ') enn antall kryss i manntall (' . $ballots->{'Kryss i manntall'} . ').'
                . ' ' . abs($checksum) . ' flere stemmesedler enn det skulle vært.';
            if ($comments != null) {
                $ballotStuffingErrorsComments[$comments] = $obj->comments->$comments;
            }
        }

        return "<td>$text</td>
    <td>" . $ballots->{'Kryss i manntall'} . '</td>
    <td>' . $ballots->{'Ant. sedler'} . '</td>
    <td>' . $checksum . '</td>
    <td style="text-align: left;">' . ($checksum >= 0
                ? '<span style="color: darkgreen;">OK. ' . $checksum . ' voters crossed out in census, but didn\'t vote.</span>'
                : '<span style="color: ' . $color . ';">More votes (' . $ballots->{'Ant. sedler'} . ' ballots) then people who was'
                . ' crossed out in census (' . $ballots->{'Kryss i manntall'} . ').' . $extraText . '</span>') . '</td>
    <td style="max-width: 300px; text-align: left; font-size: 0.4em;">' . nl2br($comments2) . '</td>
    ';
    };


    $total = new stdClass();
    $total->{'Kryss i manntall'} = $obj->keyfigures_totaltAntallKryssIManntallet;
    $total->{'Ant. sedler'} = $obj->keyfigures_totaltAntallGodkjenteStemmesedler;

    $ballotsMainFirstCount = null;
    if ($obj->foretattForeløpigOpptellingHosValgstyret) {
        $c4_1_numbers = $obj->numbers
            ->{'C4.1 Antall valgtingsstemmesedler i urne'}
            ->{'Total antall valgtingstemmesedler i urne'};
        $ballotsMainFirstCount = $c4_1_numbers;
        $totalKryssIManntallMainVotes = $c4_1_numbers->{'Kryss i manntall'};
    }
    else {
        $totalKryssIManntallMainVotes = $ballotsMainFirstCounting->{'Kryss i manntall'};
    }


    $d2_1_numbers = $obj->numbers->{'D2.1 Opptalte valgtingsstemmesedler'}->{'Total antall opptalte valgtingsstemmesedler'};
    $ballotsMainFinalCount = new stdClass();
    $ballotsMainFinalCount->{'Kryss i manntall'} = $totalKryssIManntallMainVotes;
    // TODO: should we also add forkastet?
    $ballotsMainFinalCount->{'Ant. sedler'} = $d2_1_numbers->{'Godkjente'};

    $html_BallotStuffing .= "<tr>
    <th rowspan='6'><a href='" . $new_path . "'>" . $obj->election . " - " . $obj->municipality . "</a></th>
        " . $ballotStuffingRow(
            'Key figures',
            'Totalt',
            $total,
            null
        ) . "
</tr>
<tr>
    " . $ballotStuffingRow(
            'B2.1.1 - Pre-votes - ordinary',
            'B2.1.1 - Forhåndsstemmer - ordinære',
            $ballotsPreOrdinary,
            'B2.1.3 Merknad'
        ) . '
</tr>
<tr>
    ' . $ballotStuffingRow(
            'B2.2.1 - Pre-votes - late arrival',
            'B2.2.1 - Forhåndsstemmer - sent innkomme',
            $ballotsPreLate,
            'B2.2.3 Merknad'
        ) . '
</tr>
<tr>
    ' . ($ballotsMainFirstCounting == null
            ? '<td>C2.1 - Main votes - prelim counting stemmestyret</td><td>-</td>'
            : $ballotStuffingRow(
                'C2.1 - Main votes - prelim counting stemmestyret',
                'C2.1 - Valgtingsstemmer - foreløpig opptelling stemmestyret',
                $ballotsMainFirstCounting,
                'C2.3 Merknad fra stemmestyret'
            )) . '
</tr>
<tr>
    ' . ($ballotsMainFirstCount == null
            ? '<td>C4.1 - Main-votes - prelim counting valgstyret</td><td>-</td>'
            : $ballotStuffingRow(
                'C4.1 - Main-votes - prelim counting valgstyret',
                'C4.1 - Valgtingsstemmer - foreløpig opptelling valgstyret',
                $ballotsMainFirstCount,
                'C4.3 Merknad'
            )) . '
</tr>
<tr>
    ' . $ballotStuffingRow(
            'D2.1 - Main-votes - final counting',
            'D2.1 - Valgtingsstemmer - endelig opptelling',
            $ballotsMainFinalCount,
            'D2.5 Merknad'
        ) . '
</tr>

';
    $html_BallotStuffing .= '<tr>
<td colspan="6" style="background-color: lightgrey; min-height: 5px"></td>
</tr>
';

    $new_file = __DIR__ . '/docs/' . $new_path;
    if (!file_exists(dirname($new_file))) {
        echo '-- Creating [' . dirname($new_file) . "\n";
        mkdir(dirname($new_file), 0777, true);
    }


    // TODO:
    // [ ] B2.1.3 Merknad - Merknader til B2.1.1 + B2.1.2
    // [ ] B2.2.3 Merknad - Merknader til B2.2.1 + B2.2.2

    // [ ] Parse - two col - 'C2.1 Antall valgtingsstemmesedler i urne'
    // [ ] C2.3 Merknad fra stemmestyret - Merknader til 'C2.1 og C2.2'

    // [ ] Parse - two col - 'C4.4 Antall stemmesedler i særskilt omslag'
    // [ ] C4.6 Merknad - Merknader til C4.4 og C4.5

    // [ ] Parse - two col - 'C4.7 Antall stemmesedler i beredskapskonvolutt'
    // [ ] C4.9 Merknad

    // [ ] Parse - multi col - 'C1 Oversikt over stemmer mottatt i alle kretser'

    file_put_contents($new_file, $electionHtml);


    // :: Klage
    if ($avvik_forelopig_endelig || count($ballotStuffingErrors) > 0) {
        $klageType = '';
        $klage = 'TITTEL:
Klage på "Valgprotokoll for valgstyret - ' . $obj->election . '" for ' . $obj->municipality . '

KLAGE:
Resultatene som er presentert i disse dokumentene følger ikke kravene til Valgforskriften og kan derfor ikke godkjennes.

';
        $both = count($ballotStuffingErrors) > 0 && $avvik_forelopig_endelig;
        if ($both) {
            $klage .= 'Klage er todelt:
- Avvik i antall sedler i stemme urne opp mot manntall
- Avvik mellom foreløpig og endelig opptelling


';
        }


        // ----- AVVIK ballot stuffing
        if (count($ballotStuffingErrors)) {
            $klageType .= 'ballotStuffing';
            if ($both) {
                $klage .= "AVVIK STEMMESEDLER\n\n";
            }

            $klage .= 'Det er avvik mellom antall sedler i stemmeurne og antall kryss i manntall. Dette er ikke forklart i merknadsfeltene.
            
            <b>';

            foreach ($ballotStuffingErrors as $stuffingErrorType => $stuffingError) {
                if ($stuffingErrorType == 'Totalt') {
                    continue;
                }
                $klage .= "- $stuffingErrorType\n$stuffingError\n\n";
            }

            if (isset($ballotStuffingErrors['Totalt'])) {
                $klage .= $ballotStuffingErrors['Totalt'] . "\n\n";
            }

            $klage .= "Merknader:\n";
            if (count($ballotStuffingErrorsComments) == 0) {
                $klage .= "Ingen merknader på dette.\n";
            }
            foreach ($ballotStuffingErrorsComments as $stuffingErrorType => $stuffingError) {
                foreach ($stuffingError as $comment) {
                    $klage .= "- $stuffingErrorType:\n" . str_replace("\n", ' ', $comment) . "\n\n";
                }
            }
            $klage .= "</b>\n\n";
        }

        // ----- AVVIK foreløpig vs endelig
        if ($avvik_forelopig_endelig) {
            if (!empty($klageType)) {
                $klageType .= ' + ';
            }
            $klageType .= 'avvikForeløpigEndelig';
            if ($both) {
                $klage .= "AVVIK MELLOM FORELØPIG OG ENDELIG\n\n";
            }


            /*
            count($partyLargeDiscrepancies_D1_4_klage) > 0
            || count($partyLargeDiscrepancies_D2_4_klage) > 0
            || count($partyLargeDiscrepancies_E1_1_klage) > 0;
            */

            $klage .= 'I følge "§ 41.Fastsetting av formular" av Valgforskriften [1] så er "Valgmyndighetene er forpliktet til å benytte fastsatte formularer".

I "D1.5 Merknad" og "D2.5 Merknad" så skal man føre opp:
"(Årsak til evt. differanse mellom foreløpig og endelig opptelling av forhåndsstemmer.)"
"(Årsak til evt. differanse mellom foreløpig og endelig opptelling av valgtingsstemmer)"

I "Valgprotokoll for valgstyret - ' . $obj->election . '" [2] for ' . $obj->municipality . ' så har ikke dette blitt gjort.

';
            if (count($partyLargeDiscrepancies_D1_4_klage) > 0) {
                $klage .= "I D1.4 kan man se på avvikene på forhåndsstemmer, avvikene var for noen partier overraskende store. Årsaken til dette er ikke forklart.\n\n<b>";

                // - Norges Kommunistiske Parti mistet nærmest 86.7% av stemmene sine
                // - Folkeaksjonen Nei til mer bompenger økte med 5.7%
                foreach ($partyLargeDiscrepancies_D1_4_klage as $text) {
                    $klage .= "- $text\n";
                }
                $klage .= "</b>\n";
            }
            if (count($partyLargeDiscrepancies_D2_4_klage) > 0) {
                $klage .= "I D2.4 kan man se på avvikene på valgdagsstemmene, avvikene var for noen partier er sjokkerende store. Årsaken til dette er ikke forklart.\n\n<b>";

                // - Norges Kommunistiske Parti mistet nærmest 86.7% av stemmene sine
                // - Folkeaksjonen Nei til mer bompenger økte med 5.7%
                foreach ($partyLargeDiscrepancies_D2_4_klage as $text) {
                    $klage .= "- $text\n";
                }
                $klage .= "</b>\n";
            }

            if (count($partyLargeDiscrepancies_E1_1_klage) > 0) {
                $klage .= "I E1.1 kan man se mandatfordelingen i endelig opptelling. Dersom man beregner dette for foreløpig opptelling "
                    ."også, så kan man se at mandater har byttet party. I og med at avvik i D1.4/D2.4 ikke er forklart, er heller ikke mandatendringen blitt forklart.\n\n<b>";

                // - Norges Kommunistiske Parti mistet nærmest 86.7% av stemmene sine
                // - Folkeaksjonen Nei til mer bompenger økte med 5.7%
                foreach ($partyLargeDiscrepancies_E1_1_klage as $text) {
                    $klage .= "- $text\n";
                }
            }

            $klage .= '</b>De merknadene som er der tyder også på at man ikke så på avvikene fra manuell telling og/eller har ikke forklart hvilke kontrollmetoder som er utført for å påvise feil ved foreløpig opptelling. Eksempler:

';
            ksort($avvik_forelopig_endelig_comments);
            foreach($avvik_forelopig_endelig_comments as $commentType => $comments) {
                foreach($comments as $comment) {
                    $klage .= "<b>- $commentType:</b>\n$comment\n\n";
                }
            }
            $klage .='
Det er tydelig at man ikke har sett på avvikene mellom foreløpig telling og endelig telling. Dette bryter mot hele grunnlaget for valgforskrifts-endringen i § 37a [5].

“Manuell foreløpig opptelling er ikke ment som en erstatning for gjennomføring av tekniske og fysiske sikkerhetstiltak i opptellingen. '
                . 'Det er derimot ingen IT-systemer som er uten sårbarheter, og det er umulig å garantere at en programvare er fullstendig sikker.'
                . ' Å forskriftsfeste et krav om at den foreløpige opptellingen skal skje manuelt vil i større grad enn med dagens regelverk sikre to '
                . 'uavhengige opptellinger og derigjennom et korrekt opptellingsresultat. I tillegg vil to uavhengige opptellinger bidra til å gi '
                . 'legitimitet til valggjennomføringen. Manuell telling er en opptellingsmåte som er enkel å forstå og observere både for valgstyret '
                . 'som er ansvarlige for opptellingen, og for velgerne som skal være sikre på at valgresultatet er korrekt.”

';
        }
        if ($both) {
            $klage .= "OPPSUMMERING\n\n";
        }


        $klage .= 'Basert på det over er ikke “Valgprotokoll for valgstyret - ' . $obj->election . '” [2] for ' . $obj->municipality . ' i henhold til Valgforskriften [1].

';
        if ($avvik_forelopig_endelig) {
            $klage .= 'Mange av avvikene i ' . $obj->municipality . ' er overraskende store. Ser man på valgdagsstemmene i Oslo [6] så er gjennomsnittlig avvik på 0.35%, det at man i ' . $obj->municipality . ' sine valgprotokoller i tillegg ikke har forklart avvikene gjør at man ikke kan akseptere resultatene som er presentert.

';
        }

        $klage .= "Jeg ber om at saksnummer sendes i retur så fort dette dokumentet er journalført.\n\n";

        $klage .= 'Mvh

Hallvard Nygård
Twitter: @hallny

';

        $klage .= '

[1]: https://lovdata.no/dokument/SF/forskrift/2003-01-02-5#KAPITTEL_9
[2]: https://elections.no/docs/2019/Vestland/Valgstyrets-motebok-fylke2.PDF
[5]: https://elections.no/docs/veileder_for_manuell_opptelling.pdf
[6]: https://github.com/elections-no/elections-no.github.io/blob/master/docs/2019/Oslo/election-day.csv




TODO:
- Fiks linker
- Skriv link til valgprotokoll-oversiktene

';

        file_put_contents(__DIR__ . '/docs/klager/' . $obj->municipality . ' - ' . $obj->election . '.html',
            htmlHeading('Klage - ' . $obj->municipality . ' - ' . $obj->election)
            . '<style>body { white-space: pre-line; } </style>'
            . $klage);
        $klager[$obj->municipality . ' - ' . $obj->election . '.html'] = $klageType;
    }
}


$klagerGjennomgatt = array(
    'Haugesund - Fylkestingsvalget 2019.html' => 'Relativt små avvik. Større avvik på FRP. Ellers lite reelle merknader.'
);
$klagerFjernet = array(
    'Bergen - Fylkestingsvalget 2019.html' => 'Allerede klaget.',
    'Bergen - Kommunestyrevalget 2019.html' => 'Allerede påklaget.',
    'Evenes - Kommunestyrevalget 2019.html' => 'Allerede utvidet behandling og omtelling.',

    'Haugesund - Kommunestyrevalget 2019.html' => 'Små avvik. Skannet to ganger. Sliter med avvik i sedler vs manntall pga to valg.',
    'Fauske - Fylkestingsvalget 2019.html' => 'Små avvik. Skannet to ganger. OK kommentarer, selv om skanning er brukt to ganger.',
    'Frogn - Fylkestingsvalget 2019.html' => 'Større avvik på Piratpartiet. Skannet to ganger. Sliter med avvik i sedler vs manntall pga to valg.',
    'Aukra - Fylkestingsvalget 2019.html' => 'Større avvik på Venstre. Kommentert.'

);

$klager_html = htmlHeading('Klager');
ksort($klager);
$klager_html .= "<table>\n";
foreach ($klager as $klage => $klageType) {
    $klager_html .= "<tr>\n    <td style='text-align: left'>";
    if (isset($klagerFjernet[$klage])) {
        $klager_html .= "<span style='text-decoration: line-through'>";
    }
    $klager_html .= '<a href="./' . $klage . '">' . $klage . '</a> - ' . $klageType . "<br>";
    if (isset($klagerFjernet[$klage])) {
        $klager_html .= "</span>";
    }
    $klager_html .= "</td>\n";

    $klager_html .= "    <td style='text-align: left'>";
    if (isset($klagerGjennomgatt[$klage])) {
        $klager_html .= 'Gjennomgått: ' . $klagerGjennomgatt[$klage] . '<br>';

    }
    elseif (isset($klagerFjernet[$klage])) {
        $klager_html .= 'Fjernet: ' . $klagerFjernet[$klage] . '<br>';
    }
    else {
        $klager_html .= '<input type="text" value="'.$klage . '"> ikke gjennomgått<br>';
    }
    $klager_html .= "</td>\n";
    $klager_html .= "</tr>\n";
}
$klager_html .= "</table>\n";
file_put_contents(__DIR__ . '/docs/klager/index.html', $klager_html);


$html .= "</ul>\n\n";

$html_d1_4 .= '</table>';
$html_d2_4 .= '</table>';


$summary_html = '';
foreach ($summaryData as $election => $num) {
    $summary_html .= '<li><b>' . $election . ':</b> ' . $num . " kommuner</li>\n";
}
$html = str_replace('-----SUMMARY-----HERE-----', $summary_html, $html);
$html = str_replace('----D1.4-TABLE---', $html_d1_4, $html);
$html = str_replace('----D2.4-TABLE---', $html_d2_4, $html);
file_put_contents(__DIR__ . '/docs/index.html', $html);

$html_BallotStuffing .= "</table>";
file_put_contents(__DIR__ . '/docs/ballot-stuffing.html', $html_BallotStuffing);


$html_entities = htmlHeading('Municipality overview - Valgprotokoller') . '
<style>
        .innsyn-success {
            color: #62b356;
        }

        .innsyn-waiting {
            color: #f4a03e;
        }

        .innsyn-failed {
            color: #e04b4b;
        }
</style>

<h1>Status overview of "valgprotokoll" per municipality</h1>
<i>Status per entity (municipality/county). Which files are we missing?</i>

<table>

';
foreach ($entity_id__to__obj as $entity) {

    $elections = array(
        '<td>-</td>',
        '<td>-</td>',
        '<td style="color: darkgreen">OK, all elections found and read.</td>',
        '<td>-</td>'
    );
    if (isset($entity->elections)) {
        foreach ($entity->elections as $election) {
            if ($election->election == 'Kommunestyrevalget 2019' && $elections[0] == '<td>-</td>') {
                $elections[0] = '<td><a href="' . getNewPath($election->file) . '">' . $election->election . '</a>'
                    . chr(10)
                    . ' [<a href="' . $election->url2 . '">PDF</a>]</td>';
            }
            elseif ($election->election == 'Fylkestingsvalget 2019' && $elections[1] == '<td>-</td>') {
                $elections[1] = '<td><a href="' . getNewPath($election->file) . '">' . $election->election . '</a>'
                    . chr(10)
                    . ' [<a href="' . $election->url2 . '">PDF</a>]</td>';
            }
            else {
                var_dump($election);
                throw new Exception('Unknown election: ' . $election->election);
            }
        }
    }

    $anyMissing = ($elections[0] == '<td>-</td>' || $elections[1] == '<td>-</td>');
    if ($anyMissing) {
        $elections[2] = '<td>Missing election(s).</td>';
    }
    if (isset($entity_merging[$entity->name])) {
        $new_name = is_array($entity_merging[$entity->name])
            ? implode(', ', $entity_merging[$entity->name])
            : $entity_merging[$entity->name];
        $elections[2] = '<td style="color: darkgreen">Merged other municipality. New is [' . $new_name . ']</td>';
        $anyMissing = false;
    }

    if (isset($entity->mimesBronnUrl)) {
        $tags = 'valgprotokoll_2019';
        $mimesLink =
            // http://alaveteli.org/docs/developers/api/#starting-new-requests-programmatically
            '<a target="_blank" href="https://www.mimesbronn.no/new/' . htmlentities($entity->mimesBronnUrl, ENT_QUOTES)
            . '?title=' . urlencode('Valgprotokoll 2019, ' . $entity->name)
            . '&tags=' . urlencode($tags)
            . '&body=' . urlencode(
                'Kjære ' . $entity->name . chr(10)
                . chr(10)
                . 'Jeg ønsker innsyn i valgprotokoll 2019 for fylkestingsvalget og kommunestyrevalget. Ofte kalt "Valgprotokoll for valgstyret"'
                . ' eller "Valgstyrets møtebok". Jeg ønsker at den er maskinlesbar, altså ikke innskannet (slik de signerte ofte er). '
                . 'Dette fordi vi skal lese ut data fra den.'
                . chr(10) . chr(10)
                . 'Søker altså innsyn i:' . chr(10)
                . '1. Valgprotokoll kommunestyrevalget 2019 - ' . $entity->name . chr(10)
                . '2. Valgprotokoll fylkestingsvalg 2019 - ' . $entity->name . chr(10)
                . chr(10) . chr(10)
                . 'Ønsker også svar på følgende:' . chr(10)
                . '1. Ble foreløpig opptelling utført manuelt eller med dataprogram?' . chr(10)
                . '2. Ble endelig opptelling utført manuelt eller med dataprogram?' . chr(10)
                . '3. Hvor mange opptellinger og omtellinger ble foretatt totalt?' . chr(10)
                . '4. Hvis flere enn foreløpig og endelig, hvordan ble disse utført (manuelt/data?) og hvorfor ble de utført?' . chr(10)
                . 'Dersom det er ulikt svar for de ulike valgskretsene, så svar for hver enkelt krets.' . chr(10) . chr(10)
                . 'Takk!'
                . chr(10) . chr(10)
            )
            . '">'
            . 'Søk innsyn i dokumentet via Mimes Brønn</a>' . chr(10);

        if (isset($mimesBronnStatus[$entity->mimesBronnUrl]) || $anyMissing) {
            $mimesLink = '<span style="font-size: 0.6em;">' . $mimesLink . "</span>\n";
        }
        if (isset($mimesBronnStatus[$entity->mimesBronnUrl])) {

            foreach ($mimesBronnStatus[$entity->mimesBronnUrl] as $mimesObj) {
                $mimesLink .= '<br><a href="' . $mimesObj->url . '">' . $mimesObj->display_status . "</a>\n";
                if (str_contains($mimesObj->display_status, 'Vellykket')
                    && $anyMissing) {
                    $mimesLink .= "<br><span style='color: red'>Success but not parsed.</span>\n";
                    $mimesLink .= '<pre style="display: block; max-width: 100px;">';
                    foreach ($mimesObj->files as $file) {
                        $mimesLink .= 'php 2-valgprotokoll-parser.php "' . $file->url . "\"\n";
                    }
                    $mimesLink .= '</pre>';
                }

                if (isset($mimesObj->answerToQuestions)) {
                    $mimesLink .= "</td>\n<td style='text-align: left'>" . nl2br(htmlentities($mimesObj->answerToQuestions, ENT_QUOTES));
                }
            }
        }
    }
    else {
        $mimesLink = 'Mangler Mimes Brønn-kobling.';
    }
    $elections[3] = '<td>' . $mimesLink . '</td>';

    $html_entities .= '

                    <tr>
        <th> ' . $entity->name . '</th>
                ' . implode("\n", $elections) . '
    </tr>
                ';
}

$html_entities .= '
</table>

                ';
file_put_contents(__DIR__ . '/docs/status-files.html', $html_entities);


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

