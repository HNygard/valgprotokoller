<?php
/**
 * Parse 'valgprotokoll' PDFs
 *
 * Usage:
 *
 * - Normal usage. Tries to parse all files. Logs error but continues.
 *   php 2-valgprotokoll-parser.php
 * - Parser development. Stop on exceptions.
 *   php 2-valgprotokoll-parser.php throw
 *
 * @author Hallvard Nygård, @hallny
 */


set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$files = getDirContents(__DIR__ . '/data-store/pdfs');
foreach ($files as $file) {
    if (!str_ends_with($file, '.layout.txt')) {
        continue;
    }
    try {
        parseFile_andWriteToDisk($file);
    }
    catch (Exception $e) {
        logErrorWithStacktrace('Error parsing [' . $file . '].', $e);

        if (isset($argv[1]) && $argv[1] == 'throw') {
            throw $e;
        }
    }
    logInfo('.');
    logInfo('.');
    logInfo('.');
}

function parseFile_andWriteToDisk($file) {
    // => Parse this file. Line by line.
    logInfo('Parsing [' . str_replace(__DIR__ . '/', '', $file) . '].');

    if ($file) {
        $obj = new stdClass();
    }
    $file_content = file_get_contents($file);

    if (strlen(trim($file_content)) == 0) {
        logInfo('---> NO CONTENT.');
        return;
    }


    // :: Check for text in the start of the file
    // We are ignoring known headings.
    if (str_contains(substr($file_content, 0, 100), 'Kretsrapport valglokale')) {
        logInfo('Ignoring. Kretsrapport valglokale.');
        return;
    }
    if (str_contains(substr($file_content, 0, 100), 'Ny kandidatrangering per parti')) {
        logInfo('Ignoring. Ny kandidatrangering per parti.');
        return;
    }
    if (str_contains(substr($file_content, 0, 100), 'Valgoppgjør for ')) {
        logInfo('Ignoring. Valgoppgjør for .');
        return;
    }
    if (str_contains(substr($file_content, 0, 100), 'Valgdeltakelse')) {
        logInfo('Ignoring. Valgdeltakelse.');
        return;
    }

    /*
     * DIDN'T WORK - Should maybe pre clean these type of file instead of cluttering the code
     *  if (str_starts_with(trim($file_content), '* VALG')) {
         // Remove some stuff at the beginning of the file.
         // data-store/pdfs/elections-no.github.io-docs-2019-Troms_og_Finnmark-Kvænangen kommune, Troms og Finnmark fylke - kommunestyrevalget.pdf.layout.txt
         logInfo('Removing "* VALG" from first line.');
         $file_content = trim(substr(trim($file_content), strlen('* VALG')));
     }*/

    // :: Strip footers
    // 11.09.2019 12:50:17        Valgprotokoll for valgstyret      Side 4
    $regex_footer = '/^([0-9]*\.[0-9]*\.[0-9]* [0-9]*:[0-9]*:[0-9]*) \s* Valgprotokoll for valgstyret \s* Side [0-9]*$/';
    $match = regexAssertAndReturnMatch($regex_footer . 'm', $file_content);
    $obj->reportGenerated = $match[1];
    $file_content = preg_replace($regex_footer . 'm', '', $file_content);

    // Strip new page
    $file_content = str_replace(chr(12), '', $file_content);

    // Note: Don't know if this is a thing. But Levanger kommune have a logo and text at the top.
    /*
    if (str_starts_with(trim($file_content), 'Levanger kommune')) {
        $file_content = preg_replace('/\s\s\sLevanger kommune/', '', $file_content);
        $file_content = trim($file_content);

        var_dump( $file_content);

    }
    */

    // Strip multiple empty lines. Remenants of footer.
    $file_content = preg_replace('/\n\n\n/', "\n\n", $file_content);
    $file_content = preg_replace('/\n\n\n/', "\n\n", $file_content);
    $file_content = preg_replace('/\n\n\n/', "\n\n", $file_content);

    // Split into array and start counter.
    $lines_untrimmed = explode("\n", $file_content);
    $lines = array();
    foreach ($lines_untrimmed as $line) {
        $lines[] = str_replace("\n", '', $line);
    }
    $i = 0;

    // --- START page 1
    if (trim($lines[$i]) == 'Levanger kommune') {
        logInfo('Ignoring Levanger kommune.');
        return;
    }

    $match = regexAssertAndReturnMatch('/^\s*((Fylkestingsvalget|Kommunestyrevalget) [0-9]*)\s*$/', $lines[$i++]);
    $obj->election = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $i = assertLine_trim($lines, $i, 'Valgprotokoll for valgstyret i');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $obj->heading = 'Valgprotokoll for valgstyret i ' . trim($lines[$i++]);

    // --- START page 2
    $i = assertLine_trim($lines, $i, 'Kommunestyre- og fylkestingsvalget 2019');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, 'Valgprotokoll for valgstyret - ' . $obj->election);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $match = regexAssertAndReturnMatch('/^Kommune: \s*([A-Za-zÆØÅæøåá\- ]*)\s*$/', $lines[$i++]);
    $obj->municipality = trim($match[1]);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^Fylke: \s*([A-Za-zÆØÅæøå ]*)\s*$/', $lines[$i++]);
    $obj->county = trim($match[1]);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^År: \s*([0-9]*)\s*$/', $lines[$i++]);
    $obj->electionYear = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $i = assertLine_trim($lines, $i, 'Nøkkeltall i valggjennomføringen');

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Antall stemmeberettigede \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_antallStemmeberettigede = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall kryss i manntallet \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallKryssIManntallet = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Oppmøteprosent \s*([0-9, %]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_oppmøteprosent = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall godkjente forhåndsstemmegivninger \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallGodkjenteForhåndsstemmegivninger = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall godkjente valgtingsstemmegivninger \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallGodkjenteValgtingsstemmegivninger = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall forkastede stemmegivninger \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallForkastedeStemmegivninger = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall godkjente stemmesedler \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallGodkjenteStemmesedler = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall forkastede stemmesedler \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallForkastedeStemmesedler = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $obj->numbers = array();

    if (ifExistsAndEqual($lines, $i, 'Representantfordeling')) {
        while (!str_starts_with(trim($lines[$i]), 'Sum')) {
            // Skip
            $i++;
        }
        $i++;
        $i = removeLineIfPresent_andEmpty($lines, $i);
    }

    $i = assertLine_trim($lines, $i, 'A Administrative forhold');
    // A1 Valgstyret
    // A2 Valgtinget
    // Continue to 'B Foreløpig opptelling av forhåndsstemmer'
    while (trim($lines[$i]) != 'B Foreløpig opptelling av forhåndsstemmer') {
        $i++;
    }

    $i = assertLine_trim($lines, $i, 'B Foreløpig opptelling av forhåndsstemmer');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, 'B1 Behandling av mottatte forhåndsstemmegivninger');
    $i = removeLineIfPresent_andEmpty($lines, $i);


    // ---- Table - B1.1 Totalt mottatte forhåndsstemmegivninger
    $current_heading = 'B1.1 Totalt mottatte forhåndsstemmegivninger';
    $text_heading = null;
    $column_heading = 'Antall stemmegivninger';
    $column1 = 'Forkastet';
    $column2 = 'Godkjente';
    $sum_row1 = 'Totalt antall';
    $sum_row2 = null;
    $table_ending = $sum_row1;
    $i = readTable($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending);

    // ---- Table - B1.2 Behandlede forhåndsstemmegivninger
    $current_heading = 'B1.2 Behandlede forhåndsstemmegivninger';
    $text_heading = 'Forkastelser';
    $column_heading = 'Antall stemmegivninger';
    $column1 = 'Innenriks';
    $column2 = 'Utenriks';
    $sum_row1 = 'Godkjente forhåndsstemmegivninger (skal være lik sum av B2.1.1 og B2.2.1)';
    $sum_row2 = 'Totalt antall forhåndsstemmegivninger';
    $table_ending = $sum_row1;
    $i = readTable($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending);

    // Headings
    $i = assertLine_trim($lines, $i, 'B2 Foreløpig opptelling av forhåndsstemmesedler');
    $i = assertLine_trim($lines, $i, 'B2.1 Startet senest 4 timer før valglokalene stenger');

    // ---- Table - B2.1.1 Behandlede ordinære forhåndsstemmesedler
    $current_heading = 'B2.1.1 Behandlede ordinære forhåndsstemmesedler';
    $text_heading = null;
    $column_heading = null;
    $column1 = 'Kryss i manntall';
    $column2 = 'Ant. sedler';
    $sum_row1 = null;
    $sum_row2 = null;
    $table_ending = 'B2.1.2 Partifordelte forhåndsstemmesedler';
    $i = readTable($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending);

    // ---- Table - B2.1.2 Partifordelte forhåndsstemmesedler
    $i = assertLine_trim($lines, $i, 'B2.1.2 Partifordelte forhåndsstemmesedler');
    while (!str_starts_with(trim($lines[$i]), 'Totalt antall partifordelte stemmesedler')) {
        // Skip
        $i++;
    }
    $i++;
    $i = removeLineIfPresent_andEmpty($lines, $i);

    // ---- Table - B2.1.3 Merknad
    $merknad_heading = 'B2.1.3 Merknad';
    $merknad_reason = '(Årsak til evt. differanse mellom kryss i manntall (B2.1.1) og foreløpig opptelling (B2.1.2) og evt. andre forhold)';
    $continue_until = 'B2.2 Opptalt etter kl. 17.00 dagen etter valgdagen (lagt til side og sent innkomne)';
    $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);

    $i = assertLine_trim($lines, $i, 'B2.2 Opptalt etter kl. 17.00 dagen etter valgdagen (lagt til side og sent innkomne)');
    $i = removeLineIfPresent_andEmpty($lines, $i);

    // ---- Table - B2.2.1 Behandlede sent innkomne forhåndsstemmesedler
    $current_heading = 'B2.2.1 Behandlede sent innkomne forhåndsstemmesedler';
    $text_heading = null;
    $column_heading = null;
    $column1 = 'Kryss i manntall';
    $column2 = 'Ant. sedler';
    $sum_row1 = null;
    $sum_row2 = null;
    $table_ending = 'B2.2.2 Partifordelte sent innkomne forhåndsstemmesedler';
    $i = readTable($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending);


    // ---- Table - B2.2.2 Partifordelte sent innkomne forhåndsstemmesedler
    $i = assertLine_trim($lines, $i, 'B2.2.2 Partifordelte sent innkomne forhåndsstemmesedler');
    while (!str_starts_with(trim($lines[$i]), 'Totalt antall partifordelte stemmesedler')) {
        // Skip
        $i++;
    }
    $i++;
    $i = removeLineIfPresent_andEmpty($lines, $i);

    // ---- Table - B2.2.3 Merknad
    $merknad_heading = 'B2.2.3 Merknad';
    $merknad_reason = '(Årsak til evt. differanse mellom kryss i manntall (B2.2.1) og foreløpig opptelling (B2.2.2) og evt. andre forhold)';
    $continue_until = 'C Foreløpig opptelling av valgtingsstemmer';
    $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);


    // ---- C Foreløpig opptelling av valgtingsstemmer
    $i = assertLine_trim($lines, $i, 'C Foreløpig opptelling av valgtingsstemmer');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, 'C1 Oversikt over stemmer mottatt i alle kretser');
    while (!str_starts_with(trim($lines[$i]), 'C2 Foreløpig opptelling hos stemmestyrene')) {
        // Skip
        $i++;
    }
    $i = assertLine_trim($lines, $i, 'C2 Foreløpig opptelling hos stemmestyrene');
    $i = removeLineIfPresent_andEmpty($lines, $i);

    if ($lines[$i] == 'Det er ikke foretatt foreløpig opptelling hos stemmestyrene.') {
        $i = assertLine_trim($lines, $i, 'Det er ikke foretatt foreløpig opptelling hos stemmestyrene.');
        $i = removeLineIfPresent_andEmpty($lines, $i);
    }
    else {
        // ---- Table - C2.1 Antall valgtingsstemmesedler i urne
        $current_heading = 'C2.1 Antall valgtingsstemmesedler i urne';
        $text_heading = null;
        $column_heading = null;
        $column1 = 'Kryss i manntall';
        $column2 = 'Ant. sedler';
        $sum_row1 = null;
        $sum_row2 = null;
        $table_ending = 'C2.2 Partifordelte Valgtingsstemmesedler';

        $i = readTable($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending);

        // ---- Table - C2.2 Partifordelte Valgtingsstemmesedler
        $i = assertLine_trim($lines, $i, 'C2.2 Partifordelte Valgtingsstemmesedler');
        while (!str_starts_with(trim($lines[$i]), 'Totalt antall partifordelte stemmesedler')) {
            // Skip
            $i++;
        }
        $i++;
        $i = removeLineIfPresent_andEmpty($lines, $i);


        // ---- Table - C2.3 Merknad fra stemmestyret
        $merknad_heading = 'C2.3 Merknad fra stemmestyret';
        $merknad_reason = '(Årsak til evt. differanse mellom kryss i manntall (C2.1) og foreløpig opptelling og evt. andre forhold)';
        $continue_until = 'C3 Stemmegivninger - valgting';
        $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);
    }

    // C3 Stemmegivninger - valgting
    // C4 Foreløpig opptelling hos valgstyret
    // C4.1 - C4.3 Valgtingsstemmesedler i urne
    // C4.4 Antall stemmesedler i særskilt omslag
    // C4.5 Partifordelte stemmesedler i særskilt omslag
    // C4.6 Merknad
    $i = assertLine_trim($lines, $i, 'C3 Stemmegivninger - valgting');
    // Skip
    while ($lines[$i] != 'C4.6 Merknad') {
        $i++;
    }

    // ---- Table - C4.6 Merknad
    $merknad_heading = 'C4.6 Merknad';
    $merknad_reason = '(Årsak til evt. differanse mellom kryss i manntall (C4.4) og foreløpig opptelling (C4.5) og evt. andre forhold)';
    $continue_until = 'C4.7 Antall stemmesedler i beredskapskonvolutt';
    if (str_contains($file_content, 'C4.7 Antall fremmede stemmesedler')) {
        $continue_until = 'C4.7 Antall fremmede stemmesedler';
    }
    $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);

    // C4.7 Antall stemmesedler i beredskapskonvolutt
    // C4.8 Partifordelte stemmesedler i beredskapskonvolutt
    //
    // OR
    // C4.7 Antall fremmede stemmesedler
    // C4.8 Partifordelte fremmede stemmesedler
    $i = assertLine_trim($lines, $i, $continue_until);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    // Skip
    while ($lines[$i] != 'C4.9 Merknad') {
        $i++;
    }

    // ---- Table - C4.9 Merknad
    $merknad_heading = 'C4.9 Merknad';
    $merknad_reason = '(Årsak til evt. differanse mellom kryss i manntall (C4.7) og foreløpig opptelling (C4.8) og evt. andre forhold)';
    $continue_until = 'D Endelig opptelling';
    $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);

    // D Endelig opptelling
    // D1 Forhåndsstemmer
    // D1.1 Opptalte forhåndsstemmesedler
    // D1.2 Forkastede forhåndsstemmesedler
    // D1.3 Godkjente forhåndsstemmesedler fordelt på parti
    // TODO: parse - D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler

    while ($lines[$i] != 'D1.5 Merknad') {
        // Skip
        $i++;
    }
    //

    // ---- Table - D1.5 Merknad
    $merknad_heading = 'D1.5 Merknad';
    $merknad_reason = '(Årsak til evt. differanse mellom foreløpig og endelig opptelling av forhåndsstemmer.)';
    $continue_until = 'D2 Valgtingsstemmer';
    $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);


    // D2 Valgtingsstemmer
    // D2.1 Opptalte valgtingsstemmesedler
    // D2.2 Forkastede valgtingsstemmesedler
    // D2.3 Godkjente valgtingsstemmesedler fordelt på parti
    // D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler
    while ($lines[$i] != 'D2.5 Merknad') {
        // Skip
        $i++;
    }
    //

    // ---- Table - D2.5 Merknad
    $merknad_heading = 'D2.5 Merknad';
    $merknad_reason = '(Årsak til evt. differanse mellom foreløpig og endelig opptelling av valgtingsstemmer)';
    $continue_until = 'D3 Totaloversikt over antall stemmesedler fordelt på parti';
    $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);


    // D3 Totaloversikt over antall stemmesedler fordelt på parti
    while ($lines[$i] != 'Øvrige medlemmer:') {
        // Skip
        $i++;
    }
    $i++;


    $unknown_lines = false;
    for (; $i < count($lines); $i++) {
        $unknown_lines = true;
    }

    if ($unknown_lines) {
        logError('Unknown lines in [' . str_replace(__DIR__ . '/', '', $file) . '].');
        // TODO: throw exception here!
    }

    $data_dir = __DIR__ . '/data-store/json/' . $obj->election . '/' . $obj->county;
    if (!file_exists($data_dir)) {
        mkdir($data_dir, 0777, true);
    }
    file_put_contents(
        $data_dir . '/' . $obj->municipality . '.json',
        json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE)
    );
}

