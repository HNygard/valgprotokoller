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

    // :: Strip footers and headers
    // 10.09.2025   09:01                                                       Side 2 av 19
    $regex_footer = '/^([0-9]*\.[0-9]*\.[0-9]* *[0-9]*:[0-9]*) \s* Side [0-9]* av [0-9]*$/';
    $match = regexAssertAndReturnMatch($regex_footer . 'm', $file_content);
    $obj->error = false;
    $obj->reportGenerated = $match[1];
    $file_content = preg_replace($regex_footer . 'm', '', $file_content);

    // Strip new page
    $file_content = str_replace(chr(12), '', $file_content);

    // Strip headers
    // Stortingsvalget 2025   Protokoll for valgstyret i Eigersund kommune, Rogaland Valgdistrikt
    $regex_header = '/^(Fylkestingsvalget|Kommunestyrevalget|Stortingsvalget) [0-9]* \s* Protokoll for valgstyret i (.*), (.*) Valgdistrikt\s*$/m';
    $file_content = preg_replace($regex_header, '', $file_content);

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
    $match = regexAssertAndReturnMatch('/^Godkjente stemmegivninger \s* ([0-9]* ?[0-9]*)\s*$/', trim($lines[$i++]));
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
            $number = regexAssertAndReturnMatch('/^' . $part1 . ' \s*([0-9\-]* ?[0-9]*)\s*$/', trim($lines[$i++]))[1];
            $i = assertLine_trim($lines, $i, $part2);
        }
        else {
            $number = regexAssertAndReturnMatch('/^' . $item_regex . ' \s*([0-9\-]* ?[0-9]*)\s*$/', trim($lines[$i++]))[1];
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
    $match = regexAssertAndReturnMatch('/^Godkjente stemmesedler \s* ([0-9]* ?[0-9]*)\s*$/', trim($lines[$i++]));
    $obj->{'A1.3 Stemmesedler'}->{'Godkjente stemmesedler'} = cleanFormattedNumber($match[1]);
    $match = regexAssertAndReturnMatch('/^Forkastede stemmesedler \s* ([0-9]* ?[0-9]*)\s*$/', trim($lines[$i++]));
    $obj->{'A1.3 Stemmesedler'}->{'Forkastede stemmesedler'} = cleanFormattedNumber($match[1]);
    $match = regexAssertAndReturnMatch('/^Totalt antall stemmesedler \s* ([0-9]* ?[0-9]*)\s*$/', trim($lines[$i++]));
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
        $number = regexAssertAndReturnMatch('/^' . $item_regex . ' \s*([0-9\-]* ?[0-9]*)\s*$/', trim($lines[$i++]))[1];
        $obj->{'A1.3 Stemmesedler'}->{$item} = $number;
    }
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    

    // A1.4 Fordeling av stemmesedler
    // Fordelingen av godkjente stemmesedler fra forhånd, valgting og totalt.
    // 
    //    Partinavn                                                                             Forhånd                   Valgting   Totalt
    //    Fremskrittspartiet                                                                        1 353                    1 313    2 666
    //    Arbeiderpartiet                                                                            941                     1 034    1 975
    //    Høyre                                                                                      585                      644     1 229
    //    Kristelig Folkeparti                                                                       375                      506        881
    //    Senterpartiet                                                                              191                      355        546
    //    Rødt                                                                                       208                      176        384
    //    SV - Sosialistisk Venstreparti                                                             157                      123        280
    //    Venstre                                                                                     90                      100        190
    //    Miljøpartiet De Grønne                                                                      75                       75        150
    //    Konservativt                                                                                40                       27         67
    //    Industri- og Næringspartiet                                                                 29                       35         64
    //    Pensjonistpartiet                                                                           25                       34         59
    //    Norgesdemokratene                                                                           16                       28         44
    //    Generasjonspartiet                                                                          21                       17         38
    //    Partiet DNI                                                                                  7                        8         15
    //    Velferd og Innovasjonspartiet                                                                6                        8         14
    //    Fred og Rettferdighet (FOR)                                                                  7                        2           9
    //    Partiet Sentrum                                                                              3                        2           5
    //    Totalt partifordelte stemmesedler                                                         4 129                   4 487     8 616
    //    Blanke stemmesedler                                                                         27                       46         73
    //    Totalt godkjente stemmesedler                                                             4 156                   4 533     8 689
    //     
    // 
    // 
    
    $obj->{'A1.4 Fordeling av stemmesedler'} = new stdClass();
    $i = assertLine_trim($lines, $i, 'A1.4 Fordeling av stemmesedler');
    $i = assertLine_trim($lines, $i, 'Fordelingen av godkjente stemmesedler fra forhånd, valgting og totalt.');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    regexAssertAndReturnMatch('/^Partinavn \s* Forhånd \s* Valgting \s* Totalt\s*$/', trim($lines[$i++]));
    // The list of item are political parties
    // Parse each party line until we hit "Totalt partifordelte stemmesedler"
    $obj->{'A1.4 Fordeling av stemmesedler'}->parties = array();
    while (true) {
        $i = removeLineIfPresent_andEmpty($lines, $i);
        if (str_starts_with(trim($lines[$i]), 'Totalt partifordelte stemmesedler')) {
            break;
        }
        $match = regexAssertAndReturnMatch('/^([A-Za-zÆØÅæøåáö\(\) \-]*) \s*  ([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*)$/', trim($lines[$i++]));
        $party = new stdClass();
        $party->name = trim($match[1]);
        $party->forhånd = cleanFormattedNumber($match[2]);
        $party->valgting = cleanFormattedNumber($match[3]);
        $party->totalt = cleanFormattedNumber($match[4]);
        $obj->{'A1.4 Fordeling av stemmesedler'}->parties[] = $party;
    }
    $match = regexAssertAndReturnMatch('/^Totalt partifordelte stemmesedler \s*  ([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*)$/', trim($lines[$i++]));
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Totalt partifordelte stemmesedler'} = new stdClass();
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Totalt partifordelte stemmesedler'}->forhånd = cleanFormattedNumber($match[1]);
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Totalt partifordelte stemmesedler'}->valgting = cleanFormattedNumber($match[2]);
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Totalt partifordelte stemmesedler'}->totalt = cleanFormattedNumber($match[3]);
    $match = regexAssertAndReturnMatch('/^Blanke stemmesedler \s*  ([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*)$/', trim($lines[$i++]));
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Blanke stemmesedler'} = new stdClass();
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Blanke stemmesedler'}->forhånd = cleanFormattedNumber($match    [1]);
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Blanke stemmesedler'}->valgting = cleanFormattedNumber($match[2]);
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Blanke stemmesedler'}->totalt = cleanFormattedNumber($match[3]);
    $match = regexAssertAndReturnMatch('/^Totalt godkjente stemmesedler \s*  ([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*)  \s* ([0-9]* ?[0-9]*)$/', trim($lines[$i++]));
    if ($obj->{'A1.3 Stemmesedler'}->{'Godkjente stemmesedler'} != cleanFormattedNumber($match[3])) {
        throw new ErrorException('Mismatch in totalt godkjente stemmesedler: ' . $obj->{'A1.3 Stemmesedler'}->{'Godkjente stemmesedler'} . ' vs ' . cleanFormattedNumber($match[3]));
    }
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Totalt godkjente stemmesedler'} = new stdClass();
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Totalt godkjente stemmesedler'}->forhånd = cleanFormattedNumber($match[1]);
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Totalt godkjente stemmesedler'}->valgting = cleanFormattedNumber($match[2]);
    $obj->{'A1.4 Fordeling av stemmesedler'}->{'Totalt godkjente stemmesedler'}->totalt = cleanFormattedNumber($match[3]);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    // A2 Informasjon om valggjennomføringen
    // A2.1 Manntall og tellemåte
    // Mantallet på valgdagen kan være elektronisk eller på papir. Andre telling kan gjennomføres manuelt eller maskinelt.
    // 
    //    Manntall valgting                                                                     Tellemåte andre telling
    //    Elektronisk                                                                           Maskinell
    //     
    // 
    // 
    $obj->{'A2.1 Manntall og tellemåte'} = new stdClass();
    $i = assertLine_trim($lines, $i, 'A2 Informasjon om valggjennomføringen');
    $i = assertLine_trim($lines, $i, 'A2.1 Manntall og tellemåte');
    $i = assertLine_trim($lines, $i, 'Mantallet på valgdagen kan være elektronisk eller på papir. Andre telling kan gjennomføres manuelt eller maskinelt.');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^Manntall valgting \s* Tellemåte andre telling\s*$/', trim($lines[$i++]));
    $match = regexAssertAndReturnMatch('/^(Elektronisk|Papir) \s* (Manuell|Maskinell)\s*$/', trim($lines[$i++]));
    $obj->{'A2.1 Manntall og tellemåte'}->manntallValgting = $match[1];
    $obj->{'A2.1 Manntall og tellemåte'}->tellemåteAndreTelling = $match[2];
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    // A2.2 Valgstyret
    // Folkevalgt organ oppnevnt av kommunestyret med ansvar for valggjennomføringen i kommunen.
    // 
    // 
    // 
    // 
    // 
    //    Medlem                                                                                Rolle
    //    ANJA HOVLAND                                                                          Leder
    //    MAY HELEN HETLAND ERVIK                                                               Nestleder
    //    LEIF ERIK BROCH                                                                       Sekretær
    //    ROALD EIE                                                                             Medlem
    //    BEATE KYDLAND                                                                         Medlem
    //    MARI SKAARA OMDAL                                                                     Medlem
    //    BJØRNAR STAPNES                                                                       Medlem
    //    KNUT SIREVÅG                                                                          Medlem
    //    ARNT OLAV SIVERTSEN                                                                   Medlem
    //    JOHN MONG                                                                             Medlem
    //    KARI JOHANNE MELHUS                                                                   Medlem
    //    HALVOR ØSTERMAN THENGS                                                                Medlem
    //    KARI-ANNE BERGØY                                                                      Varamedlem
    //    TOM RUNE SLEVELAND                                                                    Varamedlem
    //    TOVE HELEN LØYNING                                                                    Varamedlem
    //    ODD STANGELAND                                                                        Varamedlem
    //    ELIN ADSEN KVÅLE                                                                      Varamedlem
    //    SIGMUND SLETTEBØ                                                                      Varamedlem
    //    MORTEN ØGLEND                                                                         Varamedlem
    //    MARIAN SEGLEM                                                                         Varamedlem
    //    SVEN EIRIK HANSEN                                                                     Varamedlem
    //    LISE RAVNEBERG                                                                        Varamedlem
    //    ISELIN GRØSFJELD SKOGEN                                                               Varamedlem
    //    KJELL VIDAR NYGÅRD                                                                    Varamedlem
    //    JENNY KVILHAUG TUEN                                                                   Varamedlem
    //    STEFFEN VINDHEIM                                                                      Varamedlem
    //    OLGA MERETE ARNESEN ØSTERBØ                                                           Varamedlem
    //    HILDE ALICE SKÅRA GUNVALDSEN                                                          Varamedlem
    //    KENNETH PEDERSEN                                                                      Varamedlem
    //    FRANK LEIDLAND                                                                        Varamedlem
    //    GUNNAR KVASSHEIM                                                                      Varamedlem
    // 
    // 
    // 
    // 
    // 
    // 
    // 
    
    $obj->{'A2.2 Valgstyret'} = array();
    $i = assertLine_trim($lines, $i, 'A2.2 Valgstyret');
    $i = assertLine_trim($lines, $i, 'Folkevalgt organ oppnevnt av kommunestyret med ansvar for valggjennomføringen i kommunen.');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    regexAssertAndReturnMatch('/^Medlem \s* Rolle\s*$/', trim($lines[$i++]));
    while (true) {
        $i = removeLineIfPresent_andEmpty($lines, $i);
        if ($i >= count($lines) || trim($lines[$i]) == '') {
            break;
        }
        $match = regexAssertAndReturnMatch('/^([A-Za-zÆØÅæøåáö \-]*) \s* (Leder|Nestleder|Sekretær|Medlem|Varamedlem)\s*$/', trim($lines[$i++]));
        $member = new stdClass();
        $member->name = trim($match[1]);
        $member->role = trim($match[2]);
        $obj->{'A2.2 Valgstyret'}[] = $member;
    }
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    
    
    // B Forhåndsstemmer
    // B1 Oppsummering av forhåndsstemmer
    // B1.1 Forhåndsstemmegivninger
    // Oversikt over godkjente stemmegivninger (kryss i manntall) og forkastede stemmegivninger i kommunen.
    // 
    //                                                                                                        Antall
    //    Godkjente stemmegivninger                                                                            4 159
    // 
    //    Forkastelsesgrunn                                                                                   Antall
    //    Velgeren er ikke innført i manntallet i kommunen § 10-2 (1) a                                              2
    //    Velgeren har ikke stemmerett § 10-2 (1) a                                                                  3
    //    Det kan ikke fastslås hvem velgeren er § 10-2 (1) b                                                        -
    //    Stemmegivningen er ikke levert til et sted der velgeren kan stemme § 10-2 (1) c                            -
    //    Det er sannsynlighetsovervekt for at omslagskonvolutten er åpnet § 10-2 (1) d                              1
    //    Velgeren har tidligere fått godkjent en stemmegivning § 10-2 (1) e                                         3
    //    Stemmegivingen har kommet inn til valgstyret før forhåndsstemmegivningen har startet eller etter           -
    //    klokken 17 dagen etter valgdagen § 10-2 (1) f
    //    Forkastede stemmegivninger                                                                                9
    // 
    // 
    // 
    $obj->{'B1.1 Forhåndsstemmegivninger'} = new stdClass();
    $i = assertLine_trim($lines, $i, 'B Forhåndsstemmer');
    $i = assertLine_trim($lines, $i, 'B1 Oppsummering av forhåndsstemmer');
    $i = assertLine_trim($lines, $i, 'B1.1 Forhåndsstemmegivninger');
    $i = assertLine_trim($lines, $i, 'Oversikt over godkjente stemmegivninger (kryss i manntall) og forkastede stemmegivninger i kommunen.');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, 'Antall'); 
    $match = regexAssertAndReturnMatch('/^Godkjente stemmegivninger \s* ([0-9]* ?[0-9]*)\s*$/', trim($lines[$i++]));
    $obj->{'B1.1 Forhåndsstemmegivninger'}->{'Godkjente stemmegivninger'} = cleanFormattedNumber($match[1]);
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
        if ($item == 'Stemmegivingen har kommet inn til valgstyret før forhåndsstemmegivningen har startet eller etter klokken 17 dagen etter valgdagen § 10-2 (1) f') {
            $part1 = 'Stemmegivingen har kommet inn til valgstyret før forhåndsstemmegivningen har startet eller etter';
            $part2 = 'klokken 17 dagen etter valgdagen § 10-2 (1) f';
            $number = regexAssertAndReturnMatch('/^' . $part1 . ' \s*([0-9\-]* ?[0-9]*)\s*$/', trim($lines[$i++]))[1];
            $i = assertLine_trim($lines, $i, $part2);
        }
        else {
            $item_regex = str_replace('(', '\(', $item);
            $item_regex = str_replace(')', '\)', $item_regex);
            $item_regex = str_replace('/', '\/', $item_regex);
            $number = regexAssertAndReturnMatch('/^' . $item_regex . ' \s*([0-9\-]* ?[0-9]*)\s*$/', trim($lines[$i++]))[1];
        }
        $obj->{'B1.1 Forhåndsstemmegivninger'}->{$item} = $number;
    }
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    
    // B1.2 Forhåndsstemmesedler
    // Oversikt over alle godkjente og forkastede stemmesedler i kommunen.
    // 
    //                                                                                                        Antall
    //    Godkjente stemmesedler                                                                               4 156
    //    Forkastede stemmesedler                                                                                  19
    //    Totalt antall stemmesedler                                                                           4 175
    // 
    //    Forkastelsesgrunn                                                                                   Antall
    //    Seddelen mangler offentlig stempel § 10-3 (1) a                                                          17
    //    Det fremkommer ikke hvilket valg stemmeseddelen gjelder § 10-3 (1) b                                       -
    //    Det fremkommer ikke hvilket parti eller hvilken gruppe velgeren har stemt på § 10-3 (1) c                  2
    //    Partiet eller gruppen stiller ikke liste i valgdistriktet § 10-3 (1) d                                     -
    //    Forkastede stemmesedler                                                                                  19
    // 
    // 
    // 
    $obj->{'B1.2 Forhåndsstemmesedler'} = new stdClass();
    $i = assertLine_trim($lines, $i, 'B1.2 Forhåndsstemmesedler');
    $i = assertLine_trim($lines, $i, 'Oversikt over alle godkjente og forkastede stemmesedler i kommunen.');
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = assertLine_trim($lines, $i, 'Antall');
    $match = regexAssertAndReturnMatch('/^Godkjente stemmesedler \s* ([0-9]* ?[0-9]*)\s*$/', trim($lines[$i++]));
    $obj->{'B1.2 Forhåndsstemmesedler'}->{'Godkjente stemmesedler'} = cleanFormattedNumber($match[1]);
    $match = regexAssertAndReturnMatch('/^Forkastede stemmesedler \s* ([0-9]* ?[0-9]*)\s*$/', trim($lines[$i++]));
    $obj->{'B1.2 Forhåndsstemmesedler'}->{'Forkastede stemmesedler'} = cleanFormattedNumber($match[1]);
    $match = regexAssertAndReturnMatch('/^Totalt antall stemmesedler \s* ([0-9]* ?[0-9]*)\s*$/', trim($lines[$i++]));
    $obj->{'B1.2 Forhåndsstemmesedler'}->{'Totalt antall stemmesedler'} = cleanFormattedNumber($match[1]);
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
        $number = regexAssertAndReturnMatch('/^' . $item_regex . ' \s*([0-9\-]* ?[0-9]*)\s*$/', trim($lines[$i++]))[1];
        $obj->{'B1.2 Forhåndsstemmesedler'}->{$item} = $number;
    }
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    // B1.3 Fordeling av forhåndsstemmesedler
    // Fordelingen av godkjente stemmesedler i første og andre telling.
    // 
    // 
    // 
    // 
    // 
    //    Partinavn                                                                      Første telling             Andre telling        Avvik
    //    Fremskrittspartiet                                                                        1 354                   1 353            −1
    //    Arbeiderpartiet                                                                            941                     941               -
    //    Høyre                                                                                      584                     585               1
    //    Kristelig Folkeparti                                                                       375                     375               -
    //    Rødt                                                                                       208                     208               -
    //    Senterpartiet                                                                              191                     191               -
    //    SV - Sosialistisk Venstreparti                                                             157                     157               -
    //    Venstre                                                                                     90                      90               -
    //    Miljøpartiet De Grønne                                                                      75                      75               -
    //    Konservativt                                                                                40                      40               -
    //    Industri- og Næringspartiet                                                                 29                      29               -
    //    Pensjonistpartiet                                                                           25                      25               -
    //    Generasjonspartiet                                                                          21                      21               -
    //    Norgesdemokratene                                                                           16                      16               -
    //    Fred og Rettferdighet (FOR)                                                                  7                       7               -
    //    Partiet DNI                                                                                  7                       7               -
    //    Velferd og Innovasjonspartiet                                                                6                       6               -
    //    Partiet Sentrum                                                                              3                       3               -
    //    Totalt partifordelte stemmesedler                                                         4 129                  4 129
    //    Blanke stemmesedler                                                                         27                      27               -
    //    Totalt godkjente stemmesedler                                                             4 156                  4 156
    // 
    // 
    // 
 
 
    // B2 Forhåndsstemmer - fra valglokalene
    // Forhåndsstemmer telt per lokale.
    // 


    // ## NOTICE: This section B2.1, B2.2 and B2.3 repeats for every polling station (valglokale)
    // 4001 Rådhuset 4 etg
    // B2.1 Sammenligning av godkjente forhåndsstemmegivninger og forhåndsstemmesedler
    // Avvik mellom godkjente forhåndsstemmegivninger (kryss i manntall) og forhåndsstemmesedler fra første telling.
    // 
    //    Godkjente forhåndsstemmegivninger                                       Totalt antall stemmesedler                             Avvik
    //    3 267                                                                                             3 281                           −14
    // 
    //    Tvilsomme stemmesedler lagt til side før første telling                                                                            17
    // 
    // Merknad til sammenligning av godkjente stemmegivninger og stemmesedler
    // 
    //    Det er 3 267 kryss i manntallet og 3 264 godkjente stemmesedler.
    //    To fellessedler, stemplet, men uten at velgeren har huket av for hvilket parti/blank stemme som velgeren ønsker å stemme på. Det er i
    //    tillegg 15 tvilsomme uten stempel. Disse behandles i kategorien Forhåndsstemmer Øvrige. Det var registrert en feil 25.8.25 som
    //    forklarer avviket.
    //    Antallet mottatte stemmesedler er talt opp hver eneste dag, uten å se på parti/liste, og antallet har stemt overens hver eneste dag
    //    mot antall kryss i manntallet.
    // 
    // 
    // 
    // 
    // 
    // 


    // B2.2 Forkastede forhåndsstemmesedler
    // Oversikt over forhåndsstemmesedler fra valglokalet som valgstyret har forkastet i andre telling.
    // 
    //    Forkastelsesgrunn                                                                                                 Antall
    //    Seddelen mangler offentlig stempel § 10-3 (1) a                                                                          -
    //    Det fremkommer ikke hvilket valg stemmeseddelen gjelder § 10-3 (1) b                                                     -
    //    Det fremkommer ikke hvilket parti eller hvilken gruppe velgeren har stemt på § 10-3 (1) c                                -
    //    Partiet eller gruppen stiller ikke liste i valgdistriktet § 10-3 (1) d                                                   -
    //    Forkastede stemmesedler                                                                                                 0
    // 
    // 
    // 


    // B2.3 Fordeling av forhåndsstemmesedler - 4001 Rådhuset 4 etg
    // Fordelingen av godkjente stemmesedler i første og andre telling.
    // 
    //    Partinavn                                                                      Første telling     Andre telling    Avvik
    //    Fremskrittspartiet                                                                        1 131           1 131          -
    //    Arbeiderpartiet                                                                            745             745           -
    //    Høyre                                                                                      438             438           -
    //    Kristelig Folkeparti                                                                       302             302           -
    //    Rødt                                                                                       165             165           -
    //    Senterpartiet                                                                              133             133           -
    //    SV - Sosialistisk Venstreparti                                                             101             101           -
    //    Venstre                                                                                     62              62           -
    //    Miljøpartiet De Grønne                                                                      43              43           -
    //    Konservativt                                                                                36              36           -
    //    Pensjonistpartiet                                                                           20              20           -
    //    Industri- og Næringspartiet                                                                 18              18           -
    //    Generasjonspartiet                                                                          17              17           -
    //    Norgesdemokratene                                                                           14              14           -
    //    Partiet DNI                                                                                  7               7           -
    //    Fred og Rettferdighet (FOR)                                                                  6               6           -
    //    Velferd og Innovasjonspartiet                                                                5               5           -
    //    Partiet Sentrum                                                                              2               2           -
    //    Totalt partifordelte stemmesedler                                                         3 245          3 245
    //    Blanke stemmesedler                                                                         19              19           -
    //    Totalt godkjente stemmesedler                                                             3 264          3 264
    // 
    // Merknad til avvik mellom første og andre telling
    // 
    //    -
    // 
    // 
    // Andre merknader til forhåndsstemmer - 4001 Rådhuset 4 etg
    // Eventuelt annen relevant informasjon fra valggjennomføring og opptelling.
    // 
    //    -
    // 
    // 
    // 
    // 
    // 
    // 


    // B3 Forhåndsstemmer - øvrige
    // Forhåndsstemmer som ikke er telt per lokale.
    // 



    // B3.1 Sammenligning av godkjente forhåndsstemmegivninger og forhåndsstemmesedler
    // Avvik mellom godkjente forhåndsstemmegivninger (kryss i manntall) og forhåndsstemmesedler fra første telling.
    // 
    //    Godkjente forhåndsstemmegivninger                                          Godkjente stemmesedler                                        Avvik
    //    622                                                                                             622                                            -
    // 
    // Merknad til sammenligning av godkjente stemmegivninger og stemmesedler
    // 
    //    -
    // 
    // 
    // 
    // 



    // B3.2 Forkastede forhåndsstemmesedler - øvrige
    // Oversikt over forhåndsstemmesedler valgstyret har forkastet. Eventuelle forkastede tvilsomme sedler lagt til side før første telling fra
    // valglokalene, er inkludert i forkastelser fra første telling.
    // 
    //    Forkastelsesgrunn                                                              Første telling         Andre telling                     Totalt
    //    Seddelen mangler offentlig stempel § 10-3 (1) a                                           17                      -                          17
    //    Det fremkommer ikke hvilket valg                                                            -                     -                            -
    //    stemmeseddelen gjelder § 10-3 (1) b
    //    Det fremkommer ikke hvilket parti eller hvilken                                            2                      -                            2
    //    gruppe velgeren har stemt på § 10-3 (1) c
    //    Partiet eller gruppen stiller ikke liste i                                                  -                     -                            -
    //    valgdistriktet § 10-3 (1) d
    //    Forkastede stemmesedler                                                                   19                     0                           19
    // 
    // 
    // 
    
    
    // B3.3 Fordeling av forhåndsstemmesedler - øvrige
    // Fordelingen av stemmesedler i første og andre telling. Forkastede som oppdages i andre telling fjernes fra tellingen.
    // 
    // 
    // 
    // 
    // 
    //    Partinavn                                                                      Første telling   Andre telling                  Avvik
    //    Fremskrittspartiet                                                                        161            161                         -
    //    Arbeiderpartiet                                                                           135            135                         -
    //    Høyre                                                                                      98             98                         -
    //    Kristelig Folkeparti                                                                       54             54                         -
    //    Senterpartiet                                                                              44             44                         -
    //    Rødt                                                                                       35             35                         -
    //    SV - Sosialistisk Venstreparti                                                             35             35                         -
    //    Miljøpartiet De Grønne                                                                     23             23                         -
    //    Venstre                                                                                    15             15                         -
    //    Industri- og Næringspartiet                                                                 7              7                         -
    //    Generasjonspartiet                                                                          4              4                         -
    //    Pensjonistpartiet                                                                           3              3                         -
    //    Konservativt                                                                                2              2                         -
    //    Partiet Sentrum                                                                             1              1                         -
    //    Velferd og Innovasjonspartiet                                                               1              1                         -
    //    Fred og Rettferdighet (FOR)                                                                 0              0                         -
    //    Norgesdemokratene                                                                           0              0                         -
    //    Partiet DNI                                                                                 0              0                         -
    //    Totalt partifordelte stemmesedler                                                         618            618
    //    Blanke stemmesedler                                                                         4              4                         -
    //    Totalt godkjente stemmesedler                                                             622            622
    // 
    // Merknad til avvik mellom første og andre telling
    // 
    //    -
    // 
    // 
    // Andre merknader til forhåndsstemmer - øvrige
    // Eventuelt annen relevant informasjon fra valggjennomføring og opptelling.
    // 
    //    -
    // 
    // 
    // 
    // 
    
    
    
    // B4 Forhåndsstemmer - telt etter kl. 17 dagen etter valgdagen
    // Forhåndsstemmer mottatt etter forhåndsstemmeperioden er over og før kl. 17 dagen etter valgdagen. Av hensyn til hemmelig valg, er det lagt
    // til side et antall forhåndsstemmegivninger, som skal telles i denne kategorien.
    // 
    //    Forhåndsstemmer lagt til side                                                                                                      30
    // 
    // 
    // 
    // 
    // 
    // 
    
    
    
    // B4.1 Sammenligning av godkjente forhåndsstemmegivninger og forhåndsstemmesedler
    // Avvik mellom godkjente forhåndsstemmegivninger (kryss i manntall) og forhåndsstemmesedler fra første telling.
    // 
    //    Godkjente forhåndsstemmegivninger                                          Godkjente stemmesedler                       Avvik
    //    270                                                                                             270                           -
    // 
    // Merknad til sammenligning av godkjente stemmegivninger og stemmesedler
    // 
    //    -
    // 
    // 
    // 
    // 
    
    
    
    // B4.2 Forkastede forhåndsstemmesedler - telt etter kl. 17 dagen etter valgdagen
    // Oversikt over forhåndsstemmesedler valgstyret har forkastet.
    // 
    //    Forkastelsesgrunn                                                              Første telling         Andre telling    Totalt
    //    Seddelen mangler offentlig stempel § 10-3 (1) a                                             -                     -           -
    //    Det fremkommer ikke hvilket valg                                                            -                     -           -
    //    stemmeseddelen gjelder § 10-3 (1) b
    //    Det fremkommer ikke hvilket parti eller hvilken                                             -                     -           -
    //    gruppe velgeren har stemt på § 10-3 (1) c
    //    Partiet eller gruppen stiller ikke liste i                                                  -                     -           -
    //    valgdistriktet § 10-3 (1) d
    //    Forkastede stemmesedler                                                                     0                    0            0
    // 
    // 
    // 
    
    
    
    // B4.3 Fordeling av forhåndsstemmesedler - telt etter kl. 17 dagen etter valgdagen
    // Fordelingen av godkjente stemmesedler i første og andre telling.
    // 
    //    Partinavn                                                                      Første telling         Andre telling     Avvik
    //    Fremskrittspartiet                                                                         62                   61          −1
    //    Arbeiderpartiet                                                                            61                   61            -
    //    Høyre                                                                                      48                   49            1
    //    SV - Sosialistisk Venstreparti                                                             21                   21            -
    //    Kristelig Folkeparti                                                                       19                   19            -
    //    Senterpartiet                                                                              14                   14            -
    //    Venstre                                                                                    13                   13            -
    //    Miljøpartiet De Grønne                                                                      9                    9            -
    //    Rødt                                                                                        8                    8            -
    //    Industri- og Næringspartiet                                                                 4                    4            -
    //    Konservativt                                                                                2                    2            -
    //    Norgesdemokratene                                                                           2                    2            -
    //    Pensjonistpartiet                                                                           2                    2            -
    //    Fred og Rettferdighet (FOR)                                                                 1                    1            -
    //    Generasjonspartiet                                                                          0                    0            -
    //    Partiet DNI                                                                                 0                    0            -
    //    Partiet Sentrum                                                                             0                    0            -
    //    Velferd og Innovasjonspartiet                                                               0                    0            -
    //    Totalt partifordelte stemmesedler                                                         266                  266
    //    Blanke stemmesedler                                                                         4                    4            -
    //    Totalt godkjente stemmesedler                                                             270                  270
    // 
    // 
    // 
    // 
    // 
    // 
    // Merknad til avvik mellom første og andre telling
    // 
    //    Tellefeil i 1. telling (manuell telling)
    // 
    // 
    // Andre merknader til forhåndsstemmer - telt etter kl. 17 dagen etter valgdagen
    // Eventuelt annen relevant informasjon fra valggjennomføring og opptelling.
    // 
    //    -
    // 
    // 
    // 
    // 
    
    
    
    // C Valgtingsstemmer
    // C1 Oppsummering av valgtingsstemmer
    // C1.1 Valgtingsstemmegivninger
    // Oversikt over godkjente stemmegivninger (kryss i manntall) og forkastede stemmegivninger i kommunen.
    // 
    //                                                                                                         Antall
    //    Godkjente stemmegivninger                                                                              4 532
    // 
    //    Forkastelsesgrunn                                                                                    Antall
    //    Velgeren er ikke innført i manntallet i kommunen § 10-2 (1) a                                             19
    //    Velgeren har ikke stemmerett § 10-2 (1) a                                                                 12
    //    Det kan ikke fastslås hvem velgeren er § 10-2 (1) b                                                         -
    //    Stemmegivningen er ikke levert til et sted der velgeren kan stemme § 10-2 (1) c                             -
    //    Det er sannsynlighetsovervekt for at omslagskonvolutten er åpnet § 10-2 (1) d                               -
    //    Velgeren har tidligere fått godkjent en stemmegivning § 10-2 (1) e                                          -
    //    Stemmegivingen har kommet inn til valgstyret før forhåndsstemmegivningen har startet eller etter            -
    //    klokken 17 dagen etter valgdagen § 10-2 (1) f
    //    Forkastede stemmegivninger                                                                                31
    // 
    // 
    // 
    
    
    
    // C1.2 Valgtingsstemmesedler
    // Oversikt over alle godkjente og forkastede stemmesedler i kommunen.
    // 
    //                                                                                                         Antall
    //    Godkjente stemmesedler                                                                                 4 533
    //    Forkastede stemmesedler                                                                                   14
    //    Totalt antall stemmesedler                                                                            4 547
    // 
    //    Forkastelsesgrunn                                                                                    Antall
    //    Seddelen mangler offentlig stempel § 10-3 (1) a                                                           14
    //    Det fremkommer ikke hvilket valg stemmeseddelen gjelder § 10-3 (1) b                                        -
    //    Det fremkommer ikke hvilket parti eller hvilken gruppe velgeren har stemt på § 10-3 (1) c                   -
    //    Partiet eller gruppen stiller ikke liste i valgdistriktet § 10-3 (1) d                                      -
    //    Forkastede stemmesedler                                                                                   14
    // 
    // 
    // 
    // 
    // 
    // 
    
    
    
    // C1.3 Fordeling av valgtingsstemmesedler
    // Fordelingen av godkjente stemmesedler i første og andre telling.
    // 
    //    Partinavn                                                                      Første telling             Andre telling     Avvik
    //    Fremskrittspartiet                                                                        1 312                   1 313           1
    //    Arbeiderpartiet                                                                           1 033                   1 034           1
    //    Høyre                                                                                      645                     644          −1
    //    Kristelig Folkeparti                                                                       506                     506            -
    //    Senterpartiet                                                                              356                     355          −1
    //    Rødt                                                                                       176                     176            -
    //    SV - Sosialistisk Venstreparti                                                             123                     123            -
    //    Venstre                                                                                     99                     100            1
    //    Miljøpartiet De Grønne                                                                      75                      75            -
    //    Industri- og Næringspartiet                                                                 35                      35            -
    //    Pensjonistpartiet                                                                           34                      34            -
    //    Norgesdemokratene                                                                           28                      28            -
    //    Konservativt                                                                                27                      27            -
    //    Generasjonspartiet                                                                          17                      17            -
    //    Partiet DNI                                                                                  8                       8            -
    //    Velferd og Innovasjonspartiet                                                                8                       8            -
    //    Fred og Rettferdighet (FOR)                                                                  2                       2            -
    //    Partiet Sentrum                                                                              2                       2            -
    //    Totalt partifordelte stemmesedler                                                         4 486                  4 487
    //    Blanke stemmesedler                                                                         46                      46            -
    //    Totalt godkjente stemmesedler                                                             4 532                  4 533
    // 
    // 
    // 
    
    
    
    // C2 Valgtingsstemmer - fra valglokalene
    // Valgtingsstemmer telt per lokale. Første telling er gjennomført i valglokalet.
    // 


    // ## NOTICE: This section C2.1, C2.2 and C2.3 repeats for every polling station (valglokale)
    // 0001 Lundåne Bo- og servicesenter
    
    
    // C2.1 Sammenligning av godkjente valgtingsstemmegivninger og valgtingsstemmesedler
    // Avvik mellom godkjente valgtingsstemmegivninger (kryss i manntall) og valgtingsstemmesedler fra første telling.
    // 
    //    Godkjente valgtingsstemmegivninger                                      Totalt antall stemmesedler                          Avvik
    //    1 373                                                                                             1 379                         −6
    // 
    //    Tvilsomme stemmesedler lagt til side før første telling                                                                           6
    // 
    // Merknad til sammenligning av godkjente stemmegivninger og stemmesedler
    // 
    //    Avvik på 6 er stemmesedler uten stempel (tvilsomme sedler)
    // 
    // 
    // 
    // 
    // 
    // 
    
    
    
    // C2.2 Forkastede valgtingsstemmesedler
    // Oversikt over valgtingsstemmesedler fra valglokalet som valgstyret har forkastet i andre telling.
    // 
    //    Forkastelsesgrunn                                                                                                  Antall
    //    Seddelen mangler offentlig stempel § 10-3 (1) a                                                                           -
    //    Det fremkommer ikke hvilket valg stemmeseddelen gjelder § 10-3 (1) b                                                      -
    //    Det fremkommer ikke hvilket parti eller hvilken gruppe velgeren har stemt på § 10-3 (1) c                                 -
    //    Partiet eller gruppen stiller ikke liste i valgdistriktet § 10-3 (1) d                                                    -
    //    Forkastede stemmesedler                                                                                                   0
    // 
    // 
    // 
    
    
    
    // C2.3 Fordeling av valgtingsstemmesedler - 0001 Lundåne Bo- og servicesenter
    // Fordelingen av godkjente stemmesedler i første og andre telling.
    // 
    //    Partinavn                                                                      Første telling     Andre telling     Avvik
    //    Fremskrittspartiet                                                                         343             345            2
    //    Arbeiderpartiet                                                                            327             327            -
    //    Høyre                                                                                      218             218            -
    //    Kristelig Folkeparti                                                                       167             165          −2
    //    Senterpartiet                                                                               81              81            -
    //    Rødt                                                                                        66              66            -
    //    Venstre                                                                                     41              41            -
    //    SV - Sosialistisk Venstreparti                                                              39              39            -
    //    Miljøpartiet De Grønne                                                                      21              21            -
    //    Pensjonistpartiet                                                                           13              13            -
    //    Norgesdemokratene                                                                           12              12            -
    //    Industri- og Næringspartiet                                                                 10              10            -
    //    Konservativt                                                                                 8               8            -
    //    Generasjonspartiet                                                                           3               3            -
    //    Fred og Rettferdighet (FOR)                                                                  2               2            -
    //    Partiet DNI                                                                                  2               2            -
    //    Partiet Sentrum                                                                              0               0            -
    //    Velferd og Innovasjonspartiet                                                                0               0            -
    //    Totalt partifordelte stemmesedler                                                         1 353          1 353
    //    Blanke stemmesedler                                                                         20              20            -
    //    Totalt godkjente stemmesedler                                                             1 373          1 373
    // 
    // Merknad til avvik mellom første og andre telling
    // 
    //    Tellefeil i 1. telling i valglokalet
    // 
    // 
    // Andre merknader til valgtingsstemmer - 0001 Lundåne Bo- og servicesenter
    // Eventuelt annen relevant informasjon fra valggjennomføring og opptelling.
    // 
    //    -
    // 
    // 
    // 
    // 
    
    
    
    // C3 Valgtingsstemmer - øvrige
    // Valgtingsstemmer som ikke er telt per lokale.
    // 
    
    
    
    // C3.1 Sammenligning av godkjente valgtingsstemmegivninger og valgtingsstemmesedler
    // Avvik mellom godkjente valgtingsstemmegivninger (kryss i manntall) og valgtingsstemmesedler fra første telling.
    // 
    //    Godkjente valgtingsstemmegivninger                                         Godkjente stemmesedler                     Avvik
    //    1                                                                                               1                           -
    // 
    // Merknad til sammenligning av godkjente stemmegivninger og stemmesedler
    // 
    //    En velger ble innført i manntallet etter å ha stemt i konvolutt, vedkommende hadde stemmerett.
    // 
    // 
    // 
    // 
    // 
    // 
    
    
    
    // C3.2 Forkastede valgtingsstemmesedler - øvrige
    // Oversikt over valgtingsstemmesedler valgstyret har forkastet. Eventuelle forkastede tvilsomme sedler lagt til side før første telling fra
    // valglokalene er inkludert i forkastelser fra første telling.
    // 
    //    Forkastelsesgrunn                                                              Første telling    Andre telling                           Totalt
    //    Seddelen mangler offentlig stempel § 10-3 (1) a                                           14                   -                            14
    //    Det fremkommer ikke hvilket valg                                                            -                  -                              -
    //    stemmeseddelen gjelder § 10-3 (1) b
    //    Det fremkommer ikke hvilket parti eller hvilken                                             -                  -                              -
    //    gruppe velgeren har stemt på § 10-3 (1) c
    //    Partiet eller gruppen stiller ikke liste i                                                  -                  -                              -
    //    valgdistriktet § 10-3 (1) d
    //    Forkastede stemmesedler                                                                   14                  0                             14
    // 
    // 
    // 
    
    
    
    // C3.3 Fordeling av stemmesedler - valgtingsstemmer - øvrige
    // Fordelingen av godkjente stemmesedler i første og andre telling.
    // 
    //    Partinavn                                                                      Første telling    Andre telling                           Avvik
    //    Fremskrittspartiet                                                                         1                  1                               -
    //    Arbeiderpartiet                                                                            0                  0                               -
    //    Fred og Rettferdighet (FOR)                                                                0                  0                               -
    //    Generasjonspartiet                                                                         0                  0                               -
    //    Høyre                                                                                      0                  0                               -
    //    Industri- og Næringspartiet                                                                0                  0                               -
    //    Konservativt                                                                               0                  0                               -
    //    Kristelig Folkeparti                                                                       0                  0                               -
    //    Miljøpartiet De Grønne                                                                     0                  0                               -
    //    Norgesdemokratene                                                                          0                  0                               -
    //    Partiet DNI                                                                                0                  0                               -
    //    Partiet Sentrum                                                                            0                  0                               -
    //    Pensjonistpartiet                                                                          0                  0                               -
    //    Rødt                                                                                       0                  0                               -
    //    SV - Sosialistisk Venstreparti                                                             0                  0                               -
    //    Senterpartiet                                                                              0                  0                               -
    //    Velferd og Innovasjonspartiet                                                              0                  0                               -
    //    Venstre                                                                                    0                  0                               -
    //    Totalt partifordelte stemmesedler                                                          1                  1
    //    Blanke stemmesedler                                                                        0                  0                               -
    //    Totalt godkjente stemmesedler                                                              1                  1
    // 
    // Merknad til avvik mellom første og andre telling
    // 
    //    -
    // 
    // 
    // 
    // 
    // 
    // 
    // Andre merknader til valgtingsstemmer - øvrige
    // Eventuelt annen relevant informasjon fra valggjennomføring og opptelling.
    // 
    //    -
    // 
    // 
    // 
    
    
    
    // D Kontrolltiltak
    // D1 Kontrolltiltak
    // Redegjørelse for kontrolltiltak kommunen har gjennomført for å ivareta korrekt og sikker valggjennomføring.
    // 
    // Valgstyrets redegjørelse for kontrolltiltak
    // 
    //    Det er gjennomført stikkprøver ved alle skanninger og alle tellinger der det er avvik er talt flere ganger.
    // 
    // 
    // 
    // 
    
    
    
    // D2 Stikkprøvekontroll
    // Gjennomførte stikkprøvekontroller for å kontrollere systematiske avvik ved maskinell telling.
    // 
    // Gjennomførte stikkprøver
    //    Opptellingskategori                                                 Resultat
    //    Forhåndsstemmer - fra valglokalene                                  Ingen avvik på noen av stikkprøvene.
    //    Forhåndsstemmer - øvrige                                            Ingen avvik på noen av stikkprøvene.
    //    Forhåndsstemmer - telt etter kl. 17 dagen etter                     Ingen avvik på noen av stikkprøvene.
    //    valgdagen
    //    Valgtingsstemmer - fra valglokalene                                 Ingen avvik på noen av stikkprøvene.
    //    Valgtingsstemmer - øvrige                                           Ingen avvik på noen av stikkprøvene.
    // 
    // 
    // 
    // 
    // 
    // 
    // 
    
    
    
    // E Godkjenning
    // E1 Valgstyrets merknad
    // Merknad til valggjennomføringen i kommunen
    // 
    //    Protokollen ble godkjent i VS-sak 3/25 9. september 2025.
    // 
    // 
    // 
    // 
    
    
    
    // E2 Signering
    // To medlemmer av valgstyret signerer i forbindelse med godkjenningen av valget i kommunen.
    // 
    // Oppmøte
    //    Medlem                                                                                Rolle
    //    ANJA HOVLAND                                                                          Leder
    //    MAY HELEN HETLAND ERVIK                                                               Nestleder
    //    LEIF ERIK BROCH                                                                       Sekretær
    //    ROALD EIE                                                                             Medlem
    //    BEATE KYDLAND                                                                         Medlem
    //    MARI SKAARA OMDAL                                                                     Medlem
    //    BJØRNAR STAPNES                                                                       Medlem
    //    KNUT SIREVÅG                                                                          Medlem
    //    KARI JOHANNE MELHUS                                                                   Medlem
    //    HALVOR ØSTERMAN THENGS                                                                Medlem
    //    STEFFEN VINDHEIM                                                                      Varamedlem
    //    KENNETH PEDERSEN                                                                      Varamedlem
    //
    // 
    //              Dato: Tirsdag 09. september 2025
    // 
    // 
    // 
    //         Signatar: SIGNERT ELEKTRONISK
    //                        BJØRNAR STAPNES
    // 
    // 
    // 
    //         Signatar: SIGNERT ELEKTRONISK
    //                        HALVOR ØSTERMAN THENGS
    // 
    // 


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
            $number = regexAssertAndReturnMatch('/^ ' . $type_forkastelse_regex . ' \s*([0-9]* ?[0-9]*)\s*$/', $lines[$i++])[1];
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
