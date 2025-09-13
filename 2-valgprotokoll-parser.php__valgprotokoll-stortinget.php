<?php

function readValgprotokollStortinget($file_content, &$obj, $election_year) {

    $nynorsk = str_contains($file_content, 'Protokoll for valstyret');
    if ($nynorsk) {
        $obj->language = 'nn-NO';
        $obj->languageName = 'Norwegian, Nynorsk';

        global $nynorskToBokmaal;
        foreach ($nynorskToBokmaal as $nynorskString => $bokmaalString) {
            $file_content = str_replace($nynorskString, $bokmaalString, $file_content);
        }
        $file_content = str_replace("manntalll", "manntall", $file_content);
    }
    elseif (str_contains($file_content, 'Protokoll for valgstyret')) {
        $obj->language = 'nb-NO';
        $obj->languageName = 'Norwegian, Bokmål';
    }
    $obj->documentType = 'valgprotokoll-stortinget';

    // :: Strip footers
    // 10.09.2025   09:01                                                       Side 2 av 19
    $regex_footer = '/^([0-9]*\.[0-9]*\.[0-9]* *[0-9]*:[0-9]*) \s* Side [0-9]* av [0-9]*$/';
    $match = regexAssertAndReturnMatch($regex_footer . 'm', $file_content);
    $obj->error = false;
    $obj->reportGenerated = $match[1];
    $file_content = preg_replace($regex_footer . 'm', '', $file_content);

    // Strip new page
    $file_content = str_replace(chr(12), '', $file_content);

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

    $match = regexAssertAndReturnMatch('/^Protokoll for valgstyret i\s*(.*)\s*$/', trim($lines[$i++]));
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $obj->heading = 'Protokoll for valgstyret i ' . trim(trim($match[1]) . ' ' . trim($lines[$i++]));

    // --- START page 2
    if ($obj->election == 'Stortingsvalget ' . $election_year) {
        $i = assertLine_trim($lines, $i, 'Stortingsvalget ' . $election_year);
    }
    else {
        $i = assertLine_trim($lines, $i, 'Kommunestyre- og fylkestingsvalget ' . $election_year);
    }
    $obj->electionYear = $election_year;
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^Protokoll for valgstyret i\s*(.*)\s*$/', trim($lines[$i++]));
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $match = regexAssertAndReturnMatch('/^Kommune: \s*(.*)\s*$/', trim($lines[$i++]));
    $obj->municipality = trim($match[1]);
    $match = regexAssertAndReturnMatch('/^Valgdistrikt: \s*([A-Za-zÆØÅæøåáö \-]*)\s*$/', trim($lines[$i++]));
    $obj->county = trim($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    // Skip over "Innholdsfortegnelse" (table of contents) if present


    $i = assertLine_trim($lines, $i, 'Innholdsfortegnelse');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, 'A Nøkkeltall og informasjon om valggjennomføringen');
    // Skip the table of contents section until we find the key figures section
    while (
        $i < count($lines)
        && trim($lines[$i]) != 'A Nøkkeltall og informasjon om valggjennomføringen'
    ) {
        $i++;
    }

    $i = assertLine_trim($lines, $i, 'A Nøkkeltall og informasjon om valggjennomføringen');
    $i = assertLine_trim($lines, $i, 'A1 Oppsummering');
    $i = assertLine_trim($lines, $i, 'A1.1 Nøkkeltall');
    $i = assertLine_trim($lines, $i, 'Oversikt over antall stemmeberettigede og hvor mange velgere som benyttet stemmeretten.');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    

    // Stemmeberettigede          Godkjente stemmegivninger    Fremmøteprosent
    // 11 018                                         8 691             78,9 %
    $match = regexAssertAndReturnMatch('/^ *Stemmeberettigede \s* Godkjente stemmegivninger \s* Fremmøteprosent\s*$/', trim($lines[$i++]));
    $match = regexAssertAndReturnMatch('/^([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*) \s* ([0-9,]* %)\s*$/', trim($lines[$i++]));
    $obj->keyfigures_antallStemmeberettigede = cleanFormattedNumber($match[1]);
    $obj->keyfigures_totaltAntallGodkjenteStemmegivninger = cleanFormattedNumber($match[2]);
    $obj->keyfigures_oppmøteprosent = $match[3];
    
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);


    // 
    // A1.2 Stemmegivninger
    // Oversikt over godkjente stemmegivninger (kryss i manntall) og forkastede stemmegivninger i kommunen.
    // 
    //                                                                                                              Antall
    // Godkjente stemmegivninger                                                                                      8 691
    // 
    // Forkastelsesgrunn                                                                                             Antall
    // Velgeren er ikke innført i manntallet i kommunen § 10-2 (1) a                                                     21
    // Velgeren har ikke stemmerett § 10-2 (1) a                                                                         15
    // Det kan ikke fastslås hvem velgeren er § 10-2 (1) b                                                                 -
    // Stemmegivningen er ikke levert til et sted der velgeren kan stemme § 10-2 (1) c                                     -
    // Det er sannsynlighetsovervekt for at omslagskonvolutten er åpnet § 10-2 (1) d                                       1
    // Velgeren har tidligere fått godkjent en stemmegivning § 10-2 (1) e                                                  3
    // Stemmegivingen har kommet inn til valgstyret før forhåndsstemmegivningen har startet eller etter                    -
    // klokken 17 dagen etter valgdagen § 10-2 (1) f
    // Forkastede stemmegivninger                                                                                        40

    $obj->{'A2.1 Stemmegivninger'} = new stdClass();
    $i = assertLine_trim($lines, $i, 'A1.2 Stemmegivninger');
    $i = assertLine_trim($lines, $i, 'Oversikt over godkjente stemmegivninger (kryss i manntall) og forkastede stemmegivninger i kommunen.');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    
    $i = assertLine_trim($lines, $i, 'Antall');
    $match = regexAssertAndReturnMatch('/^Godkjente stemmegivninger \s* ([0-9 ]*)\s*$/', trim($lines[$i++]));
    if ($obj->keyfigures_totaltAntallGodkjenteStemmegivninger != cleanFormattedNumber($match[1])) {
        throw new ErrorException('Mismatch in godkjente stemmegivninger: ' . $obj->keyfigures_totaltAntallGodkjenteStemmegivninger . ' vs ' . cleanFormattedNumber($match[1]));
    }
    $obj->{'A2.1 Stemmegivninger'}->{'Godkjente stemmegivninger'} = cleanFormattedNumber($match[1]);

    $i = removeLineIfPresent_andEmpty($lines, $i);
    regexAssertAndReturnMatch('/^Forkastelsesgrunn\s*Antall$/', trim($lines[$i++]));
    $items = array(
        'Velgeren er ikke innført i manntallet i kommunen § 10-2 (1) a',
        'Velgeren har ikke stemmerett § 10-2 (1) a',
        'Det kan ikke fastslås hvem velgeren er § 10-2 (1) b',
        'Stemmegivningen er ikke levert til et sted der velgeren kan stemme § 10-2 (1) c',
        'Det er sannsynlighetsovervekt for at omslagskonvolutten er åpnet § 10-2 (1) d',
        'Velgeren har tidligere fått godkjent en stemmegivning § 10-2 (1) e',
        'Stemmegivingen har kommet inn til valgstyret før forhåndsstemmegivningen har startet eller etter klokken 17 dagen etter valgdagen § 10-2 (1) f',
        'Forkastede stemmegivninger'
    );
    foreach ($items as $item) {
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $item_regex = str_replace('(', '\(', $item);
        $item_regex = str_replace(')', '\)', $item_regex);
        $item_regex = str_replace('/', '\/', $item_regex);
        if ($item == 'Stemmegivingen har kommet inn til valgstyret før forhåndsstemmegivningen har startet eller etter klokken 17 dagen etter valgdagen § 10-2 (1) f') {
            $part1 = 'Stemmegivingen har kommet inn til valgstyret før forhåndsstemmegivningen har startet eller etter';
            $part2 = 'klokken 17 dagen etter valgdagen § 10-2 (1) f';
            $number = regexAssertAndReturnMatch('/^' . $part1 . ' \s*([0-9 \-]*)\s*$/', trim($lines[$i++]))[1];
            $i = assertLine_trim($lines, $i, $part2);
        }
        else {
            $number = regexAssertAndReturnMatch('/^' . $item_regex . ' \s*([0-9 \-]*)\s*$/', trim($lines[$i++]))[1];
        }   
        $obj->{'A2.1 Stemmegivninger'}->{$item} = $number;
    }
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);


    // A1.3 Stemmesedler
    // Oversikt over alle godkjente og forkastede stemmesedler i kommunen.
    // 
    //                                                                                                                 Antall
    // Godkjente stemmesedler                                                                                         8 689
    // Forkastede stemmesedler                                                                                           33
    // Totalt antall stemmesedler                                                                                    8 722
    // 
    // Forkastelsesgrunn                                                                                             Antall
    // Seddelen mangler offentlig stempel § 10-3 (1) a                                                                   31
    // Det fremkommer ikke hvilket valg stemmeseddelen gjelder § 10-3 (1) b                                                -
    // Det fremkommer ikke hvilket parti eller hvilken gruppe velgeren har stemt på § 10-3 (1) c                           2
    // Partiet eller gruppen stiller ikke liste i valgdistriktet § 10-3 (1) d                                              -
    // Forkastede stemmesedler                                                                                           33

    $obj->{'A1.3 Stemmesedler'} = new stdClass();
    $i = assertLine_trim($lines, $i, 'A1.3 Stemmesedler');
    $i = assertLine_trim($lines, $i, 'Oversikt over alle godkjente og forkastede stemmesedler i kommunen.');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $i = assertLine_trim($lines, $i, 'Antall');
    $match = regexAssertAndReturnMatch('/^Godkjente stemmesedler \s* ([0-9 ]*)\s*$/', trim($lines[$i++]));
    $obj->{'A1.3 Stemmesedler'}->{'Godkjente stemmesedler'} = cleanFormattedNumber($match[1]);
    $match = regexAssertAndReturnMatch('/^Forkastede stemmesedler \s* ([0-9 ]*)\s*$/', trim($lines[$i++]));
    $obj->{'A1.3 Stemmesedler'}->{'Forkastede stemmesedler'} = cleanFormattedNumber($match[1]);
    $match = regexAssertAndReturnMatch('/^Totalt antall stemmesedler \s* ([0-9 ]*)\s*$/', trim($lines[$i++]));
    $obj->{'A1.3 Stemmesedler'}->{'Totalt antall stemmesedler'} = cleanFormattedNumber($match[1]);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    regexAssertAndReturnMatch('/^Forkastelsesgrunn\s*Antall$/', trim($lines[$i++]));
    $items = array(
        'Seddelen mangler offentlig stempel § 10-3 (1) a',
        'Det fremkommer ikke hvilket valg stemmeseddelen gjelder § 10-3 (1) b',
        'Det fremkommer ikke hvilket parti eller hvilken gruppe velgeren har stemt på § 10-3 (1) c',
        'Partiet eller gruppen stiller ikke liste i valgdistriktet § 10-3 (1) d',
        'Forkastede stemmesedler'
    );
    foreach ($items as $item) {
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $item_regex = str_replace('(', '\(', $item);
        $item_regex = str_replace(')', '\)', $item_regex);
        $item_regex = str_replace('/', '\/', $item_regex);
        $number = regexAssertAndReturnMatch('/^' . $item_regex . ' \s*([0-9 \-]*)\s*$/', trim($lines[$i++]))[1];
        $obj->{'A1.3 Stemmesedler'}->{$item} = $number;
    }
    
    
    regexAssertAndReturnMatch('/Stop here/', $lines[$i++]);


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
        regexAssertAndReturnMatch('/^B.1 \s*Forkastede stemmegivninger$/', trim($lines[$i++]));
        $muncipality->numbers['B.1 Forkastede stemmegivninger'] = new stdClass();

        $i = removeLineIfPresent_andEmpty($lines, $i);
        regexAssertAndReturnMatch('/Type forkastelse  \s* Antall$/', trim($lines[$i++]));

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