function readTable(&$obj, &$lines, $i, $current_heading, $text_heading, $column_heading,
                   $column1, $column2,
                   $sum_row1, $sum_row2, $table_ending) {
    $obj->numbers[$current_heading] = array();
    $i = assertLine_trim($lines, $i, $current_heading);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    if ($text_heading == null && $column_heading == null) {
        $header_length = strlen($lines[$i]);
        regexAssertAndReturnMatch('/^\s*' . $column1 . ' \s* ' . $column2 . '$/', $lines[$i++]);
    }
    elseif ($text_heading == null) {
        $i = assertLine_trim($lines, $i, $column_heading);
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $header_length = strlen($lines[$i]);
        regexAssertAndReturnMatch('/^\s*' . $column1 . ' \s* ' . $column2 . '$/', $lines[$i++]);
    }
    else {
        $header_length = strlen($lines[$i]);
        regexAssertAndReturnMatch('/^' . $text_heading . '\s*' . $column1 . '\s*' . $column2 . '$/', trim($lines[$i++]));
    }
    $readTable_twoColNumbers = function ($lines, $i, $header_length, $table_ending) {
        // One line.
        $row_lines = array($lines[$i++]);

        // Line 2
        if (strlen($lines[$i]) > 3 && !str_starts_with(trim($lines[$i]), $table_ending)) {
            $row_lines[] = str_replace("\r", '', $lines[$i++]);
        }

        // Line 3
        if (strlen($lines[$i]) > 3 && !str_starts_with(trim($lines[$i]), $table_ending)) {
            $row_lines[] = str_replace("\r", '', $lines[$i++]);
        }


        // Status:
        // - All on one line
        // - Numbers all the way to the right

        $row_line = '';
        foreach ($row_lines as $line) {
            if (strlen($line) >= ($header_length - 10)) {
                // -> Numbers line
                $match = regexAssertAndReturnMatch('/^(.*)\s+(([0-9]* ?[0-9]+)|(\—))\s\s\s+([0-9]* ?[0-9]+)\s*$/', $line);
                $row_line .= trim($match[1]);
            }
            else {
                $row_line .= $line;
            }
        }

        $i = removeLineIfPresent_andEmpty($lines, $i);

        $row_line = str_replace("\n", '', $row_line);
        $row_line = trim($row_line);

        return array(
            'i' => $i,
            'line' => $row_lines,
            'text' => $row_line,
            'numberColumn1' => $match[2],
            'numberColumn2' => $match[5]
        );
    };
    while (!str_starts_with(trim($lines[$i]), $table_ending)) {
        $row = $readTable_twoColNumbers($lines, $i, $header_length, $table_ending);
        $obj->numbers[$current_heading][$row['text']] = array(
            $column1 => $row['numberColumn1'],
            $column2 => $row['numberColumn2']
        );
        $i = $row['i'];
    }

    if ($sum_row1 != null) {
        $obj->numbers[$current_heading][$sum_row1] = regexAssertAndReturnMatch('/^'
            . str_replace('(', '\(',
                str_replace(')', '\)',
                    $sum_row1
                ))
            . ' \s* ([0-9 ]*)$/', trim($lines[$i++]));
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $i = removeLineIfPresent_andEmpty($lines, $i);
    }
    if ($sum_row2 != null) {
        $obj->numbers[$current_heading][$sum_row2] = regexAssertAndReturnMatch('/^'
            . str_replace('(', '\(',
                str_replace(')', '\)',
                    $sum_row2
                ))
            . ' \s* ([0-9 ]*)$/', trim($lines[$i++]));
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $i = removeLineIfPresent_andEmpty($lines, $i);
    }
    return $i;
}

function readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until) {
    $i = assertLine_trim($lines, $i, $merknad_heading);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, $merknad_reason);

    $comment_lines = array();
    while (!str_starts_with($lines[$i], $continue_until)) {
        $comment_lines[] = trim($lines[$i++]);
    }
    $comments = explode("\n\n", trim(implode("\n", $comment_lines)));
    $obj->comments[$merknad_heading] = $comments;
    return $i;
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

function logDebug($string) {
    //logLine($string, 'DEBUG');
}

function logInfo($string) {
    logLine($string, 'INFO');
}

function logError($string) {
    logLine($string, 'ERROR');
}

/**
 * @param $string
 * @param Exception $e
 */
function logErrorWithStacktrace($string, $e) {
    logLine($string . chr(10)
        . $e->getMessage() . chr(10)
        . $e->getTraceAsString(), 'ERROR');
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


function ifExistsAndEqual($lines, $i, $expected) {
    return (isset($lines[$i]) && $lines[$i] == $expected);
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

function assertLine($lines, $i, $expected) {
    if ($lines[$i] != $expected) {
        throw new Exception('Did not find expected value on line [' . $i . '].' . chr(10)
            . 'Expected ... : ' . $expected . chr(10)
            . 'Actual ..... : ' . $lines[$i]
        );
    }
    return $i + 1;
}

function assertLine_trim($lines, $i, $expected) {
    $lines[$i] = trim($lines[$i], " \t\n\r\0\x0B" . chr(12));
    if ($lines[$i] != $expected) {
        printChars($lines[$i]);
        throw new Exception('Did not find expected value on line [' . $i . '].' . chr(10)
            . 'Expected ... : ' . $expected . chr(10)
            . 'Actual ..... : ' . $lines[$i]
        );
    }
    return $i + 1;
}

function removeLineIfPresent($lines, $i, $expected) {
    if (isset($lines[$i]) && $lines[$i] == $expected) {
        $i++;
    }
    return $i;
}

function removeLineIfPresent_andEmpty($lines, $i) {
    if (isset($lines[$i]) && empty(trim($lines[$i]))) {
        $i++;
    }
    return $i;
}

function printChars($string) {
    for ($i = 0; $i < strlen($string); $i++) {
        echo '[' . $i . '] ' . ord($string{$i}) . ' - ' . $string{$i} . "\n";
    }
}