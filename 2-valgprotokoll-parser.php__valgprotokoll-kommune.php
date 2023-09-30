<?php

function readValgprotokollKommune($file_content, &$obj, $election_year) {

    $nynorsk = str_contains($file_content, 'Valprotokoll for valstyret');
    if ($nynorsk) {
        $obj->language = 'nn-NO';
        $obj->languageName = 'Norwegian, Nynorsk';

        // Luster 2023, kommunestyrevalget virker neste (Etter manuelt oppgjør). Legger til en replace.
        if ($election_year == '2023' && str_contains($file_content, 'Luster')) {
            $file_content = str_replace('Valprotokoll for vastyret', 'Valprotokoll for valstyret', $file_content);
            $file_content = str_replace('12.09.2023 18:06:04            '
                . '                 Valprotokoll for valstyret       ' .
                '          Side' . chr(10) . '15', '12.09.2023 18:06:04            '
                . '                 Valprotokoll for valstyret       ' .
                '          Side 15', $file_content);
            $file_content = str_replace('12.09.2023 18:05:52                          '
                . '                     Valprotokoll for valstyret                         '
                . '                        Side' . chr(10) . '5',
                '12.09.2023 18:05:52                          '
                . '                     Valprotokoll for valstyret                         '
                . '                        Side 5', $file_content);
        }
        // Vågå 2023, mindre formattteringsbug i txt
        if ($election_year == '2023' && str_contains($file_content, 'Vågå')) {
            $file_content = str_replace(' Godkjende førehandsstemmegjevingar '
                . '(skal vere lik sum av B2.1.1 og B2.' . chr(10)
                . ' 2.1)' . chr(10)
                . '                                           '
                . '                                      '
                . '                           47',
                ' Godkjende førehandsstemmegjevingar '
                . '(skal vere lik sum av B2.1.1 og B2.2.1)'
                . '                                       '
                . '                                      '
                . '                             47', $file_content);

        }

        global $nynorskToBokmaal;
        foreach ($nynorskToBokmaal as $nynorskString => $bokmaalString) {
            $file_content = str_replace($nynorskString, $bokmaalString, $file_content);
        }
        $file_content = str_replace("manntalll", "manntall", $file_content);
    }
    elseif (str_contains($file_content, 'Valgprotokoll for valgstyret')) {
        $obj->language = 'nb-NO';
        $obj->languageName = 'Norwegian, Bokmål';
    }


    if ($election_year == '2023' && str_contains($file_content, 'Karmøy')) {
        // Page 28 is empty. It's fine.
        $file_content = str_replace(chr(10) . chr(10) . '13.09.2023 13:22:55              '
            . '              Valgprotokoll for valgstyret               Side 27', '', $file_content);
        $file_content = str_replace('13.09.2023 13:22:55   Valgprotokoll for valgstyret   Side 28', '', $file_content);
    }
    // Lørenskog 2023, en ekstra tom side rundt E1.1
    if ($election_year == '2023' && str_contains($file_content, 'Lørenskog')) {
        $file_content = str_replace('13.09.2023 12:41:20                            Valgprotokoll for valgstyret               Side 24'.chr(10),'', $file_content);
        $file_content = str_replace('13.09.2023 12:41:20   Valgprotokoll for valgstyret   Side 25'.chr(10),'', $file_content);
    }


    // :: Strip footers
    // 11.09.2019 12:50:17        Valgprotokoll for valgstyret      Side 4
    $regex_footer = '/^([0-9]*\.[0-9]*\.[0-9]* [0-9]*:[0-9]*:[0-9]*) \s* Valgprotokoll for valgstyret \s* Side [0-9]*$/';
    $match = regexAssertAndReturnMatch($regex_footer . 'm', $file_content);
    $obj->documentType = 'valgprotokoll';
    $obj->error = false;
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

    // Multi line header for Porsanger
    // Porsanger - Porságu - Porsanki kommune, Finnmark Finnmárku valgdistrikt
    $file_content = preg_replace('/Finnmárku\n *valgdistrikt/', "Finnmárku valgdistrikt", $file_content);


    // Clean up Randaberg.
    foreach (array(
                 'Kommunestyre- og fylkestingsvalget                       ' . $election_year => 'Kommunestyre- og fylkestingsvalget ' . $election_year,
                 'Total antall valgtingstemmesedler   i urne' => 'Total antall valgtingstemmesedler i urne  ',
                 'Total antall valgtingstemmesedler    i urne' => 'Total antall valgtingstemmesedler i urne  ',
                 'Total antall valgtingstemmesedler               i urne' => 'Total antall valgtingstemmesedler i urne               ',
                 'C4.1 Antall valgtingsstemmesedler    i urne' => 'C4.1 Antall valgtingsstemmesedler i urne',
                 'C4.1 Antall valgtingsstemmesedler     i urne' => 'C4.1 Antall valgtingsstemmesedler i urne',
                 'C4.2 Partifordelte valgtingsstemmesedler                  i urne' => 'C4.2 Partifordelte valgtingsstemmesedler i urne',
                 'C4.2 Partifordelte valgtingsstemmesedler      i urne' => 'C4.2 Partifordelte valgtingsstemmesedler i urne',
                 'C4.2 Partifordelte valgtingsstemmesedler       i urne' => 'C4.2 Partifordelte valgtingsstemmesedler i urne',
             ) as $to_be_replace => $replace_with) {
        $file_content = str_replace($to_be_replace, $replace_with, $file_content);
    }
    $file_content = str_replace('C2.1 Antall valgtingsstemmesedler    i urne', 'C2.1 Antall valgtingsstemmesedler i urne', $file_content);
    $file_content = str_replace('C4.1 - C4.3 Valgtingsstemmesedler                i urne', 'C4.1 - C4.3 Valgtingsstemmesedler i urne', $file_content);
    $file_content = str_replace('Valgprotokoll for valgstyret - Kommunestyrevalget       ' . $election_year, 'Valgprotokoll for valgstyret - Kommunestyrevalget ' . $election_year, $file_content);
    $file_content = str_replace('C2.1 Antall valgtingsstemmesedler                i urne', 'C2.1 Antall valgtingsstemmesedler i urne', $file_content);
    $file_content = str_replace('C4.1 - C4.3 Valgtingsstemmesedler    i urne', 'C4.1 - C4.3 Valgtingsstemmesedler i urne', $file_content);


    // Split into array and start counter.
    $lines_untrimmed = explode("\n", $file_content);
    $lines = array();
    foreach ($lines_untrimmed as $line) {
        $lines[] = str_replace("\n", '', $line);
    }
    $i = 0;

    // --- START page 1
    $match = regexAssertAndReturnMatch('/^\s*((Fylkestingsvalget|Kommunestyrevalget|Stortingsvalget) [0-9]*)\s*$/', $lines[$i++]);
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
    if ($obj->election == 'Stortingsvalget ' . $election_year) {
        $i = assertLine_trim($lines, $i, 'Stortingsvalget ' . $election_year);
    }
    else {
        $i = assertLine_trim($lines, $i, 'Kommunestyre- og fylkestingsvalget ' . $election_year);
    }
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, 'Valgprotokoll for valgstyret - ' . $obj->election);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $match = regexAssertAndReturnMatch('/^Kommune: \s*([A-Za-zÆØÅæøåáKárášjohka\- ]*)\s*$/', $lines[$i++]);
    $obj->municipality = trim($match[1]);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^Valgdistrikt: \s*([A-Za-zÆØÅæøåáö \-]*)\s*$/', $lines[$i++]);
    $obj->county = trim($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $yearLine = str_replace($election_year . '.0', $election_year, $lines[$i++]);
    $match = regexAssertAndReturnMatch('/^År: \s*([0-9]*)\s*$/', $yearLine);
    $obj->electionYear = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $i = assertLine_trim($lines, $i, 'Nøkkeltall i valggjennomføringen');

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Antall stemmeberettigede \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_antallStemmeberettigede = cleanFormattedNumber($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall kryss i manntallet \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallKryssIManntallet = cleanFormattedNumber($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Oppmøteprosent \s*([0-9, %]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_oppmøteprosent = $match[1];

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall godkjente forhåndsstemmegivninger \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallGodkjenteForhåndsstemmegivninger = cleanFormattedNumber($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall godkjente valgtingsstemmegivninger \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallGodkjenteValgtingsstemmegivninger = cleanFormattedNumber($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall forkastede stemmegivninger \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallForkastedeStemmegivninger = cleanFormattedNumber($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall godkjente stemmesedler \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallGodkjenteStemmesedler = cleanFormattedNumber($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^ Totalt antall forkastede stemmesedler \s*([0-9 ]*)\s*$/', $lines[$i++]);
    $obj->keyfigures_totaltAntallForkastedeStemmesedler = cleanFormattedNumber($match[1]);

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
    $i = readTable_twoColumns($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending);

    // ---- Table - B1.2 Behandlede forhåndsstemmegivninger
    $current_heading = 'B1.2 Behandlede forhåndsstemmegivninger';
    $text_heading = 'Forkastelser';
    $column_heading = 'Antall stemmegivninger';
    $column1 = 'Innenriks';
    $column2 = 'Utenriks';
    $sum_row1 = 'Godkjente forhåndsstemmegivninger (skal være lik sum av B2.1.1 og B2.2.1)';
    $sum_row2 = 'Totalt antall forhåndsstemmegivninger';
    $table_ending = $sum_row1;
    $start_of_row_keywords = array(
        'Godkjente forhåndsstemmegivninger (skal være lik sum av B2.1.1 og B2.2.1)',
        'Totalt antall forhåndsstemmegivninger',
    );
    $i = readTable_twoColumns($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending, $start_of_row_keywords);

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
    $start_of_row_keywords = array(
        'Godkjente',
        'Blanke',
        'Tvilsomme',
        'Total antall behandlede forhåndsstemmesedler',
    );
    $i = readTable_twoColumns($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending, $start_of_row_keywords);

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
    $start_of_row_keywords = array(
        'Godkjente',
        'Blanke',
        'Tvilsomme',
        'Total antall sent innkomne forhåndsstemmesedler',
    );
    $i = readTable_twoColumns($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending, $start_of_row_keywords);


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
        $obj->foretattForeløpigOpptellingHosStemmestyrene = false;
    }
    else {
        // ---- Table - C2.1 Antall valgtingsstemmesedler i urne
        $obj->foretattForeløpigOpptellingHosStemmestyrene = true;
        $current_heading = 'C2.1 Antall valgtingsstemmesedler i urne';
        $text_heading = null;
        $column_heading = null;
        $column1 = 'Kryss i manntall';
        $column2 = 'Ant. sedler';
        $sum_row1 = null;
        $sum_row2 = null;
        $table_ending = 'C2.2 Partifordelte Valgtingsstemmesedler';
        $start_of_row_keywords = array(
            'Godkjente',
            'Blanke',
            'Tvilsomme',
            'Total antall valgtingstemmesedler i urne'
        );
        $i = readTable_twoColumns($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending, $start_of_row_keywords);

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

    $i = assertLine_trim($lines, $i, 'C3 Stemmegivninger - valgting');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    // Skip
    while ($lines[$i] != 'C4 Foreløpig opptelling hos valgstyret') {
        $i++;
    }

    // C4 Foreløpig opptelling hos valgstyret
    $i = assertLine_trim($lines, $i, 'C4 Foreløpig opptelling hos valgstyret');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    if ($lines[$i + 1] == 'Foreløpig opptelling av valgtingsstemmesedler er foretatt hos stemmestyrene'
        || $lines[$i + 2] == 'Foreløpig opptelling av valgtingsstemmesedler er foretatt hos stemmestyrene') {
        // C4.1 - C4.3 Valgtingsstemmesedler i urne
        // Foreløpig opptelling av valgtingsstemmesedler er foretatt hos stemmestyrene
        $obj->foretattForeløpigOpptellingHosValgstyret = false;
        $i = assertLine_trim($lines, $i, 'C4.1 - C4.3 Valgtingsstemmesedler i urne');
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $i = assertLine_trim($lines, $i, 'Foreløpig opptelling av valgtingsstemmesedler er foretatt hos stemmestyrene');
    }
    else {
        // C4.1 Antall valgtingsstemmesedler i urne
        // C4.2 Partifordelte valgtingsstemmesedler i urne
        // C4.3 Merknad
        $obj->foretattForeløpigOpptellingHosValgstyret = true;

        $current_heading = 'C4.1 Antall valgtingsstemmesedler i urne';
        $text_heading = null;
        $column_heading = null;
        $column1 = 'Kryss i manntall';
        $column2 = 'Ant. sedler';
        $sum_row1 = null;
        $sum_row2 = null;
        $table_ending = 'C4.2 Partifordelte valgtingsstemmesedler i urne';
        $start_of_row_keywords = array(
            'Godkjente',
            'Blanke',
            'Tvilsomme',
            'Total antall valgtingstemmesedler i urne'
        );
        $i = readTable_twoColumns($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending, $start_of_row_keywords);
        $i = assertLine_trim($lines, $i, $table_ending);
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $i = removeLineIfPresent_andEmpty($lines, $i);

        // Skip
        while ($lines[$i] != 'C4.3 Merknad') {
            $i++;
        }

        // ---- Table - C4.3 Merknad
        $merknad_heading = 'C4.3 Merknad';
        $merknad_reason = '(Årsak til evt. differanse mellom kryss i manntall (C4.1) og foreløpig opptelling (C4.2) og evt. andre forhold)';
        $continue_until = 'C4.4 Antall stemmesedler i særskilt omslag';
        $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);
    }

    // C4.4 Antall stemmesedler i særskilt omslag
    // C4.5 Partifordelte stemmesedler i særskilt omslag
    // C4.6 Merknad
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

    if (str_contains($file_content, 'C4.7 Antall fremmede stemmesedler')) {
        $current_heading = 'C4.7 Antall fremmede stemmesedler';
        $text_heading = null;
        $column_heading = null;
        $column1 = 'Kryss i manntall';
        $column2 = 'Ant. sedler';
        $sum_row1 = null;
        $sum_row2 = null;
        $table_ending = 'C4.8 Partifordelte fremmede stemmesedler';
        $start_of_row_keywords = array(
            'Godkjente',
            'Blanke',
            'Tvilsomme',
            'Total antall fremmedstemmesedler'
        );
        $i = readTable_twoColumns($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending, $start_of_row_keywords);
        $i = assertLine_trim($lines, $i, $table_ending);
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $i = removeLineIfPresent_andEmpty($lines, $i);
    }
    else {
        $i = assertLine_trim($lines, $i, $continue_until);
        $i = removeLineIfPresent_andEmpty($lines, $i);
    }


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
    while ($lines[$i] != 'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler') {
        // Skip
        $i++;
    }

    // ---- Table - D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler
    // 3 columns with numbers
    $current_heading = 'D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler';
    $text_heading = 'Parti';
    $column1 = 'Foreløpig';
    $column2 = 'Endelig';
    $column3 = 'Avvik';
    $table_ending = 'D1.5 Merknad';
    global $alle_partier;
    $start_of_row_keywords_partier = $alle_partier;
    $start_of_row_keywords_partier[] = 'Totalt antall partifordelte stemmesedler';
    $i = readTable_threeColumns($obj, $lines, $i, $current_heading,
        $text_heading, $column1, $column2, $column3, $table_ending,
        $start_of_row_keywords_partier);


    // ---- Table - D1.5 Merknad
    $merknad_heading = 'D1.5 Merknad';
    $merknad_reason = '(Årsak til evt. differanse mellom foreløpig og endelig opptelling av forhåndsstemmer.)';
    $continue_until = 'D2 Valgtingsstemmer';
    $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);


    // D2 Valgtingsstemmer
    // D2.1 Opptalte valgtingsstemmesedler
    $current_heading = 'D2.1 Opptalte valgtingsstemmesedler';
    $column1 = 'Godkjente';
    $column2 = 'Blanke';
    $column3 = 'Forkastet';
    $column4 = 'Total';

    $i = assertLine_trim($lines, $i, $continue_until);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, $current_heading);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    regexAssertAndReturnMatch('/^Type \s* ' . $column1 . ' \s* ' . $column2 . ' \s* ' . $column3 . ' \s* ' . $column4 . '$/', trim($lines[$i++]));
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $obj->numbers[$current_heading] = array();
    foreach (array(
                 'Ordinære',
                 'Særskilt',
                 'Beredskap',
                 'Fremmede',
                 'Total antall opptalte valgtingsstemmesedler'
             ) as $keyWord) {
        if (
            ($keyWord == 'Beredskap' && !str_contains($lines[$i], $keyWord))
            || ($keyWord == 'Fremmede' && !str_contains($lines[$i], $keyWord))
        ) {
            // Optional
            continue;
        }

        $match = regexAssertAndReturnMatch('/^(' . $keyWord . ') \s* ([0-9]* ?[0-9]+) \s* ([0-9]* ?[0-9]+) \s* ([0-9]* ?[0-9]+) \s* ([0-9]* ?[0-9]+)$/', trim($lines[$i++]));
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $obj->numbers[$current_heading][$match[1]] = array(
            $column1 => cleanFormattedNumber($match[2]),
            $column2 => cleanFormattedNumber($match[3]),
            $column3 => cleanFormattedNumber($match[4]),
            $column4 => cleanFormattedNumber($match[5]),
        );
    }
    $i = assertLine_trim($lines, $i, 'D2.2 Forkastede valgtingsstemmesedler');


    // D2.2 Forkastede valgtingsstemmesedler
    // D2.3 Godkjente valgtingsstemmesedler fordelt på parti
    while ($lines[$i] != 'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler') {
        // Skip
        $i++;
    }

    // ---- Table - D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler
    $current_heading = 'D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler';
    $text_heading = 'Parti';
    $column1 = 'Foreløpig';
    $column2 = 'Endelig';
    $column3 = 'Avvik';
    $table_ending = 'D2.5 Merknad';
    $i = readTable_threeColumns($obj, $lines, $i, $current_heading,
        $text_heading, $column1, $column2, $column3, $table_ending,
        $start_of_row_keywords_partier);

    // ---- Table - D2.5 Merknad
    $merknad_heading = 'D2.5 Merknad';
    $merknad_reason = '(Årsak til evt. differanse mellom foreløpig og endelig opptelling av valgtingsstemmer)';
    $continue_until = 'D3 Totaloversikt over antall stemmesedler fordelt på parti';
    $i = readComments($obj, $lines, $i, $merknad_heading, $merknad_reason, $continue_until);

    // D3 Totaloversikt over antall stemmesedler fordelt på parti
    if (str_contains($obj->election, 'Kommunestyrevalget')) {
        // E Valgoppgjør
        // E1 Mandatfordeling
        // E1.1 Beregning av listestemmetall og antall mandater til listene

        while ($lines[$i] != 'E1.1 Beregning av listestemmetall og antall mandater til listene') {
            // Skip
            $i++;
        }
        assertLine_trim($lines, $i++, 'E1.1 Beregning av listestemmetall og antall mandater til listene');

        // This next section contains:
        //      <party name>
        // Antall stemmesedler x antall kst.repr.= <NUM> x <NUM OF SEATS>                    <RESULT>
        //
        //                                            Mottatt:                               <NUM>
        // Slengere:
        //                                            Avgitt:                                <NUM>
        // Listestemmetall:                                                                  <NUM>
        //
        // 1. div                                                                    <NUM>   <NUM>
        // 2. div                                                                    <NUM>   <NUM>
        // X. div                                                                    <NUM>   <NUM>
        // ...

        $i = removeLineIfPresent_andEmpty($lines, $i);

        $current_party = 'NULL NULL NULL NULL NULL NULL NULL NULL NULL NULL NULL NULL NULL NULL NULL NULL';
        $obj->e1_1_listestemmetall_og_mandater = array();
        $unknown_lines_e1_1 = array();
        $kommunestyrerepresentanter = null;
        while ($lines[$i] != 'Mandatene ble fordelt som følger:') {
            if (str_contains($lines[$i], '. div ')) {
                regexAssertAndReturnMatch('/^\s*[0-9]*\. div\s*[0-9\.]*\s\s*\s[0-9, ]*\s*$/', $lines[$i++]);
            }
            elseif (in_array(trim($lines[$i]), $alle_partier)) {
                if (!is_string($current_party)) {
                    $obj->e1_1_listestemmetall_og_mandater[] = $current_party;
                }
                $current_party = new stdClass();
                $current_party->name = trim($lines[$i++]);
                $i = removeLineIfPresent_andEmpty($lines, $i);

                $match = regexAssertAndReturnMatch('/^\s*Antall stemmesedler x antall kst\.repr\.= ([0-9]*) x ([0-9]*)\s\s*\s([0-9]*)$/', $lines[$i++]);
                $current_party->stemmesedler = cleanFormattedNumber($match[1]);
                $current_party->kommunestyrerepresentanter = cleanFormattedNumber($match[2]);
                $current_party->sedler_ganger_kst_repr = cleanFormattedNumber($match[3]);
                $i = removeLineIfPresent_andEmpty($lines, $i);

                $match = regexAssertAndReturnMatch('/^\s*Mottatt:\s\s*\s([0-9]*)$/', $lines[$i++]);
                $current_party->slengere_mottatt = cleanFormattedNumber($match[1]);
                $i = removeLineIfPresent_andEmpty($lines, $i);

                regexAssertAndReturnMatch('/^\s*Slengere:\s*$/', $lines[$i++]);
                $i = removeLineIfPresent_andEmpty($lines, $i);

                $match = regexAssertAndReturnMatch('/^\s*Avgitt:\s\s*\s([0-9]*)$/', $lines[$i++]);
                $current_party->slengere_avgitt = cleanFormattedNumber($match[1]);
                $i = removeLineIfPresent_andEmpty($lines, $i);

                $match = regexAssertAndReturnMatch('/^\s*Listestemmetall:\s\s*\s([0-9]*)$/', $lines[$i++]);
                $current_party->listestemmetall = cleanFormattedNumber($match[1]);

                // :: Consistency check - kommunestyrerep
                if ($kommunestyrerepresentanter != null && $current_party->kommunestyrerepresentanter != $kommunestyrerepresentanter) {
                    var_dump($obj->e1_1_listestemmetall_og_mandater);
                    var_dump($current_party);
                    throw new Exception('Different kommunestyrerepresentanter.');
                }
                $kommunestyrerepresentanter = $current_party->kommunestyrerepresentanter;

                // :: Consistency check - votes in final counting
                $partyNumbers_D1_4_pre_election_votes = $obj->numbers['D1.4 Avvik mellom foreløpig og endelig opptelling av forhåndsstemmesedler'][$current_party->name];
                $partyNumbers_D2_4_pre_election_votes = $obj->numbers['D2.4 Avvik mellom foreløpig og endelig opptelling av ordinære valgtingsstemmesedler'][$current_party->name];
                if (
                    ($partyNumbers_D1_4_pre_election_votes['Endelig'] + $partyNumbers_D2_4_pre_election_votes['Endelig'])
                    != $current_party->stemmesedler
                ) {
                    var_dump($current_party);
                    var_dump($partyNumbers_D1_4_pre_election_votes);
                    var_dump($partyNumbers_D2_4_pre_election_votes);
                    throw new Exception('Inconsistency in ["stemmesedler" in E1.1][' . $current_party->stemmesedler . ']'
                        . ' vs [D1.4 + D2.4][' . $partyNumbers_D1_4_pre_election_votes['Endelig'] . ' + ' . $partyNumbers_D2_4_pre_election_votes['Endelig'] . ']'
                        . ' for party [' . $current_party->name . '].');
                }

                // :: Consistency check - E1.1 - Just to make sure I got this right :-)
                if ($current_party->stemmesedler * $current_party->kommunestyrerepresentanter != $current_party->sedler_ganger_kst_repr) {
                    var_dump($current_party);
                    throw new Exception('Multiplication is hard. This should not happen.');
                }
                if ($current_party->sedler_ganger_kst_repr + $current_party->slengere_mottatt - $current_party->slengere_avgitt
                    != $current_party->listestemmetall) {
                    var_dump($current_party);
                    throw new Exception('Multiplication is hard. This should not happen.');
                }

                // :: Do it in reverse for initial counting
                $current_party->stemmesedler_initial_counting = $partyNumbers_D1_4_pre_election_votes['Foreløpig'] + $partyNumbers_D2_4_pre_election_votes['Foreløpig'];
                $current_party->listestemmetall_simulated_initial_counting =
                    (
                        $current_party->stemmesedler_initial_counting * $current_party->kommunestyrerepresentanter
                    ) + $current_party->slengere_mottatt - $current_party->slengere_avgitt;

            }
            else {
                $unknown_lines_e1_1[] = $lines[$i++];
            }
            $i = removeLineIfPresent_andEmpty($lines, $i);
        }
        if (!is_string($current_party)) {
            $obj->e1_1_listestemmetall_og_mandater[] = $current_party;
        }
        if (count($unknown_lines_e1_1) > 0) {
            throw new Exception('Unknown line in E1.1: ' . chr(10) . implode(chr(10), $unknown_lines_e1_1));
        }

        $current_heading = 'Mandatene ble fordelt som følger:';
        $text_heading = 'Mandat nr.:';
        $column_heading = null;
        $column1 = 'Parti:';
        $column2 = 'Kvotient:';
        $sum_row1 = null;
        $sum_row2 = null;
        $table_ending = 'E2 Kandidatkåring';
        $i = readTable_twoColumns($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending);
        $i = assertLine_trim($lines, $i, $table_ending);

        // Move it do a sensible key
        $obj->mandatFordelingEndelig = $obj->numbers[$current_heading];
        unset($obj->numbers[$current_heading]);

        $obj->e1_mandatPerParty = array();
        foreach ($obj->mandatFordelingEndelig as $party) {
            // Name fixups. Some long names...
            $party['Parti'] = str_replace('Tverrpolitisk liste for Fremskrittspartiet, Høyre', 'Tverrpolitisk liste for Fremskrittspartiet, Høyre og Venstre', $party['Parti']);
            $party['Parti'] = str_replace('Liste for Rødt, Senterpartiet og partiuavhengige', 'Liste for Rødt, Senterpartiet og partiuavhengige fiskere', $party['Parti']);
            $party['Parti'] = str_replace('Melhuslista, Tverrpolitisk liste for hele Melhus', 'Melhuslista, Tverrpolitisk liste for hele Melhus kommune', $party['Parti']);
            $party['Parti'] = str_replace('Fellesliste for Senterpartiet og Kristelig', 'Fellesliste for Senterpartiet og Kristelig Folkeparti', $party['Parti']);

            if (!isset($obj->e1_mandatPerParty[$party['Parti']])) {
                $obj->e1_mandatPerParty[$party['Parti']] = 0;
            }
            $obj->e1_mandatPerParty[$party['Parti']]++;
        }

        // E2 Kandidatkåring
        // E2.1 Beregning av stemmetillegg og personstemmer
        // E2.2 Valgte representanter og vararepresentanter
        $i = removeLineIfPresent_andEmpty($lines, $i);
        assertLine_trim($lines, $i, 'E2.1 Beregning av stemmetillegg og personstemmer');
    }


    while (isset($lines[$i]) && $lines[$i] != 'Øvrige medlemmer:') {
        // Skip
        $i++;
    }
    $i++;

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $unknown_lines = false;
    for (; $i < count($lines); $i++) {
        $unknown_lines = true;
        //echo '[' . $i . '] ' . $lines[$i] . "\n";
    }

    if ($unknown_lines) {
        logError('Unknown lines in [' . str_replace(__DIR__ . '/', '', $file) . '].');
        // TODO: throw exception here!

    }

    if (isset($obj->e1_1_listestemmetall_og_mandater)) {
        $runSettlement = function ($kommunestyrerepresentanter, $settlement_data) {
            file_put_contents('/tmp/election-data.json', json_encode($settlement_data, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_UNICODE ^ JSON_UNESCAPED_SLASHES));
            if (file_exists('/tmp/election-output.json')) {
                unlink('/tmp/election-output.json');
            }
            exec('python3 Election-stuff/settlement.py "/tmp/election-data.json" "/tmp/election-output.json"', $pdfinfoOutput);
            //var_dump($pdfinfoOutput);
            return json_decode(file_get_contents('/tmp/election-output.json'));
        };
        $settlement_data = new stdClass();
        $settlement_data->numberOfSeats = $kommunestyrerepresentanter;
        $settlement_data->voteTotals = array();
        foreach ($obj->e1_1_listestemmetall_og_mandater as $party) {
            $settlement_data->voteTotals[$party->name] = $party->listestemmetall;
        }
        $outputData = $runSettlement($kommunestyrerepresentanter, $settlement_data);

        $settlement_data2 = new stdClass();
        $settlement_data2->numberOfSeats = $kommunestyrerepresentanter;
        $settlement_data2->voteTotals = array();
        foreach ($obj->e1_1_listestemmetall_og_mandater as $party) {
            $settlement_data2->voteTotals[$party->name] = $party->listestemmetall_simulated_initial_counting;
        }
        $outputData_initial = $runSettlement($kommunestyrerepresentanter, $settlement_data2);

        // :: Consistency check of settlement.py vs Valgprotokoll
        foreach ($outputData->party_seats as $partyName => $partySeats) {
            if (!isset($obj->e1_mandatPerParty[$partyName]) && $partySeats == 0) {
                continue;
            }

            if (!isset($obj->e1_mandatPerParty[$partyName])
                || $obj->e1_mandatPerParty[$partyName] != $partySeats) {
                var_dump($obj->e1_mandatPerParty);
                var_dump($outputData->party_seats);
                exit('Inconsistencies in settlement.py for party [' . $partyName . ']'
                    . ' in [' . $obj->election . ', ' . $obj->municipality . ']. Missing in E1.1 or different.');
            }
        }
        $outputData->party_seats = (array)$outputData->party_seats;
        foreach ($obj->e1_mandatPerParty as $partyName => $partySeats) {
            if (!isset($outputData->party_seats[$partyName])) {
                var_dump($obj->e1_mandatPerParty);
                var_dump($outputData->party_seats);
                exit('Inconsistencies in settlement.py for party [' . $partyName . ']'
                    . ' in [' . $obj->election . ', ' . $obj->municipality . ']. Missing in settlement.py or different.');
            }
        }

        // :: Add parties with 0 for consistency
        foreach ($outputData->party_seats as $partyName => $partySeats) {
            $obj->e1_mandatPerParty[$partyName] = $partySeats;
        }

        // :: Add simulated initial counting
        $obj->e1_mandatPerParty_simulated_initial_counting = array();
        foreach ($outputData_initial->party_seats as $partyName => $partySeats) {
            $obj->e1_mandatPerParty_simulated_initial_counting[$partyName] = $partySeats;
        }
    }
}