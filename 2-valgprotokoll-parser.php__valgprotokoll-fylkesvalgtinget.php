<?php

function readValgprotokollFylkesvalgting($file_content, &$obj, $election_year) {

    $nynorsk = str_contains($file_content, 'Valprotokoll for fylkesvalstyret');
    if ($nynorsk) {
        $obj->language = 'nn-NO';
        $obj->languageName = 'Norwegian, Nynorsk';

        global $nynorskToBokmaal;
        foreach ($nynorskToBokmaal as $nynorskString => $bokmaalString) {
            $file_content = str_replace($nynorskString, $bokmaalString, $file_content);
        }
        $file_content = str_replace("manntalll", "manntall", $file_content);
    }
    elseif (str_contains($file_content, 'Valgprotokoll for fylkesvalgstyret')) {
        $obj->language = 'nb-NO';
        $obj->languageName = 'Norwegian, Bokmål';
    }

    // :: Strip footers
    // 13.09.2023 20:15:03         Valgprotokoll for fylkesvalgstyret - del 2   Side 2
    $regex_footer = '/^([0-9]*\.[0-9]*\.[0-9]* [0-9]*:[0-9]*:[0-9]*) \s* Valgprotokoll for fylkesvalgstyret - del 2 \s* Side [0-9]*$/';
    $match = regexAssertAndReturnMatch($regex_footer . 'm', $file_content);
    $obj->documentType = 'valgprotokoll-fylkesvalgstyret';
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

    $i = assertLine_trim($lines, $i, 'Valgprotokoll for fylkesvalgstyret i');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $obj->heading = 'Valgprotokoll for fylkesvalgstyret i ' . trim($lines[$i++]);

    // --- START page 2
    if ($obj->election == 'Stortingsvalget ' . $election_year) {
        $i = assertLine_trim($lines, $i, 'Stortingsvalget ' . $election_year);
    }
    else {
        $i = assertLine_trim($lines, $i, 'Kommunestyre- og fylkestingsvalget ' . $election_year);
    }
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, 'Valgprotokoll for fylkesvalgstyret - del 2');
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

    $obj->{'B Avvik kommune-fylke'} = new stdClass();

    if (ifExistsAndEqual($lines, $i, 'Mandatfordeling')) {
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
    while (trim($lines[$i]) != 'B Avvik mellom kommunenes endelige opptelling og valgdistriktets endelige opptelling') {
        $i++;
    }


    $i = assertLine_trim($lines, $i, 'B Avvik mellom kommunenes endelige opptelling og valgdistriktets endelige opptelling');
    $i = removeLineIfPresent_andEmpty($lines, $i);

    while (trim($lines[$i]) != 'C Avgitte godkjente stemmesedler') {
        // For each municipality
        $muncipality = new stdClass();
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $muncipality->name = regexAssertAndReturnMatch('/^Kommune:\s*([A-Za-zÆØÅæøå0-9 \-]*)\s*$/', $lines[$i++])[1];
        $obj->{'B Avvik kommune-fylke'}->{$muncipality->name} = $muncipality;
        $muncipality->numbers = array();

        $i = removeLineIfPresent_andEmpty($lines, $i);
        $i = assertLine_trim($lines, $i, 'B.1    Forkastede stemmegivninger');
        $muncipality->numbers['B.1 Forkastede stemmegivninger'] = new stdClass();

        $i = removeLineIfPresent_andEmpty($lines, $i);
        regexAssertAndReturnMatch('/ Type forkastelse  \s* Antall$/', $lines[$i++]);

        foreach (array(
                     'Velgeren er ikke innført i manntallet i kommunen § 10-1 (1) a',
                     'Stemmegivningen inneholder ikke tilstrekkelige opplysninger til å fastslå hvem velgeren er § 10-1 (1) b',
                     'Stemmegivningen er ikke avgitt til rett tid § 10-1 (1) c',
                     'Stemmegivningen er ikke levert til rett stemmemottaker § 10-1 (1) d',
                     'Omslagskonvolutten er åpnet eller forsøkt åpnet § 10-1 (1) e',
                     'Velgeren har allerede avgitt godkjent stemmegivning § 10-1 (1) f',
                     'Stemmegivningen er ikke kommet inn til valgstyret innen kl. 17 dagen etter valgdagen § 10-1 (1) g',
                     'Velgeren er ikke innført i manntallet i kommunen § 10-1a (1) a',
                     'Velgeren har allerede avgitt godkjent stemmegivning § 10-1a (1) c',
                     'Sum forkastede forhåndsstemmegivninger',
                     'Velger ikke i kommunens manntall §10-2(1) a)',
                     'Velger hadde forhåndsstemt/avgitt allerede godkjent stemme §10-2(1) c)',
                     'Sum forkastede valgtingsstemmegivninger',
                     'Totalt forkastede stemmegivninger'
                 ) as $type_forkastelse) {
            $i = removeLineIfPresent_andEmpty($lines, $i);
            $type_forkastelse_regex = str_replace('(', '\(', $type_forkastelse);
            $type_forkastelse_regex = str_replace(')', '\)', $type_forkastelse_regex);
            $type_forkastelse_regex = str_replace('/', '\/', $type_forkastelse_regex);
            $number = regexAssertAndReturnMatch('/^ ' . $type_forkastelse_regex . ' \s*([0-9 ]*)\s*$/', $lines[$i++])[1];
            $muncipality->numbers['B.1 Forkastede stemmegivninger']->{$type_forkastelse} = $number;
            $i = removeLineIfPresent_andEmpty($lines, $i);
        }


        $i = removeLineIfPresent_andEmpty($lines, $i);

        // ---- Table per municipality - B.2 Behandling av stemmesedler
        $current_heading = 'B.2 Behandling av stemmesedler';
        $text_heading = 'Stemmesedler';
        $column_heading = null;
        $column1 = 'Forhånd';
        $column2 = 'Valgting';
        $sum_row1 = null;
        $sum_row2 = null;
        $table_ending = 'B2.1       Forkastede stemmesedler';
        $i = readTable_twoColumns($muncipality, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2, $table_ending);

        // ---- Table per municipality - B2.1 Forkastede stemmesedler
        $current_heading = 'B2.1       Forkastede stemmesedler';
        $text_heading = 'Type forkastelse';
        $column_heading = null;
        $column1 = 'Kommune';
        $column2 = 'Valgdistrikt';
        $column3 = 'Avvik';
        $table_ending = 'B.2.2 Avvik mellom kommunens endelige opptelling og valgdistriktets endelige opptelling';
        $start_of_row_keywords_partier = array(
            'Seddelen manglet off. stempel §10-3(1) a)',
            'Det fremgår ikke hvilket valg stemmeseddelen gjelder §10-3(1) b)',
            'Det fremgår ikke hvilket parti eller gruppe velgeren har stemt på §10-3(1) c)',
            'Partiet eller gruppen stiller ikke liste §10-3(1) d)',
            'Sum forkastede stemmesedler - forhånd',
            'Sum forkastede stemmesedler - valgting',
            'Totalt forkastede stemmesedler',
        );
        $subheadings = array(
            'Forhånd',
            'Valgting'
        );
        $i = readTable_threeColumns_subheadings($muncipality, $lines, $i, $current_heading, $text_heading, $column1, $column2, $column3, $table_ending, $start_of_row_keywords_partier,$subheadings);
        $muncipality->numbers[$current_heading]['Sum forkastede stemmesedler - forhånd'] = $muncipality->numbers[$current_heading]['Forhånd']['Sum forkastede stemmesedler - forhånd'];
        unset($muncipality->numbers[$current_heading]['Forhånd']['Sum forkastede stemmesedler - forhånd']);
        $muncipality->numbers[$current_heading]['Sum forkastede stemmesedler - valgting'] = $muncipality->numbers[$current_heading]['Valgting']['Sum forkastede stemmesedler - valgting'];
        unset($muncipality->numbers[$current_heading]['Valgting']['Sum forkastede stemmesedler - valgting']);
        $muncipality->numbers[$current_heading]['Totalt forkastede stemmesedler'] = $muncipality->numbers[$current_heading]['Valgting']['Totalt forkastede stemmesedler'];
        unset($muncipality->numbers[$current_heading]['Valgting']['Totalt forkastede stemmesedler']);
        $muncipality->numbers['B2.1 Forkastede stemmesedler'] = $muncipality->numbers[$current_heading];
        unset($muncipality->numbers[$current_heading]);

        // ---- Table per municipality - B.2.2 Avvik mellom kommunens endelige opptelling og valgdistriktets endelige opptelling
        $current_heading = 'B.2.2 Avvik mellom kommunens endelige opptelling og valgdistriktets endelige opptelling';
        $text_heading = 'Parti';
        $column_heading = null;
        $column1 = 'Kommune';
        $column2 = 'Valgdistrikt';
        $column3 = 'Avvik';
        $table_ending = 'B.2.3 Blanke stemmesedler';
        global $alle_partier;
        $start_of_row_keywords_partier = $alle_partier;
        $start_of_row_keywords_partier[] = 'Sum antall partifordelte stemmesedler — forhånd';
        $start_of_row_keywords_partier[] = 'Sum antall partifordelte stemmesedler — valgting';
        $start_of_row_keywords_partier[] = 'Totalt antall partifordelte stemmesedler';
        $subheadings = array(
            'Forhånd',
            'Valgting'
        );
        $i = readTable_threeColumns_subheadings($muncipality, $lines, $i, $current_heading, $text_heading, $column1, $column2, $column3, $table_ending, $start_of_row_keywords_partier, $subheadings);
        $muncipality->numbers[$current_heading]['Sum antall partifordelte stemmesedler — forhånd'] = $muncipality->numbers[$current_heading]['Forhånd']['Sum antall partifordelte stemmesedler — forhånd'];
        unset($muncipality->numbers[$current_heading]['Forhånd']['Sum antall partifordelte stemmesedler — forhånd']);
        $muncipality->numbers[$current_heading]['Sum antall partifordelte stemmesedler — valgting'] = $muncipality->numbers[$current_heading]['Valgting']['Sum antall partifordelte stemmesedler — valgting'];
        unset($muncipality->numbers[$current_heading]['Valgting']['Sum antall partifordelte stemmesedler — valgting']);
        $muncipality->numbers[$current_heading]['Totalt antall partifordelte stemmesedler'] = $muncipality->numbers[$current_heading]['Valgting']['Totalt antall partifordelte stemmesedler'];
        unset($muncipality->numbers[$current_heading]['Valgting']['Totalt antall partifordelte stemmesedler']);

        // ---- Table per municipality - B.2.3 Blanke stemmesedler
        $current_heading = 'B.2.3 Blanke stemmesedler';
        $text_heading = 'Blanke stemmesedler';
        $column_heading = null;
        $column1 = 'Kommune';
        $column2 = 'Valgdistrikt';
        $column3 = 'Avvik';
        $table_ending = 'B.2.4 Merknader';
        $start_of_row_keywords_partier = array(
            'Forhånd',
            'Valgting',
            'Totalt'
        );
        $i = readTable_threeColumns($muncipality, $lines, $i, $current_heading, $text_heading, $column1, $column2, $column3, $table_ending, $start_of_row_keywords_partier);

        // ---- Comment sections per municipality - B.2.4 Merknader
        $merknad_heading = 'B.2.4 Merknader';
        $merknad_reason = null;

        $i = assertLine_trim($lines, $i, $merknad_heading);
        $i = removeLineIfPresent_andEmpty($lines, $i);

        $comment_lines = array();
        while (
            // Stopp på neste kommune
            !str_starts_with(trim($lines[$i]), 'Kommune: ') &&
            // Stopp på per-kommune-info
            !str_starts_with(trim($lines[$i]), 'C Avgitte godkjente stemmesedler')
        ) {
            $comment_lines[] = trim($lines[$i++]);
        }
        $comments = explode("\n\n", trim(implode("\n", $comment_lines)));
        $muncipality->comments[$merknad_heading] = $comments;
    }


    //$i = assertLine_trim($lines, $i, 'C Avgitte godkjente stemmesedler');
    //$i = removeLineIfPresent_andEmpty($lines, $i);

    $unknown_lines = false;
    $j = 0;
    for (; $i < count($lines); $i++) {
        $unknown_lines = true;
        echo '[' . $i . '] ' . $lines[$i] . "\n";
        $j++;
        if ($j == 10) {
            break;
        }
    }

    if ($unknown_lines) {
        $obj->unknown_lines = true;
        //throw new Exception('Unknown lines.');
    }


    return $obj;
}