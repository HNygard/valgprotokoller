<?php
/**
 * :: File cleaner
 *
 * This script will remove all numbers and other things that might vary. The output file
 * should only contain words in nynorsk and bokmål. The goal is to make an automatic list
 * of translations.
 *
 * Manual:
 * 1. 'clean3-no-empty' was pulled into LibreOffice Calc and manually aligned.
 * 2. Output in CSV file.
 * 3. Diff:
 *     output-bokmål-clean3-no-empty-lines
 *     output-nynorsk-clean4-translated
 *
 * @author Hallvard Nygård, @hallny
 */


$bokmaal = readFile_andClean(
    __DIR__ . '/input-bokmål - elections-no.github.io-docs-2019-Innlandet-Tynset kommune, Innlandet fylke - kommunestyrevalget.pdf.layout.txt'
);
file_put_contents(__DIR__ . '/output-bokmål-clean1', $bokmaal);
$bokmaal = removeData($bokmaal);
file_put_contents(__DIR__ . '/output-bokmål-clean2-no-data', $bokmaal);
$bokmaal = clean2($bokmaal);
file_put_contents(__DIR__ . '/output-bokmål-clean3-no-empty-lines', $bokmaal);

$nynorsk = readFile_andClean(
    __DIR__ . '/input-nynorsk - elections-no.github.io-docs-2019-More_og_Romsdal-Hareid_kommune_kommunestyrevalet.pdf.layout.txt'
);
file_put_contents(__DIR__ . '/output-nynorsk-clean1', $nynorsk);
$nynorsk = removeData($nynorsk);
file_put_contents(__DIR__ . '/output-nynorsk-clean2-no-data', $nynorsk);
$nynorsk = clean2($nynorsk);
file_put_contents(__DIR__ . '/output-nynorsk-clean3-no-empty-lines', $nynorsk);

$lines = file(__DIR__ . '/nynorsk-til-bokmål.csv');
foreach($lines as $line) {
    if (empty(trim($line))) {
        continue;
    }
    $line = trim($line);
    $nynorskString = explode("\t", $line)[0];
    $bokmaalString = explode("\t", $line)[1];

    $nynorsk = str_replace($nynorskString, $bokmaalString, $nynorsk);
}
file_put_contents(__DIR__ . '/output-nynorsk-clean4-translated', $nynorsk);


function readFile_andClean($file) {
    $file_content = file_get_contents($file);

    $nynorsk = str_contains($file_content, 'Valprotokoll for valstyret');

    // :: Strip footer
    $regex_footer = '/^([0-9]*\.[0-9]*\.[0-9]* [0-9]*:[0-9]*:[0-9]*) \s* ' . (
        $nynorsk
            ? 'Valprotokoll for valstyret'
            : 'Valgprotokoll for valgstyret'
        ) . ' \s* Side [0-9]*$/';
    $match = regexAssertAndReturnMatch($regex_footer . 'm', $file_content);
    var_dump($match);
    $file_content = preg_replace($regex_footer . 'm', '', $file_content);

    // :: Strip new page
    $file_content = str_replace(chr(12), '', $file_content);

    // :: Strip multiple empty lines. Remenants of footer.
    $file_content = preg_replace('/\n\n\n/', "\n\n", $file_content);
    $file_content = preg_replace('/\n\n\n/', "\n\n", $file_content);
    $file_content = preg_replace('/\n\n\n/', "\n\n", $file_content);

    // :: Strip end of line numbers
    $file_content = preg_replace('/\s\s\s*([0-9 ,%—]*)$/m', '', $file_content);
    $file_content = trimAllLines($file_content);

    // :: Remove Sekretør - contains data
    $file_content = preg_replace('/Sekretær:\s\s\s*(.*)/', '', $file_content);

    // :: Switch from random number of spaces to 3
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    $file_content = preg_replace('/    /', "   ", $file_content);
    return $file_content;

}

