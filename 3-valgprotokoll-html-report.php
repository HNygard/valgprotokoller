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
    if(isset($entity->entityExistedToAndIncluding)) {
        continue;
    }
    $entity_id__to__obj[$entity->entityId] = $entity;
    $entity_name__to__entity_id[$entity->name] = $entity->entityId;
}
foreach ($entitiesArray2 as $entity) {
    $entity_id__to__obj[$entity->entityId] = $entity;
    $entity_name__to__entity_id[$entity->name] = $entity->entityId;
}
$allComments = array();

$entityFoiSent = explode("\n", file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-status-sent.txt'));
$entityFoiFinished = explode("\n", file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-status-finished.txt'));
$entitySuccess = explode("\n", file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-set-success-sent.txt'));
$entityOnlyOneOutgoing = explode("\n", file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-only-one-email-outgoing.txt'));
$entityFirstAction = explode("\n", file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-first-action.txt'));
$entityLastAction = explode("\n", file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-last-action.txt'));
$entityEmails = (array)json_decode(file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/entity-emails.json'));
$mxRecords = (array)json_decode(file_get_contents(__DIR__ . '/docs/data-store/json/mx-records-' . date('Y') .'.json'));

$klageJson = array();
$klageJson['kommune'] = json_decode(file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/klage-sendt-kommune.json'));
$klageJson['fylkeskommune'] = json_decode(file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/klage-sendt-fylkeskommune.json'));
$klageJson['stortinget'] = json_decode(file_get_contents(__DIR__ . '/docs/data-store/email-engine-result/klage-sendt-stortinget.json'));

$mimesBronnStatus = array();

$entity_merging = array(
    'Skedsmo kommune' => 'Lillestrøm kommune',
    'Spydeberg kommune' => 'Indre Østfold kommune',
    'Hobøl kommune' => 'Indre Østfold kommune',
    'Trøgstad kommune' => 'Indre Østfold kommune',
    'Askim kommune' => 'Indre Østfold kommune',
    'Eidsberg kommune' => 'Indre Østfold kommune',
    'Tysfjord kommune' => array('Nye Narvik', 'Nye Hamarøy'),
    'Snillfjord kommune' => array('Orkland kommune', 'Heim kommune', 'Hitra kommune'),
    'Ballangen kommune' => 'Narvik kommune',
    'Hemne kommune' => 'Heim kommune',
    'Rømskog kommune' => 'Aurskog-Høland kommune',
);

$innlandet = array(
    'entity_id' => 'innlandet-fylkeskommune',
    'name' => 'Innlandet fylkeskommune',
    'entity_email' => 'post@innlandetfylke.no',
);
$viken = array(
    'name' => 'Viken fylkeskommune',
    'entity_id' => 'viken-fylkeskommune',
    'entity_email' => 'post@viken.no'
);
$vestland = array(
    'name' => 'Vestland fylkeskommune',
    'entity_id' => 'vestland-fylkeskommune',
    'entity_email' => 'post@vlfk.no'
);
$entity_valgdistrikt = array(
    'Hedmark' => $innlandet,
    'Oppland' => $innlandet,
    'Akershus' => $viken,
    'Buskerud' => $viken,
    'Østfold' => $viken,
    'Sogn og Fjordane' => $vestland,
    'Hordaland' => $vestland
);

$klagerSendt = array(
    // Andre har klaget
    'Bergen - Fylkestingsvalget 2019.html' => 'Allerede klaget.',
    'Bergen - Kommunestyrevalget 2019.html' => 'Allerede påklaget.',
    'Evenes - Kommunestyrevalget 2019.html' => 'Allerede utvidet behandling og omtelling.',
    'Gjøvik - Kommunestyrevalget 2019.html' => 'Allerede påklaget - https://innsyn.gjovik.kommune.no/wfinnsyn.ashx?response=mote&moteid=423&',

    // Mine
    'Stavanger - Fylkestingsvalget 2019.html' => 'Avvik på mange prosenter for mange parti. Eneste kommentar "Feiltelling i foreløpig telling.". Ingen forklaring av kontrollmetode.',
    'Stavanger - Kommunestyrevalget 2019.html' => 'Avvik på mange prosenter for mange parti. Sparsommelige kommentarer. Flest av "Feiltelling i foreløpig telling". Ingen forklaring av kontrollmetode.',
    'Haugesund - Fylkestingsvalget 2019.html' => 'Relativt små avvik. Større avvik på FRP. Ellers lite reelle merknader.',
    'Bærum - Kommunestyrevalget 2019.html' => 'Store avvik på AP og FRP. Er kommentert som "Vi har skannet to ganger og er sikre på at feilen ligger i den manuelle tellingen.". Ingen forklaring utover det.',
    'Aurskog -Høland - Kommunestyrevalget 2019.html' => 'Rødt har fått mer stemmer i både forhånd og valgdag. Kommentarer: "Antatt feiltelling". Ingen forklaring av kontrollmetode.',
    'Fredrikstad - Kommunestyrevalget 2019.html' => 'Bør påklages. Mandatendring og større avvik på andre parti. Ikke forklart.',
    'Kongsberg - Fylkestingsvalget 2019.html' => 'Større stemmeavvik og prosent. Ikke forklart spesifikt.',

);

$county_sums = array();
$addCountySums = function ($county, $party, $numbers) {
    global $county_sums;
    if (!isset($county_sums[$county])) {
        $county_sums[$county] = array();
    }
    if (!isset($county_sums[$county][$party])) {
        $county_sums[$county][$party] = new stdClass();
        $county_sums[$county][$party]->{'Foreløpig'} = 0;
        $county_sums[$county][$party]->{'Endelig'} = 0;
    }
    $county_sums[$county][$party]->{'Foreløpig'} += $numbers->{'Foreløpig'};
    $county_sums[$county][$party]->{'Endelig'} += $numbers->{'Endelig'};
};

$profiles = json_decode(file_get_contents(__DIR__ . '/random-profiles-2021.json'));

// :: Sanity check - must be unique
$emails = array();
foreacH($profiles as $profile) {
    if (isset($emails[$profile->email])) {
        throw new Exception('lalalal');
    }
    $emails[$profile->email] = $profile;
}

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
$html .= "<a href='https://github.com/HNygard/valgprotokoller'>Source code for this report</a> (Github)\n";
$html .= "<a href='https://github.com/HNygard/valgprotokoller/tree/master/docs/data-store/json/Stortingsvalget%202021'>Source data for this report</a> (JSON at Github) -\n";
$html .= "<a href='https://github.com/HNygard/valgprotokoller/blob/master/docs/data-store/urls-election-2021.txt'>Source - URL list</a> (excludes those gotten from freedom of information requests)<br>\n";
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
//    'Fylkestingsvalget 2019' => 0,
//    'Kommunestyrevalget 2019' => 0,
    'Stortingsvalget 2021' => 0,
);

$d1_4_heading = '<table>
<tr>
<th>Political party</th>
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

$number_if_large_diff = function ($numbers, $text, $blue_limit = 40) {
    global $partyNameShorten;
    if (str_contains($text, 'Totalt antall partifordelte stemmesedler')) {
        return '';
    }

    $diff = $numbers->{'Endelig'} - $numbers->{'Foreløpig'};
    $diffSign = $diff > 0 ? '+' : '';
    $diffStyle = abs($diff) > $blue_limit ? " style='color: blue;'" : '';
    $diffHtml = " (<span$diffStyle>$diffSign$diff votes</span>)\n";
    if ($numbers->{'Foreløpig'} != 0) {
        $diff_percent = 100 * (($diff) / $numbers->{'Foreløpig'});
        $formattedNumber = number_format($diff_percent, 2);

        if (($diff_percent >= 1 || $diff_percent <= -1)) {
            return "\n<span style='color: red;'>" . $formattedNumber . ' %</span> '
                . $partyNameShorten($text)
                . $diffHtml;
        }
        if (abs($diff) >= $blue_limit) {
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

    if (str_contains($file, 'mx-records')) {
        continue;
    }
    if ($obj->error || $obj->documentType != 'valgprotokoll' || !isset($obj->election) || !isset($obj->municipality)) {
        continue;
    }
    if ($obj->election == 'Fylkestingsvalget 2019' || $obj->election == 'Kommunestyrevalget 2019') {
        continue;
    }
    logInfo('Using [' . str_replace(__DIR__ . '/', '', $file) . '].');


    $name = $obj->municipality;
    $name = str_replace('Aurskog -Høland', 'Aurskog-Høland', $name);
    $name = str_replace('Unjárga - Nesseby', 'Nesseby', $name);
    $name = str_replace('Porsanger - Porságu - Porsanki', 'Porsanger', $name);
    $name2 = $name . ' kommune';
    $name2 = str_replace('Ullensvang kommune', 'Ullensvang herad', $name2);
    $name2 = str_replace('Ulvik kommune', 'Ulvik herad', $name2);
    $name2 = str_replace('Snåase - Snåsa kommune', 'Snåsa kommune', $name2);
    if ($obj->county == 'Viken') {
        $name2 = str_replace('Våler kommune', 'Våler kommune (Østfold)', $name2);
    }
    if ($obj->county == 'Østfold') {
        $name2 = str_replace('Våler kommune', 'Våler kommune (Østfold)', $name2);
    }
    if ($obj->county == 'Møre og Romsdal') {
        $name2 = str_replace('Sande kommune', 'Sande kommune (Møre og Romsdal)', $name2);
    }
    if ($obj->county == 'Nordland') {
        $name2 = str_replace('Bø kommune', 'Bø kommune (Nordland)', $name2);
    }
    if ($obj->county == 'Nordland') {
        $name2 = str_replace('Herøy kommune', 'Herøy kommune (Nordland)', $name2);
    }
    if ($obj->county == 'Møre og Romsdal') {
        $name2 = str_replace('Herøy kommune', 'Herøy kommune (Møre og Romsdal)', $name2);
    }
    if ($obj->county == 'Hedmark') {
        $name2 = str_replace('Våler kommune', 'Våler kommune (Hedmark)', $name2);
    }
    $obj->file = $file;
    $entity_id__to__obj[$entity_name__to__entity_id[$name2]]->elections[] = $obj;
    $klager_html_navn = $name2 . ' - ' . $obj->election;
    $klager_html_navn = str_replace(' herad', '', $klager_html_navn);
    $klager_html_navn = str_replace(' kommune', '', $klager_html_navn);
    $klager_html_navn = str_replace('  ', '', $klager_html_navn);

    $summaryData[$obj->election]++;

    $new_path = getNewPath($file);
    $electionHtml = htmlHeading($klager_html_navn . ' - Valgprotokoll') . '
<a href="../../">Back to overview page</a>

<h1>' . $obj->election . ' - ' . $obj->municipality . "</h1>\n";

    $urlLocal = '../../data-store/pdfs-2021/' . str_replace('docs/data-store/pdfs-2021/', '', $obj->localSource);
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
        foreach ($comments as $comment123) {
            $allComments[] = $comment123;
        }
    }
    $html .= "</ul></li>\n";

    // :: Individual election pages
    $avvik_forelopig_endelig_comments = array();
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
        $addCountySums($obj->county, $party, $numbers);
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
        $addCountySums($obj->county, $party, $numbers);
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

        $color = (str_contains($text, 'Main-votes') || str_contains($text, 'Key figur')) && !str_contains($text, 'prelim')
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
    $total->{'Ant. sedler'} = $obj->keyfigures_totaltAntallGodkjenteStemmesedler - $obj->keyfigures_totaltAntallForkastedeStemmegivninger;


    /*
     *
    "keyfigures_totaltAntallGodkjenteForhåndsstemmegivninger": 1851,
    "keyfigures_totaltAntallGodkjenteValgtingsstemmegivninger": 3994,
    "keyfigures_totaltAntallForkastedeStemmegivninger": 0,
    "keyfigures_totaltAntallGodkjenteStemmesedler": 5808,
    "keyfigures_totaltAntallForkastedeStemmesedler": 12,
    "numbers": {
     */
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
    if (isset($obj->numbers->{'C4.7 Antall fremmede stemmesedler'})) {
        $totalKryssIManntallMainVotes += $obj->numbers->{'C4.7 Antall fremmede stemmesedler'}->{'Total antall fremmedstemmesedler'}->{'Kryss i manntall'};
        if ($ballotsMainFirstCount != null) {
            $ballotsMainFirstCount->{'Kryss i manntall'} += $obj->numbers->{'C4.7 Antall fremmede stemmesedler'}->{'Total antall fremmedstemmesedler'}->{'Kryss i manntall'};
        }
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
        $klageSummary = array();

        $entity = $entity_id__to__obj[$entity_name__to__entity_id[$name2]];
        if (isset($entity->mimesBronnUrl)) {
            $tags = 'valgprotokoll_2019 valgprotokoll_klage';
            $mimesLink =
                // http://alaveteli.org/docs/developers/api/#starting-new-requests-programmatically
                '<a target="_blank" href="https://www.mimesbronn.no/new/' . htmlentities($entity->mimesBronnUrl, ENT_QUOTES)
                . '?title=' . urlencode('Klage på valgprotokoll 2019, ' . $klager_html_navn)
                . '&tags=' . urlencode($tags)
                . '">'
                . 'Send klage via Mimes Brønn</a>' . chr(10);
        }
        else {
            $mimesLink = 'Mangler Mimes Brønn-kobling.';
        }
        $name = 'Twitteriatet for Valgkontroll (' . $entity->municipalityNumber . ')';
        $mimesLink =
            '<a target="_blank" href="http://localhost:25081/start-thread.php'
            . '?my_email=' . urlencode('valgklage_' . $entity->entityId . '@offpost.no')
            . '&my_name=' . urlencode($name)
            . '&title=' . urlencode('Klage på Stortingsvalget 2021, ' . $entity->name)
            . '&labels=' . urlencode('valgklage_2021 valgklage_2021_kommune')
            . '&entity_id=' . urlencode($entity->entityId)
            . '&entity_title_prefix=' . urlencode($entity->name)
            . '&entity_email=' . urlencode($entity->entityEmail)
            . '&body=KLAGE_BODY_INN_HER'
            . '">'
            . 'Klag via Email engine</a> [Kommune]' . chr(10);

        if (isset($entity_valgdistrikt[$obj->county])) {
            $mimesLink .=
                '<a target="_blank" href="http://localhost:25081/start-thread.php'
                . '?my_email=' . urlencode('valgklage_fk_' . $entity->entityId . '@offpost.no')
                . '&my_name=' . urlencode($name . ', fylkeskommune')
                . '&title=' . urlencode('Klage på Stortingsvalget 2021, ' . $entity->name)
                . '&labels=' . urlencode('valgklage_2021 valgklage_2021_fylkeskommune:' . $entity->entityId)
                . '&entity_id=' . urlencode($entity_valgdistrikt[$obj->county]['entity_id'])
                . '&entity_title_prefix=' . urlencode($entity_valgdistrikt[$obj->county]['name'])
                . '&entity_email=' . urlencode($entity_valgdistrikt[$obj->county]['entity_email'])
                . '&body=KLAGE_BODY_INN_HER'
                . '">'
                . 'Klag via Email engine</a> [Fylkeskommune]' . chr(10);
        }
        else {
            $mimesLink .= 'Klag via Email engine [Fylkeskommune] - ' . $obj->county . ' missing' . chr(10);
        }


        $mimesLink .=
            '<a target="_blank" href="http://localhost:25081/start-thread.php'
            . '?my_email=' . urlencode('valgklage_st_' . $entity->entityId . '@offpost.no')
            . '&my_name=' . urlencode($name . ', Stortinget')
            . '&title=' . urlencode('Klage på Stortingsvalget 2021, ' . $entity->name)
            . '&labels=' . urlencode('valgklage_2021 valgklage_2021_stortinget:' . $entity->entityId)
            . '&entity_id=stortinget'
            . '&entity_title_prefix=Stortinget'
            . '&entity_email=kontroll-konstitusjon@stortinget.no'
            . '&body=KLAGE_BODY_INN_HER'
            . '">'
            . 'Klag via Email engine</a> [Stortinget]' . chr(10);


        $klageStatus = '<table><tr>';
        $myndigheter = array('kommune' , 'fylkeskommune', 'stortinget');
        foreach ($myndigheter as $myndighet) {
            $status = array(
                '1 - Klage sent' => 'red',
                '2 - Mottak bekreftet' => 'black',
                '3.1 - Besvart med forklaring' => 'black',
                '3.2 - Besvart med behandling i valgstyret' => 'black',
                '3.3 - Avvist klage' => 'black',
                '4 - Klage på avslag' => 'black',
                '5 - Klagebehandling av avslag' => 'black',
            );

            if (isset($klageJson[$myndighet]->{$entity->entityId})) {
                $entityStatus = $klageJson[$myndighet]->{$entity->entityId};
                $status['1 - Klage sent'] = isset($entityStatus->klageSent) ? 'green' : 'red';
            }

            $klageStatus .= "\n<td style='text-align: left;'><b>$myndighet</b>";
            foreach ($status as $statusType => $statusColor) {
                $klageStatus .= "<li style='color: $statusColor'>" . ($statusColor == 'green' ? '✓' : '✗') . "$statusType</li>";
            }

            if ($status['1 - Klage sent'] == 'green') {
                $klageName = $klager_html_navn . '.html';
                $klagerSendt[$klageName] = (isset($klagerSendt[$klageName]) ? $klagerSendt[$klageName] . ', ' : '') . $myndighet;
            }

            $klageStatus .= "</td>\n\n";
        }
        $klageStatus .= "</tr></table>\n\n";

        $title = 'Klage på "Valgprotokoll for valgstyret - ' . $obj->election . '" for ' . $obj->municipality ;

        $klageStart =
            '<a href="../' . $new_path . '">' . $obj->election . ' - ' . $obj->municipality . '</a><br>' .
            $mimesLink . $klageStatus . '
(Dette eposten har feil emne. Korrekt tittel står under)        
        
TITTEL:
<input type="text" value="' . str_replace('"', '', $title) . '" style="width: 500px;">

---------

' ;
        $klage = $title . '

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
                $klageSummary[] = "$stuffingErrorType\n$stuffingError";
            }

            if (isset($ballotStuffingErrors['Totalt'])) {
                $klage .= '- ' . $ballotStuffingErrors['Totalt'] . "\n\n";
                $klageSummary[] = $ballotStuffingErrors['Totalt'];
            }

            $klage .= "Merknader:\n";
            if (count($ballotStuffingErrorsComments) == 0) {
                $klage .= "- Ingen merknader på dette.\n";
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
                $klage .= "I D1.4 kan man se på avvikene på forhåndsstemmer, avvikene var for noen partier overraskende store. Årsaken til dette og måten det er kontrollert på er ikke forklart.\n\n<b>";

                // - Norges Kommunistiske Parti mistet nærmest 86.7% av stemmene sine
                // - Folkeaksjonen Nei til mer bompenger økte med 5.7%
                foreach ($partyLargeDiscrepancies_D1_4_klage as $text) {
                    $klage .= "- $text\n";
                    $klageSummary[] = $text;
                }
                $klage .= "</b>\n";
            }
            if (count($partyLargeDiscrepancies_D2_4_klage) > 0) {
                $klage .= "I D2.4 kan man se på avvikene på valgdagsstemmene, avvikene var for noen partier er sjokkerende store. Årsaken til dette og måten det er kontrollert på  er ikke forklart.\n\n<b>";

                // - Norges Kommunistiske Parti mistet nærmest 86.7% av stemmene sine
                // - Folkeaksjonen Nei til mer bompenger økte med 5.7%
                foreach ($partyLargeDiscrepancies_D2_4_klage as $text) {
                    $klage .= "- $text\n";
                    $klageSummary[] = $text;
                }
                $klage .= "</b>\n";
            }

            if (count($partyLargeDiscrepancies_E1_1_klage) > 0) {
                $klageType .= ' + avvikMandat';
                $klage .= "I E1.1 kan man se mandatfordelingen i endelig opptelling. Dersom man beregner dette for foreløpig opptelling "
                    . "også, så kan man se at mandater har byttet parti. I og med at avvik i D1.4/D2.4 ikke er forklart, er heller ikke mandatendringen blitt forklart.\n\n<b>";

                // - Norges Kommunistiske Parti mistet nærmest 86.7% av stemmene sine
                // - Folkeaksjonen Nei til mer bompenger økte med 5.7%
                foreach ($partyLargeDiscrepancies_E1_1_klage as $text) {
                    $klage .= "- $text\n";
                    $klageSummary[] = $text;
                }
            }

            $klage .= '</b>De merknadene som er der tyder også på at man ikke så på avvikene fra manuell telling og/eller har ikke forklart hvilke kontrollmetoder som er utført for å påvise feil ved foreløpig opptelling. Eksempler:

';
            ksort($avvik_forelopig_endelig_comments);
            foreach ($avvik_forelopig_endelig_comments as $commentType => $comments) {
                foreach ($comments as $comment) {
                    $klage .= "<b>- $commentType:</b>\n$comment\n\n";
                }
            }
            $klage .= '
Det er tydelig at man ikke har sett på avvikene mellom foreløpig telling og endelig telling. Dette bryter mot hele grunnlaget for valgforskrifts-endringen i § 37a [3].

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


        $klage .= 'Basert på det over er ikke "Valgprotokoll for valgstyret - ' . $obj->election . '" [2] for ' . $obj->municipality . ' i henhold til Valgforskriften [1].

';
        if ($avvik_forelopig_endelig) {
            $klage .= 'Mange av avvikene i ' . $obj->municipality . ' er overraskende store. Ser man på valgdagsstemmene i Oslo [4] så er avvik på 0,02%, det at man i ' . $obj->municipality . ' sine valgprotokoller i tillegg ikke har forklart avvikene gjør at man ikke kan akseptere resultatene som er presentert.

';
        }

        $klage .= "Jeg ber om at saksnummer sendes i retur så fort dette dokumentet er journalført.\n\n";

        $klage .= "Jeg ber også om at eventuelle merknader fra behandling av valgprotokollen hos kommune/fylkeskommune/Stortinget oversendes snarest.\n\n";

        $klage .= '---
Mvh
Twitteriatet for Valgkontroll

Klage ført i penn av Hallvard Nygård [Twitter: @hallny]

';

        $klage .= '

[1]: https://lovdata.no/dokument/SF/forskrift/2003-01-02-5#KAPITTEL_9
[2]: ' . str_replace(__DIR__ . '/docs/', 'https://hnygard.github.io/valgprotokoller/', $new_file) . "\n"
. ($avvik_forelopig_endelig ? "[3]: https://www.regjeringen.no/no/dokumenter/veileder-om-manuell-forelopig-opptelling-etter-valgforskriften--37a/id2629412/\n" : '')
. ($avvik_forelopig_endelig ? '[4]: https://hnygard.github.io/valgprotokoller/Stortingsvalget%202021/Oslo/Oslo.html' : '') . '




';

        $klageStart = str_replace('KLAGE_BODY_INN_HER', urlencode(strip_tags($klage)), $klageStart);

        file_put_contents(__DIR__ . '/docs/klager/' . $klager_html_navn . '.html',
            htmlHeading('Klage - ' . $klager_html_navn)
            . '<style>body { white-space: pre-line; } </style>'
            . $klageStart . $klage);
        $klager[$klager_html_navn . '.html'] =
            array(
                $klageType,
                $klageSummary
            );
    }
}


$html .= "<h2>Fylkesoversikt</h2>\n";
$html .= "<table>\n";
foreach ($county_sums as $county => $county_sum) {
    $html .= "<tr>\n";
    $html .= "<td>$county</td>\n";
    $html .= "<td style='text-align: left;'>\n";
    $large_diffs = array();
    foreach ($county_sum as $party => $numbers) {
        $large_diffs[] = $number_if_large_diff($numbers, $party, 300);

    }
    $html .= implode($large_diffs, "\n");
    $html .= "</td>\n";
    $html .= "</tr>\n";
}
$html .= "</table>\n";


$klagerGjennomgatt_skalKlages = array(
    'Gjesdal - Kommunestyrevalget 2019.html' => 'Små avvik i stemmer og prosent, men har endret mandat.',

    'Steinkjer - Fylkestingsvalget 2019.html' => 'Avvik på mange prosenter for mange parti. Kommentarer av type "Stemmestyret har telt 5 sedler for lite". Ingen forklaring av kontrollmetode.',
    'Steinkjer - Kommunestyrevalget 2019.html' => 'Avvik på mange prosenter for mange parti. Kommentarer av type "Stemmestyret har telt 5 sedler for lite". Ingen forklaring av kontrollmetode.',
    'Ullensaker - Fylkestingsvalget 2019.html' => 'Avvik på mange prosenter for mange parti. Fleste kommentarer er "feil ved manuell telling". Ingen forklaring av kontrollmetode.',
    'Ullensaker - Kommunestyrevalget 2019.html' => '47 stemmer avvik på FRP. Kommentarer som "Feil ved manuell telling". Ingen forklaring av kontrollmetode.',

    'Hammerfest - Kommunestyrevalget 2019.html' => '!!!  Må sjekke avvik stemmesedler om det skal være med: !!! Stemmeavvik på AP. Avvik i antall stemmesedler vs manntall.'
        . ' Ikke forklart (?). Korrigert i endelig opptelling. Avvik AP ikke kommentert.',

    'Eidsvoll - Fylkestingsvalget 2019.html' => 'Bør påklages. Avviket i stemmer stort og ikke gjengitt på forklarlig måte.',
    'Eigersund - Fylkestingsvalget 2019.html' => 'Bør påklages. 50 ekstra stemmer. Stor prosent. Forklart som tellefeil og noe om bunker. Kontrollmetoder ikke forklart.',

    'Færder - Fylkestingsvalget 2019.html' => 'Kanskje. Mange forskjellig avvik. Inkludert 2 avvik som kansellerer hverandre.',
    'Færder - Kommunestyrevalget 2019.html' => 'Kanskje. Mange merknader og mange kansellerende avvik. Se også det andre valget i samme kommune.',
    'Gausdal - Kommunestyrevalget 2019.html' => 'Stort prosentavvik i liten kommune. Ingen merknad.',
    'Grimstad - Fylkestingsvalget 2019.html' => 'Kanskje. Kontrollmetoder ikke forklart. Avviket er forklart.',
    'Arendal - Fylkestingsvalget 2019.html' => 'Stort avvik på enkeltparti. Stort avvik på antall stemmer. Ingen informasjon om kontrolltiltak. Kun at maskinell er kjørt dobbelt.',
    'Arendal - Kommunestyrevalget 2019.html' => 'Stort avvik i stemmer og prosent. Ingen informasjon om kontrolltilak. Dobbelkjørt maskinell er eneste nevnte.',
    'Asker - Fylkestingsvalget 2019.html' => 'Stort avvik i stemmer og prosent. Ingen informasjon om kontrolltiltak.',
    'Bodø - Fylkestingsvalget 2019.html' => 'Forhåndsstemmer: OK. Valgting: Stort avvik i stemmer og prosent. Ingen informasjon om kontrolltiltak. Maskin kjørt to ganger.',
    'Bodø - Kommunestyrevalget 2019.html' => 'Stort avvik i stemmer og prosent. Ingen informasjon om kontrolltiltak. Maskin kjørt to ganger.',


);
$klagerFjernet = array(

    // Andre
    'Frogn - Fylkestingsvalget 2019.html' => 'Større avvik på Piratpartiet. Skannet to ganger. Sliter med avvik i sedler vs manntall pga to valg.',
    'Aukra - Fylkestingsvalget 2019.html' => 'Større avvik på Venstre. Kommentert.',
    'Tønsberg - Kommunestyrevalget 2019.html' => 'Kun -4 stemmer på Partiet De kristne. Ingen forklaring av avviket.',
    'Berlevåg - Fylkestingsvalget 2019.html' => 'KRF fikk +1 stemme. Utgjorde 50% ekstra.',
    'Ullensvang - Fylkestingsvalget 2019.html' => 'Lite avvik. Ca kommentert tror jeg.',
    'Fauske - Fylkestingsvalget 2019.html' => 'Avvik på 1 stemme på to små partier. Utkjør 11-12 % for disse partiene. Kommentar: "OK"',

    // Kanskje:
    'Haugesund - Kommunestyrevalget 2019.html' => 'Små avvik. Skanning.',


    'Eidskog - Kommunestyrevalget 2019.html' => '1 stemme avvik på lite parti. Klagegrunnlag for lite.',
    'Eigersund - Kommunestyrevalget 2019.html' => '1-2 stemmer avvik på små parti. Klagegrunnlag for lite.',
    'Flekkefjord - Fylkestingsvalget 2019.html' => '1 stemme avvik på lite parti. Klagegrunnlag for lite.',
    'Flekkefjord - Kommunestyrevalget 2019.html' => '1-2 stemmer avvik på små parti. Klagegrunnlag for lite.',
    'Fredrikstad - Fylkestingsvalget 2019.html' => '1-2 stemmer avvik på små parti. Klagegrunnlag for lite.',
    'Asker - Kommunestyrevalget 2019.html' => '1-4 stmemer avvik på små parti. Klagegrunnlag for lite.',

    // 1-4 stmemer avvik på små parti. Klagegrunnlag for lite.
    'Gjesdal - Fylkestingsvalget 2019.html' => '',
    'Gjøvik - Fylkestingsvalget 2019.html' => '',
    'Rakkestad - Kommunestyrevalget 2019.html' => '',
    'Randaberg - Fylkestingsvalget 2019.html' => '',
    'Randaberg - Kommunestyrevalget 2019.html' => '',
    'Ringsaker - Fylkestingsvalget 2019.html' => '',
    'Rælingen - Fylkestingsvalget 2019.html' => '',
    'Rælingen - Kommunestyrevalget 2019.html' => '',
    'Røst - Kommunestyrevalget 2019.html' => '',
    'Sirdal - Fylkestingsvalget 2019.html' => '',
    'Sirdal - Kommunestyrevalget 2019.html' => '',
    'Sola - Fylkestingsvalget 2019.html' => '',
    'Stange - Kommunestyrevalget 2019.html' => '',
    'Strand - Fylkestingsvalget 2019.html' => '',
    'Suldal - Fylkestingsvalget 2019.html' => '',
    'Søndre Land - Fylkestingsvalget 2019.html' => '',
    'Sør-Varanger - Kommunestyrevalget 2019.html' => '',
    'Tvedestrand - Fylkestingsvalget 2019.html' => '',
    'Tvedestrand - Kommunestyrevalget 2019.html' => '',
    'Tynset - Fylkestingsvalget 2019.html' => '',
    'Tynset - Kommunestyrevalget 2019.html' => '',
    'Vadsø - Kommunestyrevalget 2019.html' => '',
    'Vestnes - Fylkestingsvalget 2019.html' => '',
    'Vestre Toten - Kommunestyrevalget 2019.html' => '',
    'Volda - Fylkestingsvalget 2019.html' => '',
    'Volda - Kommunestyrevalget 2019.html' => '',
    'Ørland - Kommunestyrevalget 2019.html' => '',
    'Ørsta - Fylkestingsvalget 2019.html' => '',
    'Ørsta - Kommunestyrevalget 2019.html' => '',
    'Halden - Kommunestyrevalget 2019.html' => '',
    'Hamar - Fylkestingsvalget 2019.html' => '',
    'Hamar - Kommunestyrevalget 2019.html' => '',
    'Hamarøy - Fylkestingsvalget 2019.html' => '',
    'Hitra - Fylkestingsvalget 2019.html' => '',
    'Hitra - Kommunestyrevalget 2019.html ' => '',
    'Hjelmeland - Fylkestingsvalget 2019.html' => '',
    'Hol - Kommunestyrevalget 2019.html' => '',
    'Holmestrand - Fylkestingsvalget 2019.html' => '',
    'Holmestrand - Kommunestyrevalget 2019.html' => 'Faktisk kommentar.',
    'Hurdal - Fylkestingsvalget 2019.html' => '',
    'Hurdal - Kommunestyrevalget 2019.html' => '',
    'Hå - Fylkestingsvalget 2019.html' => '',
    'Indre Østfold - Kommunestyrevalget 2019.html' => '',
    'Kongsberg - Kommunestyrevalget 2019.html' => '',
    'Kristiansund - Fylkestingsvalget 2019.html' => 'Kommentert. Virker som de har skannet og telt manuelt to ganger.',
    'Kristiansund - Kommunestyrevalget 2019.html' => 'Kommentert. Også skrevet stikkprøvekontroll. Bra!',
    'Kvæfjord - Kommunestyrevalget 2019.html' => '',
    'Marker - Fylkestingsvalget 2019.html' => '',
    'Molde - Fylkestingsvalget 2019.html' => '',
    'Moss - Kommunestyrevalget 2019.html' => '',
    'Oppdal - Fylkestingsvalget 2019.html' => '',
    'Oppdal - Kommunestyrevalget 2019.html' => '',
    'Orkland - Fylkestingsvalget 2019.html' => 'Kommentert. OK.',
    'Orkland - Kommunestyrevalget 2019.html' => '',
    'Porsanger - Porságu - Porsanki - Kommunestyrevalget 2019.html' => '',
    'Sandefjord - Kommunestyrevalget 2019.html' => 'Kommentert og forklart. Noen stemmer tatt med som ikke skulle med.',
    'Sarpsborg - Fylkestingsvalget 2019.html' => 'Godt forklart i forbindelse med innsynshenvendelsen.',
    'Sandnes - Kommunestyrevalget 2019.html' => 'Forsåvidt forklart. Ikke den beste, men la gå.',
    'Sandnes - Fylkestingsvalget 2019.html' => 'Flere stemmer på endelig. Fordelt utover flere partier.',
    'Nord-Aurdal - Kommunestyrevalget 2019.html' => 'Kun ballot stuffing i foreløpig opptelling.',
    'Strand - Kommunestyrevalget 2019.html' => 'Kun ballot stuffing i foreløpig opptelling.',
    'Nesodden - Kommunestyrevalget 2019.html' => '',

    'Alstahaug - Stortingsvalget 2021.html' => '',
    'Arendal - Stortingsvalget 2021.html' => '',
    'Asker - Stortingsvalget 2021.html' => '',
    'Evenes - Stortingsvalget 2021.html' => 'Forklart ok. 3 stk beredskapsstemmer tatt med.',
    'Høyanger - Stortingsvalget 2021.html' => '',
    'Jevnaker - Stortingsvalget 2021.html' => '',
    'Kongsvinger - Stortingsvalget 2021.html' => '',
    'Flesberg - Stortingsvalget 2021.html' => '',
    'Fredrikstad - Stortingsvalget 2021.html' => '',
    'Færder - Stortingsvalget 2021.html' => '',
    'Gausdal - Stortingsvalget 2021.html' => '',
    'Gjesdal - Stortingsvalget 2021.html' => '',
    'Kvinesdal - Stortingsvalget 2021.html' => '',
    'Malvik - Stortingsvalget 2021.html' => '',
    'Molde - Stortingsvalget 2021.html' => '',
    'Osterøy - Stortingsvalget 2021.html' => '',
    'Sandefjord - Stortingsvalget 2021.html' => '',
    'Sarpsborg - Stortingsvalget 2021.html' => '',
    'Senja - Stortingsvalget 2021.html' => '',
    'Sola - Stortingsvalget 2021.html' => '',
    'Stavanger - Stortingsvalget 2021.html' => '',
    'Steinkjer - Stortingsvalget 2021.html' => '',
    'Stord - Stortingsvalget 2021.html' => '',
    'Time - Stortingsvalget 2021.html' => '',
    'Tønsberg - Stortingsvalget 2021.html' => '',
    'Vang - Stortingsvalget 2021.html' => '',
    'Vestre Toten - Stortingsvalget 2021.html' => '',
    'Øygarden - Stortingsvalget 2021.html' => '',
    'Bamble - Stortingsvalget 2021.html' => '',
    'Rendalen - Stortingsvalget 2021.html' => 'særskilt omslag lagt til i valgtingstemmer for å bevare anonymitet',
    'Drammen - Stortingsvalget 2021.html' => '',
    'Eigersund - Stortingsvalget 2021.html' => '',
    'Årdal - Stortingsvalget 2021.html' => '',
    'Rælingen - Stortingsvalget 2021.html' => '',
    'Åmot - Stortingsvalget 2021.html' => '',
    'Ås - Stortingsvalget 2021.html' => '',
    'Lurøy - Stortingsvalget 2021.html' => '',
    'Lillestrøm - Stortingsvalget 2021.html' => '',
    'Hammerfest - Stortingsvalget 2021.html' => '',
    'Hjartdal - Stortingsvalget 2021.html' => '',
    'Hamar - Stortingsvalget 2021.html' => '',
    'Ringerike - Stortingsvalget 2021.html' => '',
    'Sigdal - Stortingsvalget 2021.html' => '',
    'Sør-Odal - Stortingsvalget 2021.html' => '',
    'Sør-Fron - Stortingsvalget 2021.html' => '',
    'Tysvær - Stortingsvalget 2021.html' => '',
    'Volda - Stortingsvalget 2021.html' => '',
    'Grane - Stortingsvalget 2021.html' => '',
    'Giske - Stortingsvalget 2021.html' => '',
    'Kinn - Stortingsvalget 2021.html' => '',
    'Hitra - Stortingsvalget 2021.html' => '',
    'Nord-Fron - Stortingsvalget 2021.html' => '',
    'Ringsaker - Stortingsvalget 2021.html' => '',
    'Midtre Gauldal - Stortingsvalget 2021.html' => '',
    'Hareid - Stortingsvalget 2021.html' => 'Er forklart.',
    'Hå - Stortingsvalget 2021.html' => '',
    'Tolga - Stortingsvalget 2021.html' => '"Differanse på 45 stemmer er sent innkomne forhåndsstemmer som er registrert og prøvd."',
    'Fjord - Stortingsvalget 2021.html' => 'særskild omslag og beredskapskonvolutt sammen med valgting',
    'Alta - Stortingsvalget 2021.html' => '',
    '' => '',
    '' => '',
    '' => '',
    '' => '',
    '' => '',
    '' => '',


    // Fremmed stemmesedler ikke tatt med
    'Nordreisa - Kommunestyrevalget 2019.html' => 'Ikke diff hvis fremmede stemmesedler er tatt med.',
);

$klager_html = htmlHeading('Klager');
ksort($klager);
$klager_html .= "<style>.fjernet, .fjernet a { color: lightgrey; }</style>\n\n";
$klager_html .= "<table>\n";
foreach ($klager as $klage => $klageArray) {
    $klageType = $klageArray[0];
    $klageSummary = $klageArray[1];
    $klager_html .= "<tr>\n    <td style='text-align: left'>";
    if (isset($klagerGjennomgatt_skalKlages[$klage])) {
        $klager_html .= "<span style='text-decoration: line-through; color: red'>";
    }
    if (isset($klagerFjernet[$klage])) {
        $klager_html .= "<span style='text-decoration: line-through' class='fjernet'>";
    }
    $klager_html .= '<a href="./' . $klage . '">' . $klage . '</a> - ' . $klageType . "<br>";
    if (isset($klagerFjernet[$klage]) || isset($klagerGjennomgatt_skalKlages[$klage])) {
        $klager_html .= "</span>";
    }
    $klager_html .= "</td>\n";

    $klager_html .= "    <td style='text-align: left'>";
    if (isset($klagerGjennomgatt_skalKlages[$klage])) {
        $klager_html .= 'Gjennomgått, skal klages: ' . $klagerGjennomgatt_skalKlages[$klage] . '<br>';

    }
    elseif (isset($klagerSendt[$klage])) {
        $klager_html .= '<span style="color: green";>✓ Klage sendt: ' . $klagerSendt[$klage] . '</span><br>';
    }
    elseif (isset($klagerFjernet[$klage])) {
        $klager_html .= 'Gjennomgått, fjernet: ' . $klagerFjernet[$klage] . '<br>';
    }
    else {
        $klager_html .= '<input type="text" value="' . $klage . '"> ikke gjennomgått<br>';
        $klager_html .= "<span style='font-size: 10px; text-align: left'>";
        foreach($klageSummary as $text) {
            $klager_html .= "<li>$text</li>\n";
        }
        $klager_html .= "</span>\n";
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
$foiStatusPerEmailServerType = array();
$i = 0;
foreach ($entity_id__to__obj as $entity) {
    $i++;

    $elections = array(
        '<td>-</td>',
        '<td>-</td>',
        '<td style="color: darkgreen">OK, all elections found and read.</td>',
        '<td>-</td>'
    );
    if (isset($entity->elections)) {
        foreach ($entity->elections as $election) {
            /*
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
            */
            if ($election->election == 'Sametingsvalget 2021' && $elections[0] == '<td>-</td>') {
                $elections[0] = '<td><a href="' . getNewPath($election->file) . '">' . $election->election . '</a>'
                    . chr(10)
                    . ' [<a href="' . $election->url2 . '">PDF</a>]</td>';
            }
            elseif ($election->election == 'Stortingsvalget 2021' && $elections[1] == '<td>-</td>') {
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

    //$anyMissing = ($elections[0] == '<td>-</td>' || $elections[1] == '<td>-</td>');
    $anyMissing = ($elections[1] == '<td>-</td>');
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

    $nameColor = 'black';
    if (isset($entity->entityEmail)) {
        $tags = 'valgprotokoll_2021';
        $email = 'valgprotokoll_2021_' . $entity->entityId . '@offpost.no';
        $name = 'Prosjekt Åpne Valgdata (' . $entity->municipalityNumber . ')';
        $mimesLink =
            // http://alaveteli.org/docs/developers/api/#starting-new-requests-programmatically
            '<a target="_blank" href="http://localhost:25081/start-thread.php'
            . '?my_email=' . urlencode($email)
            . '&my_name=' . urlencode($name)
            . '&title=' . urlencode('Valgprotokoll 2021, ' . $entity->name)
            . '&labels=' . urlencode($tags)
            . '&entity_id=' . urlencode($entity->entityId)
            . '&entity_title_prefix=' . urlencode($entity->name)
            . '&entity_email=' . urlencode($entity->entityEmail)
            . '&body=' . urlencode(
                'Kjære ' . $entity->name . chr(10)
                . chr(10)
                . 'Jeg ønsker innsyn i valgprotokoll for stortingsvalget 2021, og eventuell sametingsvalget 2021. Ofte kalt "Valgprotokoll for valgstyret"'
                . ' eller "Valgstyrets møtebok". Jeg ønsker at den er maskinlesbar, altså ikke innskannet (slik de signerte ofte er). '
                . 'Dette fordi vi skal lese ut data fra den.'
                . chr(10) . chr(10)
                . 'Søker altså innsyn i:' . chr(10)
                . '1. Valgprotokoll Stortingsvalget 2021 - ' . $entity->name . chr(10)
                . '2. Hvis dere har: Valgprotokoll Sametingsvalget 2021 - ' . $entity->name . chr(10)
                . chr(10) . chr(10)
//                . 'Ønsker også svar på følgende:' . chr(10)
//                . '1. Ble foreløpig opptelling utført manuelt eller med dataprogram?' . chr(10)
//                . '2. Ble endelig opptelling utført manuelt eller med dataprogram?' . chr(10)
//                . '3. Hvor mange opptellinger og omtellinger ble foretatt totalt?' . chr(10)
//                . '4. Hvis flere enn foreløpig og endelig, hvordan ble disse utført (manuelt/data?) og hvorfor ble de utført?' . chr(10)
//                . 'Dersom det er ulikt svar for de ulike valgskretsene, så svar for hver enkelt krets.' . chr(10) . chr(10)
                . 'Takk!'
                . chr(10) . chr(10)
                . 'Prosjekt Åpne Valgdata - https://hnygard.github.io/valgprotokoller/'
            )
            . '">'
            . 'Søk innsyn via Email engine</a>' . chr(10);

        if (in_array($entity->entityId, $entityFoiFinished)) {
            $mimesLink = "<span style=\"font-size: 0.6em;\">FOI request finished</span>\n";
        }
        else if (in_array($entity->entityId, $entityFoiSent)) {
            $mimesLink = "<span style=\"font-size: 0.6em;\">FOI request sent</span>\n";
            foreach ($entitySuccess as $successUrl) {
                if (str_starts_with($successUrl, $entity->entityId)) {
                    $text = $anyMissing ? 'Missing, but set success anyway' : 'Election ok, set success';
                    $mimesLink .= '<br><a href="'.explode(':', $successUrl, 2)[1]."\">$text</a>\n";
                }
            }

            if (in_array($entity->entityId, $entityOnlyOneOutgoing)) {
                $mimesLink .= "<span style=\"font-size: 0.6em;\">Only one outgoing</span>\n";
            }
            foreach ($entityLastAction as $lastAction) {
                if (str_starts_with($lastAction, $entity->entityId)) {
                    $time = explode(':', $lastAction, 2)[1];
                    $daysSince = round((time() - $time) / 86400);
                    if ($daysSince < 7) {
                        continue;
                    }
                    $mimesLink .= "<span style=\"font-size: 0.6em;\">"
                        . "Last action: " . date('Y-m-d', $time) . " "
                        . "Days: $daysSince "
                        . ($daysSince >= 7 ? ' - 7 days limit' : ' - under 7 days')
                        . "</span>\n";
                    if ($daysSince >= 7) {
                        $nameColor = 'red';
                    }
                }
            }

            $initialSendDate = 'N/A';
            foreach ($entityFirstAction as $lastAction) {
                if (str_starts_with($lastAction, $entity->entityId)) {
                    $time = explode(':', $lastAction, 2)[1];
                    $daysSince = round((time() - $time) / 86400);
                    $initialSendDate = date('d.m.Y', $time) . ' (' . $daysSince . ' dager siden, sendt til "' .
                       explode('@', $entity->entityEmail)[0].'")';
                }
            }

            $entityEmail = (isset($entityEmails[$entity->entityId]) ? $entityEmails[$entity->entityId] : array());

            $randomProfile = $profiles[$i];
            $email = $randomProfile->email;
            $name = $randomProfile->firstName . $randomProfile->middleName . ' ' . $randomProfile->lastName;
            $mimesLink .=
                '<br><a target="_blank" href="http://localhost:25081/start-thread.php'
                . '?my_email=' . urlencode($email)
                . '&my_name=' . urlencode($name)
                . '&title=' . urlencode('Innsynshenvendelse - Valgprotokoll 2021, ' . $entity->name . ' - klage på manglende svar')
                . '&labels=' . urlencode($tags)
                . '&entity_id=' . urlencode($entity->entityId)
                . '&entity_title_prefix=' . urlencode($entity->name)
                . '&entity_email=' . urlencode($entity->entityEmail)
                . '&body=' . urlencode(
                    'Klage til ' . $entity->name . chr(10)
                    . chr(10)
                    . 'Dere har ikke svart på vår epost av ' . $initialSendDate .'. '
                    . 'Dette kan skyldes tekniske årsaker hos dere og vi forsøker å sende fra en ny epsotadresse. '
                    . 'Dere bør sjekke deres epostsystem (f.eks. spammappe eller Microsoft Impersonation Insight). '
                    . 'At dere ikke svarer er brudd på Offentleglova og vil bli klaget inn til Statsforvalteren.'
                    . chr(10). chr(10)
                    . 'Oppsummering av epostkorrespondanse:' . chr(10)
                    . implode("\n", $entityEmail)

                    . chr(10). chr(10)
                    . 'Fra original henvendelse:' . chr(10)
                    . 'Jeg ønsker innsyn i valgprotokoll for stortingsvalget 2021, og eventuell sametingsvalget 2021. Ofte kalt "Valgprotokoll for valgstyret"'
                    . ' eller "Valgstyrets møtebok". Jeg ønsker at den er maskinlesbar, altså ikke innskannet (slik de signerte ofte er). '
                    . 'Dette fordi vi skal lese ut data fra den.'
                    . chr(10) . chr(10)
                    . 'Søker altså innsyn i:' . chr(10)
                    . '1. Valgprotokoll Stortingsvalget 2021 - ' . $entity->name . chr(10)
                    . '2. Hvis dere har: Valgprotokoll Sametingsvalget 2021 - ' . $entity->name . chr(10)
                    . chr(10) . chr(10)
                    . 'Ønsker svar på original henvendelse (over) samt en forklaring på hvorfor dere avviser krav om innsyn ved ikke å svare.'
                    . chr(10) . chr(10)
                    . 'Takk!'
                    . chr(10) . chr(10)
                    . 'Prosjekt Åpne Valgdata'
                )
                . '">'
                . 'Klage random profile via Email engine</a>' . chr(10);
        }
        elseif(!$anyMissing) {
            // -> Finished else where
            $mimesLink = '-';
        }
    }
    else {
        $mimesLink = 'Mangler epost for innsyn.';
    }
    $elections[3] = '<td>' . $mimesLink . '</td>';

    if(!isset($mxRecords[$entity->entityId])) {
        $output = '';
        exec('dig ' . explode('@', $entity->entityEmail)[1] . ' MX +short', $output);
        $mxRecords[$entity->entityId] = $output;
        file_put_contents(__DIR__ . '/docs/data-store/json/mx-records-' . date('Y') . '.json', json_encode($mxRecords, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));
    }
    else {
        // Read from mx-records.php output
        $emailServerCombo = implode(', ', array_keys((array)$mxRecords[$entity->entityId]->emailServer));
        if (!isset($foiStatusPerEmailServerType[$emailServerCombo])) {
            $foiStatusPerEmailServerType[$emailServerCombo] = new stdClass();
            $foiStatusPerEmailServerType[$emailServerCombo]->total = 0;
            $foiStatusPerEmailServerType[$emailServerCombo]->foiFinished = 0;
            $foiStatusPerEmailServerType[$emailServerCombo]->foiWaiting = 0;
        }
        $foiStatusPerEmailServerType[$emailServerCombo]->total++;

        if (str_contains($mimesLink, 'FOI request finished')) {
            $foiStatusPerEmailServerType[$emailServerCombo]->foiFinished++;
        }
        if (str_contains($mimesLink, 'FOI request sent')) {
            $foiStatusPerEmailServerType[$emailServerCombo]->foiWaiting++;
        }
    }

    $html_entities .= '

                    <tr>
        <th style="color: ' . $nameColor . '"> ' . $entity->name . '</th>
                ' . implode("\n", $elections) . '
    </tr>
                ';
}

$html_entities .= '
</table>
<br><br><br>
                ';


ksort($foiStatusPerEmailServerType);
$html_entities .= "\n\n<table>\n<tr><th>Email server combo</th>\n<td>Total</td>\n<td>FOI waiting</td>\n<td>FOI finished</td>\n<td>Without FOI request</td>\n</tr>";
$tableSummary = new stdClass();
$tableSummary->total = 0;
$tableSummary->foiFinished = 0;
$tableSummary->foiWaiting = 0;
foreach($foiStatusPerEmailServerType as $emailServerType => $mailServerStatus) {
    $tableSummary->total += $mailServerStatus->total;
    $tableSummary->foiFinished += $mailServerStatus->foiFinished;
    $tableSummary->foiWaiting += $mailServerStatus->foiWaiting;
    $html_entities .= "\n<tr><td>$emailServerType</td>\n";
    $html_entities .= "<td>" . $mailServerStatus->total . "</td>\n";
    if ($mailServerStatus->foiWaiting == 0) {
        $html_entities .= "<td>zero</td>\n";
    }
    else {
        $html_entities .= "<td " . ($mailServerStatus->foiWaiting > 10 ? ' style="color: red"': '') . ">" . $mailServerStatus->foiWaiting . ' (' . round(100 * $mailServerStatus->foiWaiting / $mailServerStatus->total) . " %)</td>\n";
    }
    $html_entities .= "<td>" . $mailServerStatus->foiFinished . ' (' . round(100 * $mailServerStatus->foiFinished / $mailServerStatus->total) . " %)</td>\n";

    $nonFoi = $mailServerStatus->total - $mailServerStatus->foiFinished - $mailServerStatus->foiWaiting;
    $html_entities .= "<td>" . $nonFoi . ' (' . round(100 * $nonFoi / $mailServerStatus->total) . " %)</td>\n";
    $html_entities .= "</tr>\n";
}
$html_entities .= "\n<tr><td>TOTAL</td>\n";
$html_entities .= "<td>" . $tableSummary->total . "</td>\n";
$html_entities .= "<td>" . $tableSummary->foiWaiting . ' (' . round(100 * $tableSummary->foiWaiting / $tableSummary->total) . " %)</td>\n";
$html_entities .= "<td>" . $tableSummary->foiFinished . ' (' . round(100 * $tableSummary->foiFinished / $tableSummary->total) . " %)</td>\n";
$nonFoi = $tableSummary->total - $tableSummary->foiFinished - $tableSummary->foiWaiting;
$html_entities .= "<td>" . $nonFoi . ' (' . round(100 * $nonFoi / $tableSummary->total) . " %)</td>\n";
$html_entities .= "</tr>\n";
$html_entities .= '</table>';

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

function sortsortsort($a, $b) {
    return strlen($a) - strlen($b);
}

usort($allComments, 'sortsortsort');

foreach ($allComments as $comment) {
//    echo strlen($comment) . '   ----   ' . $comment . chr(10) . chr(10);
}

$allComments2 = array();
$allComments3 = array();
foreach ($allComments as $comment) {
    $comments = explode(':' . chr(10), $comment);
    foreach ($comments as $comment2) {
        $comment2 = trim($comment2);
        $allComments2[] = $comment2;
    }
    if (!isset($allComments3[$comment2])) {
        $allComments3[$comment2] = 0;
    }
    $allComments3[$comment2]++;
}
usort($allComments2, 'sortsortsort');

arsort($allComments3);

foreach ($allComments2 as $comment) {
//    echo strlen($comment) . '   ----   ' . $comment . chr(10) . chr(10);
}

//var_dump($allComments3);

foreach ($allComments3 as $comment => $count) {
    if ($count <= 5) {
        continue;
    }
//    echo str_pad($count, 3, " ", STR_PAD_LEFT) . 'x  "' . $comment . '"' . chr(10);
}