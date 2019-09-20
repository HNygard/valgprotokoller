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

$mimesBronnStatus = (array)json_decode(file_get_contents(__DIR__ . '/data-store/mimesbronn-result/result.json'));

$entity_merging = array(
    'Skedsmo kommune' => 'Lillestrøm kommune'
);


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
    $diffHtml = " (<span style='color: blue;'>$diff votes</span>)\n";
    if ($numbers->{'Foreløpig'} != 0) {
        $diff_percent = 100 * (($diff) / $numbers->{'Foreløpig'});
        $formattedNumber = number_format($diff_percent, 2);

        if (($diff_percent >= 1 || $diff_percent <= -1)) {
            return "\n<span style='color: red;'>" . $formattedNumber . ' %</span> '
                . $partyNameShorten($text)
                . $diffHtml;
        }
        if (abs($diff) >= 40) {
            return "\n<span style='color: orange;'>" . $formattedNumber . ' %</span> '
                . $partyNameShorten($text)
                . $diffHtml;
        }
        return '';
    }
    elseif ($numbers->{'Endelig'} != 0) {
        return "\n<span style='color: red;'>∞</span> " . $partyNameShorten($text) . $diffHtml;
    }
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
    <td' . (abs($diff) > 40 ? ' style="color: orange;"' : '') . '>' . $numbers->{'Avvik'} . '</td>
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
    return str_replace('.json', '.html', str_replace('data-store/json/', '', str_replace(__DIR__ . '/', '', $file)));
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

    if (isset($obj->url) && $obj->url != '<missing>') {
        $obj->url2 = $obj->url;
        $electionHtml .= 'Kilde: <a href="' . $obj->url . '">' . $obj->url . '</a>';
    }
    elseif (str_contains($obj->localSource, 'elections-no.github.io')) {
        // Example localSource
        // data-store/pdfs/elections-no.github.io-docs-2019-Agder-Bykle kommune, Agder fylke - kommunestyrevalget4222_2019-09-10.pdf.layout.txt
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
                'data-store/pdfs/elections-no.github.io-docs-2019-' . $county . '-',
                'https://github.com/elections-no/elections-no.github.io/blob/master/docs/2019/' . $county . '/',
                $link
            );
        }
        $link = str_replace('.layout.txt', '', $link);
        if (!str_starts_with($link, 'https://')) {
            throw new Exception('Unknown link: ' . $link);
        }
        $obj->url2 = $link;
        $electionHtml .= 'Kilde: <a href="' . $link . '">' . $link . '</a>';
    }
    else {
        throw new Exception('Unknown source: ' . $obj->localSource);
    }
    foreach ($obj->comments as $commentType => $comments) {
        $html .= "<li><b>$commentType: </b>" . implode("<br>", $comments) . "</li>\n";
    }
    $html .= "</ul></li>\n";

    // :: Individual election pages
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


    // :: D1.4 and D2.4 summary page
    $d1_4_numbers = $obj->numbers->{'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'};
    $html_d1_4 .= $d1_4_d2_4_row($d1_4_numbers,
        '<a href="' . $new_path . '">' . $obj->election . ' - ' . $obj->municipality . '</a>',
        '<td style="text-align: left;">' . str_replace("\n", ",\n", trim($partyLargeDiscrepancies_D1_4)) . '</td>');
    $d2_4_numbers = $obj->numbers->{'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'}->{'Totalt antall partifordelte stemmesedler'};
    $html_d2_4 .= $d1_4_d2_4_row($d2_4_numbers,
        '<a href="' . $new_path . '">' . $obj->election . ' - ' . $obj->municipality . '</a>',
        '<td style="text-align: left;">' . str_replace("\n", ",\n", trim($partyLargeDiscrepancies_D2_4)) . '</td>');

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

    $ballotStuffingRow = function ($text, $ballots) {
        $checksum = $ballots->{'Kryss i manntall'} - $ballots->{'Ant. sedler'};

        $color = (str_contains($text, 'Main-votes') || str_contains($text, 'Key figur'))
            ? 'red'
            : '';
        $extraText = $color == 'red' ? ' This should not happen.' : ' Pre-votes: OK.';

        return "<td>$text</td>
    <td>" . $ballots->{'Kryss i manntall'} . '</td>
    <td>' . $ballots->{'Ant. sedler'} . '</td>
    <td>' . $checksum . '</td>
    <td>' . ($checksum >= 0
                ? '<span style="color: darkgreen;">OK. ' . $checksum . ' voters crossed out in census, but didn\'t vote.</span>'
                : '<span style="color: ' . $color . ';">More votes (' . $ballots->{'Ant. sedler'} . ' ballots) then people who was'
                . ' crossed out in census (' . $ballots->{'Kryss i manntall'} . ').' . $extraText . '</span>') . '</td>
    ';
    };


    $total = new stdClass();
    $total->{'Kryss i manntall'} = $obj->keyfigures_totaltAntallKryssIManntallet;
    $total->{'Ant. sedler'} = $obj->keyfigures_totaltAntallGodkjenteStemmesedler;

    if ($obj->foretattForeløpigOpptellingHosValgstyret) {
        $d4_1_numbers = $obj->numbers
            ->{'C4.1 Antall valgtingsstemmesedler i urne'}
            ->{'Total antall valgtingstemmesedler i urne'};
        $ballotsMainFirstCount = $d4_1_numbers;
        $totalKryssIManntallMainVotes = $d4_1_numbers->{'Kryss i manntall'};
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
        " . $ballotStuffingRow('Key figures', $total) . "
</tr>
<tr>
    " . $ballotStuffingRow('B2.1.1 - Pre-votes - ordinary', $ballotsPreOrdinary) . '
</tr>
<tr>
    ' . $ballotStuffingRow('B2.2.1 - Pre-votes - late arrival', $ballotsPreOrdinary) . '
</tr>
<tr>
    ' . ($ballotsMainFirstCounting == null ? '<td>C2.1 - Main votes - prelim counting stemmestyret</td><td>-</td>' : $ballotStuffingRow('C2.1 - Main votes - prelim counting stemmestyret', $ballotsMainFirstCounting)) . '
</tr>
<tr>
    ' . $ballotStuffingRow('C4.2 - Main-votes - prelim counting valgstyret', $ballotsMainFirstCount) . '
</tr>
<tr>
    ' . $ballotStuffingRow('D2.1 - Main-votes - final counting', $ballotsMainFinalCount) . '
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
}

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
        $elections[2] = '<td style="color: darkgreen">Merged with [' . $entity_merging[$entity->name] . ']</td>';
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