function removeData($file_content) {
    $nynorsk = str_contains($file_content, 'Valprotokoll for valstyret');

    // :: Remove lists of data
    if ($nynorsk) {
        $file_content = removeBetween($file_content,
            "Parti   Tal\n",
            'Sum');
        $file_content = removeBetween($file_content,
            "Namn   Funksjon\n",
            'A2 Valtinget');
        $file_content = removeBetween($file_content,
            "Krins   Opningstid søndag   Opningstid mandag\n",
            'B Førebels oppteljing av førehandsstemmer');
        $file_content = removeBetween($file_content,
            "B2.1.2 Partifordelte førehandsstemmesetlar\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom kryss i manntal (B2.1.1) og førebels oppteljing (B2.1.2) og evt. andre tilhøve)\n",
            'B2.2 Talt opp etter kl. 17.00 dagen etter valdagen (lagt til side og seint innkomne)');
        $file_content = removeBetween($file_content,
            "B2.2.2 Partifordelte seint innkomande førehandsstemmesetlar\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "Søndag   Mandag   urne   omslag   omslag\n",
            'Sum');
        $file_content = removeBetween($file_content,
            "C2.2 Partifordelte valtingsstemmesetlar\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom kryss i manntal (C2.1) og førebels oppteljing og evt. andre tilhøve)\n",
            'C3 Stemmegjevingar - valting');
        $file_content = removeBetween($file_content,
            "C4.5 Partifordelte stemmesetlar i særskild omslag\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom kryss i manntal (C4.4) og førebels oppteljing (C4.5) og evt. andre tilhøve)\n",
            'C4.7 Tal stemmesetlar i beredskapskonvolutt');
        $file_content = removeBetween($file_content,
            "C4.8 Partifordelte stemmesetlar i beredskapskonvolutt\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom kryss i manntal (C4.7) og førebels oppteljing (C4.8) og evt. andre tilhøve)\n",
            'D Endeleg oppteljing');
        $file_content = removeBetween($file_content,
            "D1.3 Godkjende førehandsstemmesetlar fordelt på parti\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "D1.4 Avvik mellom førebels og endeleg oppteljing av førehandsstemmesetlar\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom førebels og endeleg oppteljing av førehandsstemmer.)\n",
            'D2 Valtingsstemmer');
        $file_content = removeBetween($file_content,
            "D2.3 Godkjende valtingsstemmesetlar fordelt på parti\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "D2.4 Avvik mellom førebels og endeleg oppteljing av av ordinære valtingsstemmesetlar\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom førebels og endeleg oppteljing av valtingsstemmer)\n",
            'D3 Totaloversikt over tal stemmesetlar fordelt på parti');
        $file_content = removeBetween($file_content,
            "Parti   Førehand   Valting   Total\n",
            'Totalt tal på partifordelte stemmesetlar');
        $file_content = removeBetween($file_content,
            "E1.1 Utrekning av listestemmetal og talet på mandat til listene\n",
            'E2 Kandidatkåring');
        $file_content = removeBetween($file_content,
            "E2.1 Utrekning av stemmetillegg og personstemmer\n",
            'E2.2 Valde representantar og vararepresentantar');
        $file_content = removeBetween($file_content,
            "E2.2 Valde representantar og vararepresentantar\n",
            'Underskrifter:');
    }
    else {
        $file_content = removeBetween($file_content,
            "Parti   Antall\n",
            'Sum');
        $file_content = removeBetween($file_content,
            "Navn   Funksjon\n",
            'A2 Valgtinget');
        $file_content = removeBetween($file_content,
            "Krets   Åpningstid søndag   Åpningstid mandag\n",
            'B Foreløpig opptelling av forhåndsstemmer');
        $file_content = removeBetween($file_content,
            "B2.1.2 Partifordelte forhåndsstemmesedler\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "B2.2.2 Partifordelte sent innkomne forhåndsstemmesedler\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom kryss i manntall (B2.2.1) og foreløpig opptelling (B2.2.2) og evt. andre forhold)\n",
            'C Foreløpig opptelling av valgtingsstemmer');
        $file_content = removeBetween($file_content,
            "Søndag   Mandag   urne   omslag   omslag\n",
            'Sum');
        $file_content = removeBetween($file_content,
            "C1.1 Merknad fra stemmestyrene\n",
            'C2 Foreløpig opptelling hos stemmestyrene');
        $file_content = removeBetween($file_content,
            "C4.2 Partifordelte valgtingsstemmesedler i urne\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom kryss i manntall (C4.1) og foreløpig opptelling (C4.2) og evt. andre forhold)\n",
            'C4.4 Antall stemmesedler i særskilt omslag');
        $file_content = removeBetween($file_content,
            "C4.5 Partifordelte stemmesedler i særskilt omslag\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom kryss i manntall (C4.4) og foreløpig opptelling (C4.5) og evt. andre forhold)\n",
            'C4.7 Antall stemmesedler i beredskapskonvolutt');
        $file_content = removeBetween($file_content,
            "C4.8 Partifordelte stemmesedler i beredskapskonvolutt\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom kryss i manntall (C4.7) og foreløpig opptelling (C4.8) og evt. andre forhold)\n",
            'D Endelig opptelling');
        $file_content = removeBetween($file_content,
            "D1.3 Godkjente forhåndsstemmesedler fordelt på parti\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom foreløpig og endelig opptelling av forhåndsstemmer.)\n",
            'D2 Valgtingsstemmer');
        $file_content = removeBetween($file_content,
            "D2.3 Godkjente valgtingsstemmesedler fordelt på parti\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "(Årsak til evt. differanse mellom foreløpig og endelig opptelling av valgtingsstemmer)\n",
            'D3 Totaloversikt over antall stemmesedler fordelt på parti');
        $file_content = removeBetween($file_content,
            "Parti   Forhånd   Valgting   Total\n",
            'Totalt antall partifordelte stemmesedler');
        $file_content = removeBetween($file_content,
            "E1.1 Beregning av listestemmetall og antall mandater til listene\n",
            'E2 Kandidatkåring');
        $file_content = removeBetween($file_content,
            "E2.1 Beregning av stemmetillegg og personstemmer\n",
            'E2.2 Valgte representanter og vararepresentanter');
        $file_content = removeBetween($file_content,
            "E2.2 Valgte representanter og vararepresentanter\n",
            'Underskrifter:');
    }
    return $file_content;
}

function clean2($file_content) {
    // Strip empty lines
    $file_content = preg_replace('/\n\n/', "\n", $file_content);
    $file_content = preg_replace('/\n\n/', "\n", $file_content);
    $file_content = preg_replace('/\n\n/', "\n", $file_content);

    return $file_content;
}

function removeBetween($file_content, $first_word, $second_word) {
    if (empty($second_word)) {
        return $file_content;
    }

    return
        // Before
        substr($file_content, 0, strpos($file_content, $first_word) + strlen($first_word))
        // After
        . substr($file_content, strpos($file_content, $second_word, strpos($file_content, $first_word)));
}

function trimAllLines($file_content) {
    $lines = array();
    foreach (explode("\n", $file_content) as $line) {
        $lines[] = trim($line);
    }
    return implode("\n", $lines);
}

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

function regexAssertAndReturnMatch($regex, $line) {
    preg_match($regex, $line, $matches);
    if (!isset($matches[0])) {
        throw new Exception(
            'No match for regex.' . chr(10)
            . 'Regex ..... : ' . $regex . chr(10)
            . 'Line ...... : ' . $line
        );
    }
    return $matches;
}