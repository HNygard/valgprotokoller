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


set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$election_year = '2023';

$alle_partier = array(
    // Known values for this table. Improves reading.
    'Demokratene',
    'Liberalistene',
    'Senterpartiet',
    'Høyre',
    'Høgre',
    'Pensjonistpartiet',
    'Rødt',
    'Raudt',
    'Kristelig Folkeparti',
    'Kristeleg Folkeparti',
    'Arbeiderpartiet',
    'Arbeidarpartiet',
    'Fremskrittspartiet',
    'Framstegspartiet',
    'Partiet De Kristne',
    'Miljøpartiet De Grønne',
    'Miljøpartiet Dei Grøne',
    'SV - Sosialistisk Venstreparti',
    'Venstre',
    'Helsepartiet',

    'Nordmørslista',
    'Tverrpolitisk Folkeliste For Hele Kristiansand',
    'Xtra-lista',
    'Tverrpolitisk liste for Fremskrittspartiet, Høyre og Venstre',
    'Bygdalista i Gausdal',
    'By- og Landlista',

    // From https://valgresultat.no/api/2019/ko
    'Alliansen',
    'Árja',
    'Folkeaksjonen Nei til mer bompenger',
    'Kystpartiet',
    'Norges Kommunistiske Parti',
    'Piratpartiet',
    'Samefolkets Parti',
    'Ap og KrF samarbeidet',
    'Arbeiderpartiet og Kristelig Folkeparti',
    'Arbeiderpartiet-Senterpartiet-Kvitsøylisten',
    'Askøylisten',
    'By- og bygdelista',
    'Beiarn Bygdeliste',
    'Bygdelista i Austrheim',
    'Bindalslista',
    'BedreLarvik',
    'Blanke',
    'Buviklista',
    'Bygdelista for Flå',
    'Bygdalista',
    'Bygdelista i Stange',
    'Bygdalista i Vågå',
    'Utsira Bygdeliste',
    'Bygdelista i Vang',
    'Bygdeliste',
    'Bygdelista for Midtre Gauldal',
    'Bygdelista for Nordre Land',
    'Borgerlig fellesliste',
    'Bygdalista i Skjåk',
    'Bygdeliste for Tau og nordbygda',
    'Bygdelista Våler i Solør',
    'Bygdelista',
    'Bygdalista',
    'By- og Landlista ',
    'Bymiljølista',
    'By og land - tverrpolitisk liste',
    'Bypartiet',
    'Seter kretsliste',
    'Dovrelista',
    'Det Rette Parti',
    'Deanu Sámelistu - Samelista i Tana',
    'Evenes Tverrpolitiske Liste',
    'Flakstad distriktsliste',
    'Framtida Holtålen',
    'Fellesliste for Høyre og Fremskrittspartiet',
    'Fellesliste Høyre - Venstre',
    'Feministisk Initiativ',
    'Felleslista',
    'Felleslista Kristelig folkeparti/Frie borgerlige',
    'Utsira Fellesliste',
    'Folkelista for Etnedal',
    'Folkelista for Hareid kommune',
    'Folkelista',
    'FOLKELISTA',
    'Folkestyre ',
    'Folkets Røst by og bygdeliste',
    'Fremskrittspartiet og Sirdalslisten',
    'Felleslista SV - Sosialistisk Venstreparti og RØDT',
    'SV - Sosialistisk Venstreparti og Rødt',
    'Felleslista for Værøy',
    'Gran Bygdeliste',
    'Kautokeino Fastboendes liste',
    'Gratangen Fellespolitiske Liste',
    'Glad i Hurdal',
    'Gildeskållista',
    'Grue Bygdeliste',
    'Halsalista',
    'Hemsedal Bygdeliste',
    'Herøy Bygdeliste',
    'Hattfjelldal Bygdeliste',
    'Heimlista',
    'Høyre og Fremskrittspartiet',
    'Høyre, Kristelig Folkeparti og Venstre',
    'Hå-lista',
    'Hemnes lista',
    'Hovelista',
    'Hemnes samfunnsdemokratiske folkeparti',
    'Hinnøysiden Tverrpolitiske liste',
    'Kautokeino Flyttsameliste',
    'Karmøylista',
    'Karasjok lista',
    'Karlsøy Fellesliste',
    'Kongsberglista',
    'Johttiidsámiid listu',
    'Kommunelista',
    'Venstre/ Kristelig Folkeparti',
    'Bjoa Bygdeliste',
    'Kvænangen Bygdeliste',
    'Lofotlista (ja til kommunesammenslåing)',
    'Lomslista',
    'Lyngen Tverrpolitiske liste',
    'Melhuslista, Tverrpolitisk liste for hele Melhus kommune',
    'Meråker Tverrpolitiske Bygdeliste',
    'Bygdelista i Moskenes',
    'Melbu og omegn samarbeidsliste',
    'Moskenes Fellesliste',
    'Nannestad bygdeliste',
    'Nye Bygdelista',
    'Nes bygdeliste',
    'Nærmiljølista Ottestad',
    'Nei til bompenger i Tromsø',
    'Nei til bomring ',
    'Ny Kurs',
    'Optimum',
    'Samhold Lyngen',
    'Rødøy fellesliste',
    'Røst Samarbeidsliste',
    'Ringsaklista',
    'Felleslista for Sosialistisk Venstreparti og Rødt',
    'Røroslista',
    'Sammen For Sarpsborg',
    'Sámeálbmot Listu',
    'Saltdalslista',
    'Samfunnsdemokratane',
    'Samlingslista',
    'Samarbeidslisten',
    'Småbylista Orkland',
    'Samarbeidslista i Røyrvik',
    'Sosialdemokratene Hemnes',
    'Sentrumslista',
    'Selvstendighetspartiet',
    'Senjalista',
    'Sentrumslista',
    'Høyre og Kristelig Folkeparti',
    'Sammen for Bamble',
    'Sør-Fron Bygdaliste',
    'Samlingslista',
    'Søndre Land Bygdeliste',
    'Setesdalslista',
    'Samlingslista',
    'Sør-Odal Bygdeliste',
    'Sokndal Listo',
    'Solrenningslista',
    'Sotralista',
    'Fellesliste for Senterpartiet og Kristelig Folkeparti',
    'Samlingslista, Sp og uavhengige',
    'Liste for Rødt, Senterpartiet og partiuavhengige fiskere',
    'Smøla til Trøndelag',
    'Hvaler Styrbord',
    'SV - Sosialistisk Venstreparti og Miljøpartiet De Grønne',
    'Sosialistisk Fellesliste av SV og Raudt',
    'Fellesliste SV - Sosialistisk Venstreparti og Rødt',
    'Fellesliste for SV - Sosialistisk Venstreparti og Rødt',
    'Tverrpolitisk liste',
    'Tverrpolitisk liste i Porsanger TLP',
    'Tverrpolitisk liste for Giske',
    'Tverrpolitisk Kommuneliste',
    'Tverrpolitisk liste Sømna',
    'Tverrpolitisk seniorliste',
    'Lebesby Tverrpolitiske liste',
    'Tverrpolitisk Liste for Gratangen',
    'TVERRPOLITISK KVAMSØY SANDSØY VOKSA',
    'Sulalista',
    'Vegalista',
    'Volda-lista',
    'Værøylista',
    'Vefsn tverrpolitiske parti',
    'Venstre/ Miljøpartiet De Grønne',
    'Ålesundlista Tverrpolitisk liste for Ålesund',

    // From curl https://valgresultat.no/api/2023/ko|jq -r '.partier[].id.navn'|sort|uniq
    'Alliansen - Alternativ for Norge',
    'Andre',
    'Andre2',
    'Arendalslista',
    'Åsnes Bygdeliste',
    'BÅTSFJORDLISTA',
    'Bergenslisten',
    'Bjerkreim Bygdeliste',
    'Bjerkreimlista',
    'Bygdelista Flesberg',
    'Bygdelista Framover',
    'Bygdelista i Måsøy',
    'Bygdelista Valle',
    'Bygdeliste for hele Osen kommune',
    'Bygdelisten',
    'Bykle Bygdeliste',
    'Din Stemme',
    'Felleslista for Ringvassøy, Reinøy og Rebbenesøy',
    'Felleslista Hasvik',
    'Felleslista Høyre-KrF-Venstre',
    'Felleslista i Gamvik kommune',
    'Felleslista Rødt SV',
    'Fellesliste for Arbeiderpartiet og Venstre',
    'Fellesliste for Høyre og Venstre',
    'Fellesliste for Kristelig Folkeparti og Venstre',
    'Fellesliste for Tverrpolitisk Liste, Samefolkets Parti, SP og H',
    'Fellesliste FrP/ Høyre',
    'Fellesliste Høyre og Venstre',
    'Fellesliste Raudt og Sosialistisk Venstreparti',
    'Fellesliste Sosialistisk Venstreparti og Rødt',
    'Fellesliste SV/RØDT Måsøy',
    'Fellesliste Venstre og Senterpartiet',
    'Flekkefjords Vel ',
    'Folkelista i Loppa',
    'Folkelista Lyngen',
    'Folkestyrelisten',
    'Folkestyret- listen',
    'Folkestyret-listen',
    'Folkets Parti',
    'Folkets Røst',
    'Folkets Stemme',
    'Folk i Drammen',
    'Folldalslista',
    'Generasjonspartiet',
    'Grønt Hemnes - fellesliste for V og MDG',
    'Guovdageainnu Sámilistu',
    'Hol Bygdeliste',
    'Høyre og Kristelig folkeparti',
    'Industri- og Næringspartiet',
    'Kárášjoga sámelistu',
    'Karlsøy Pensjonistliste',
    'Kleppelista',
    'Konservativt',
    'KrF/Tverrpolitisk',
    'Leka Bygdeliste',
    'Marker Bygdeliste',
    'Melhuslista',
    'Meløy Frie Folkevalgte',
    'Nærmiljølista',
    'NEI TIL BOMPENGER I TROMSØ',
    'NOREXIT',
    'Norgesdemokratene',
    'Partiet Mot Bompenger',
    'Partiet Nord',
    'Partiet Sentrum',
    'Rettferd og Frihets partiet',
    'Røyrvik Bygdeliste',
    'Samlingslista i Austrheim',
    'Sammen for Sørøya',
    'Småbypartiet Orkland',
    'Sørlandspartiet',
    'Sosialistisk Forum',
    'Sosialistisk Venstreparti og Rødt',
    'Sotralista - TVØ',
    'SV/Rødt',
    'Tjeldsund Tverrpolitiske Liste',
    'Tverrpolitisk fellesliste',
    'Utsiralista',
    'Venstre og KrF',
    'Verdalslista',
    'Vindafjordlista',
    'Vinje tverrpolitiske bygdeliste',
);

$kommunale_domener = file(__DIR__ . '/kommunale-domener.csv');
$domain_to_name = array();
foreach ($kommunale_domener as $line) {
    $line = explode(',', trim($line));
    if (!str_contains($line[1], 'http://') && !str_contains($line[1], 'https://')) {
        // -> Plain domain
        // .name.kommune.no
        $domain_to_name['.' . $line[1]] = $line[0];
        // https://name.kommune.no or http://name.kommune.no
        $domain_to_name['://' . $line[1]] = $line[0];
    }
    else {
        // -> Full address
        $domain_to_name[$line[1]] = $line[0];
    }
}

$lines = file(__DIR__ . '/docs/data-store/nynorsk/nynorsk-til-bokmål.csv');
$nynorskToBokmaal = array();
foreach ($lines as $line) {
    if (empty(trim($line))) {
        continue;
    }
    $line = trim($line);
    $nynorskString = explode("\t", $line)[0];
    $bokmaalString = explode("\t", $line)[1];
    $nynorskToBokmaal[$nynorskString] = $bokmaalString;
}

// Path contains:
$ignore_files = array(// Svarbrev
);

$files_written = array();

$files = getDirContents(__DIR__ . '/docs/data-store/pdfs-' . $election_year);
foreach ($files as $file) {
    if (!str_ends_with($file, '.layout.txt')) {
        continue;
    }

    foreach ($ignore_files as $ignore_file) {
        if (str_contains($file, $ignore_file)) {
            continue 2;
        }
    }

    if (isset($argv[1]) && $argv[1] != 'throw') {
        $file_stripped = str_replace(__DIR__ . '/docs/data-store/pdfs-' . $election_year . '/', '', $file);
        $file2 = $argv[1];
        $file2 = str_replace('http://', '', $file2);
        $file2 = str_replace('https://', '', $file2);
        $file2 = str_replace('/', '-', $file2);
        if (!str_contains($file_stripped, $file2)
            && !str_contains($file_stripped, str_replace('%20', ' ', $file2))) {
            continue;
        }
    }

//    if (!str_contains(strtolower($file), 'ski.kommune.no') && !str_contains(strtolower($file), 'aukra')
//        && !str_contains(strtolower($file), 'trysil')
//        && !str_contains(strtolower($file), 'evenes')
//        && !str_contains(strtolower($file), 'vestnes')
//    ) {
//        continue;
//    }

    $obj = new stdClass();
    try {
        parseFile_andWriteToDisk($obj, $file);
    }
    catch (Exception $e) {
        $obj->error = true;
        $obj->errorMessage = $e->getMessage() . "\n\n" . str_replace(__DIR__ . '/', '', $e->getTraceAsString());

        // Removing all folders
        $dir = __DIR__;
        $dir_last = __DIR__;
        while (dirname($dir) != $dir_last) {
            $obj->errorMessage = str_replace(dirname($dir), '', $obj->errorMessage);
            $dir_last = dirname($dir);
            $dir = dirname($dir);
        }


        logErrorWithStacktrace('Error parsing [' . $file . '].', $e);


        if (isset($argv[1]) && $argv[1] != 'throw') {
            $file_info_file = str_replace('.layout.txt', '.json', $file);
            if (file_exists($file_info_file)) {
                $file_info = json_decode(file_get_contents($file_info_file));

                if ($file_info->url == $argv[1]
                    || $file_info->url == str_replace('%20', '', $argv[1])) {
                    throw $e;
                }
            }
        }

        if (isset($argv[1]) && $argv[1] == 'throw' && $obj->documentType == 'valgprotokoll') {
            throw $e;
        }
    }


    if (isset($obj->election) && isset($obj->county)) {
        $data_dir = __DIR__ . '/docs/data-store/json/' . $obj->election . '/' . $obj->county;
        if (!file_exists($data_dir)) {
            mkdir($data_dir, 0777, true);
        }
        $json_file = $data_dir . '/' . $obj->municipality . '.json';
        if (isset($files_written[$json_file])) {
            $first_file = $files_written[$json_file];
            if (isset($first_file->otherSources)) {
                $obj->otherSources = $first_file->otherSources;
            }
            unset($first_file->otherSources);
            $obj->otherSources[] = getDiffBetweenObjects($obj, $first_file);
        }

        file_put_contents(
            $json_file,
            json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE)
        );
        $files_written[$json_file] = $obj;
    }
    elseif (isset($obj->documentType)) {
        $data_dir = __DIR__ . '/docs/data-store/json/' . $obj->documentType . '/';
        if (!file_exists($data_dir)) {
            mkdir($data_dir, 0777, true);
        }
        file_put_contents(
            $data_dir . '/'
            . (isset($obj->municipalityNameFromUrl) ? $obj->municipalityNameFromUrl . ' - ' : '')
            . basename(str_replace('.layout.txt', '', $file))
            . '.json',
            json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE)
        );
    }
    elseif ($obj->error && $obj->errorMessage == 'No content. Might contain scanned image.') {
        $data_dir = __DIR__ . '/docs/data-store/json/error-no-content';
        if (!file_exists($data_dir)) {
            mkdir($data_dir, 0777, true);
        }
        file_put_contents(
            $data_dir . '/' . basename(str_replace('.layout.txt', '', $file)) . '.json',
            json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE)
        );
    }
    elseif ($obj->error) {
        $data_dir = __DIR__ . '/docs/data-store/json/error';
        if (!file_exists($data_dir)) {
            mkdir($data_dir, 0777, true);
        }
        file_put_contents(
            $data_dir . '/' . basename(str_replace('.layout.txt', '', $file)) . '.json',
            json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE)
        );
    }
    else {
        var_dump($obj);
        throw new Exception('What? Dont know where to write this thing.');
    }

    logInfo('.');
    logInfo('.');
    logInfo('.');
}

function parseFile_andWriteToDisk(&$obj, $file) {
    global $domain_to_name, $election_year;

    // => Parse this file. Line by line.
    logInfo('Parsing [' . str_replace(__DIR__ . '/', '', $file) . '].');

    $obj->localSource = str_replace(__DIR__ . '/', '', $file);

    $file_info_file = str_replace('.layout.txt', '.json', $file);
    if (file_exists($file_info_file)) {
        $file_info = json_decode(file_get_contents($file_info_file));

        $obj->url = $file_info->url;
        $obj->downloadTime = $file_info->downloadTime;

        foreach ($domain_to_name as $domain => $name) {
            if (str_contains($obj->url, $domain)) {
                $obj->municipalNameFromUrl = $name;
            }
        }
    }
    else {
        $obj->url = '<missing>';
        $obj->downloadTime = null;
    }

    $pdfInfo = str_replace('.layout.txt', '.pdfinfo.txt', $file);
    if (file_exists($pdfInfo)) {
        $obj->pdfMetaData = file_get_contents($pdfInfo);
    }

    $file_content = file_get_contents($file);

    if (strlen(trim($file_content)) == 0) {
        $obj->error = true;
        $obj->errorMessage = 'No content. Might contain scanned image.';
        logInfo('---> NO CONTENT.');
        return;
    }


    // :: Check for text in the start of the file
    // We are ignoring known headings.
    if (str_contains(substr($file_content, 0, 100), 'Kretsrapport valglokale')) {
        $obj->documentType = 'kretsrapport_valglokale';
        $obj->error = false;
        logInfo('Ignoring. Kretsrapport valglokale.');
        return;
    }
    if (str_contains(substr($file_content, 0, 100), 'Ny kandidatrangering per parti')) {
        $obj->documentType = 'ny_kandidatrangering_per_parti';
        $obj->error = false;
        logInfo('Ignoring. Ny kandidatrangering per parti.');
        return;
    }
    if (str_contains(substr($file_content, 0, 200), 'Valgoppgjør for ')) {
        $obj->documentType = 'valgoppgjør';
        $obj->error = false;
        logInfo('Ignoring. Valgoppgjør for .');
        return;
    }
    if (str_contains(substr($file_content, 0, 100), 'Valgdeltakelse')) {
        $obj->documentType = 'valgdeltakelse';
        $obj->error = false;
        logInfo('Ignoring. Valgdeltakelse.');
        return;
    }
    if (str_contains(substr($file_content, 0, 100), 'Valprotokoll for fylkesvalstyret')
        || str_contains(substr($file_content, 0, 100), 'Valgprotokoll for fylkesvalgstyret')) {
        $obj->documentType = 'valgprotokoll-fylkesvalgstyret';
        $obj->error = false;
        logInfo('Ignoring. Valkesvalgstyret.');
        return;
    }

    /*
     * DIDN'T WORK - Should maybe pre clean these type of file instead of cluttering the code
     *  if (str_starts_with(trim($file_content), '* VALG')) {
         // Remove some stuff at the beginning of the file.
         // docs/data-store/pdfs/elections-no.github.io-docs-2019-Troms_og_Finnmark-Kvænangen kommune, Troms og Finnmark fylke - kommunestyrevalget.pdf.layout.txt
         logInfo('Removing "* VALG" from first line.');
         $file_content = trim(substr(trim($file_content), strlen('* VALG')));
     }*/

    $nynorsk = str_contains($file_content, 'Valprotokoll for valstyret');
    if ($nynorsk) {
        $obj->language = 'nn-NO';
        $obj->languageName = 'Norwegian, Nynorsk';

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
    $match = regexAssertAndReturnMatch('/^Valgdistrikt: \s*([A-Za-zÆØÅæøåá \-]*)\s*$/', $lines[$i++]);
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


    return;
}

function readTableRow($lines, $i, $header_length, $start_of_row_keywords, $table_ending, $rowRegex, $returnFunction) {
    // One line.
    $row_lines = array($lines[$i++]);

    if ($start_of_row_keywords != 'one_line_for_each') {
        // :: Line 2

        $start_with_keyword = false;
        foreach ($start_of_row_keywords as $keyword) {
            if (str_starts_with(trim($lines[$i]), $keyword)) {
                $start_with_keyword = true;
            }
        }
        if (trim($row_lines[0]) == '' && $start_with_keyword) {
            $row_lines[] = str_replace("\r", '', $lines[$i++]);
        }

        if (
            // Stop picking up lines, if there are empty lines
            strlen($lines[$i]) > 3

            // Is the next line a key word?
            && !$start_with_keyword

            // This is not the last line?
            && !str_starts_with(trim($lines[$i]), $table_ending)) {
            $row_lines[] = str_replace("\r", '', $lines[$i++]);
        }

        // :: Line 3

        foreach ($start_of_row_keywords as $keyword) {
            if (str_starts_with(trim($lines[$i]), $keyword)) {
                $start_with_keyword = true;
            }
        }
        if (
            // Stop picking up lines, if there are empty lines
            strlen($lines[$i]) > 3

            // Is the next line a key word?
            && !$start_with_keyword

            // This is not the last line?
            && !str_starts_with(trim($lines[$i]), $table_ending)) {
            $row_lines[] = str_replace("\r", '', $lines[$i++]);
        }
    }


    // Status:
    // - All on one line
    // - Numbers all the way to the right

    $row_line = '';
    foreach ($row_lines as $line) {
        if (strlen($line) >= ($header_length - 10)) {
            // -> Numbers line
            $match = regexAssertAndReturnMatch($rowRegex, $line);
            $row_line .= trim($match[1]);
        }
        else {
            $row_line .= $line;
        }
    }

    $i = removeLineIfPresent_andEmpty($lines, $i);

    $row_line = str_replace("\n", '', $row_line);
    $row_line = trim($row_line);

    if (!isset($match)) {
        echo '10 last lines: ' . chr(10);
        for ($j = $i - 10; $j <= $i; $j++) {
            echo 'lines[' . $j . ']: ' . $lines[$j] . chr(10);
        }
        throw new Exception('Didn\'t find the row: ' . chr(10) . $row_line);
    }

    return $returnFunction($i, $row_lines, $row_line, $match);
}

function readTable_twoColumns(&$obj, &$lines, $i, $current_heading, $text_heading, $column_heading,
                              $column1, $column2,
                              $sum_row1, $sum_row2, $table_ending, $start_of_row_keywords = array()) {
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
    while (!str_starts_with(trim($lines[$i]), $table_ending)) {

        $rowRegex = '/^(.*)\s+\s\s(([0-9]* ?[0-9]+)|(\—))\s\s\s+([0-9]* ?[0-9]+)\s*$/';
        $returnFunction = function ($i, $row_lines, $row_line, $match) {
            return array(
                'i' => $i,
                'line' => $row_lines,
                'text' => $row_line,
                'numberColumn1' => cleanFormattedNumber($match[2]),
                'numberColumn2' => cleanFormattedNumber($match[5])
            );
        };
        if ($text_heading == 'Mandat nr.:') {
            // Not the normal name/party and two number columns
            //     Number       Party        Number
            $rowRegex = '/^\s*(([0-9]* ?[0-9]+)|(\—))\s+\s\s(.*)\s\s\s+([0-9]* ?[0-9]* ?[0-9]+,[0-9]*)\s*$/';
            $returnFunction = function ($i, $row_lines, $row_line, $match) {
                return array(
                    'i' => $i,
                    'line' => $row_lines,
                    'text' => cleanFormattedNumber($row_line),
                    'numberColumn1' => trim($match[4]),
                    'numberColumn2' => (float)str_replace(',', '.', cleanFormattedNumber($match[5]))
                );
            };
            $start_of_row_keywords = "one_line_for_each";

            if (str_contains($lines[$i], $text_heading)) {
                // -> Next page. New heading.
                regexAssertAndReturnMatch('/^' . $text_heading . '\s*' . $column1 . '\s*' . $column2 . '$/', trim($lines[$i++]));
            }
        }

        $row = readTableRow($lines, $i, $header_length, $start_of_row_keywords, $table_ending,
            $rowRegex,
            $returnFunction);
        $obj->numbers[$current_heading][$row['text']] = array(
            str_replace(':', '', $column1) => $row['numberColumn1'],
            str_replace(':', '', $column2) => $row['numberColumn2']
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

function readTable_threeColumns(&$obj, &$lines, $i, $current_heading, $text_heading,
                                $column1, $column2, $column3,
                                $table_ending, $start_of_row_keywords) {
    $obj->numbers[$current_heading] = array();
    $i = assertLine_trim($lines, $i, $current_heading);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $header_length = strlen($lines[$i]);
    regexAssertAndReturnMatch('/^' . $text_heading . '\s*' . $column1 . '\s*' . $column2 . '\s*' . $column3 . '$/', trim($lines[$i++]));

    while (!str_starts_with(trim($lines[$i]), $table_ending)) {
        $row = readTableRow($lines, $i, $header_length, $start_of_row_keywords, $table_ending,
            '/^(.*)\s+\s\s(([0-9]* ?[0-9]+)|(\—))\s\s\s+([0-9]* ?[0-9]+)\s\s\s+(\-?[0-9]* ?[0-9]+)\s*$/',
            function ($i, $row_lines, $row_line, $match) {
                return array(
                    'i' => $i,
                    'line' => $row_lines,
                    'text' => $row_line,
                    'numberColumn1' => $match[2],
                    'numberColumn2' => $match[5],
                    'numberColumn3' => $match[6]
                );
            });

        $obj->numbers[$current_heading][$row['text']] = array(
            $column1 => cleanFormattedNumber($row['numberColumn1']),
            $column2 => cleanFormattedNumber($row['numberColumn2']),
            $column3 => cleanFormattedNumber($row['numberColumn3'])
        );
        $i = $row['i'];
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
        echo '[' . $i . '] ' . ord($string[$i]) . ' - ' . $string[$i] . "\n";
    }
}

/**
 * Remove thosand sep + "type cast":
 * "12 123" => "12123" => 12123
 *
 * Special case '-' is just returned.
 *
 * @param $stringNumber
 * @return int|string
 */
function cleanFormattedNumber($stringNumber) {
    if ($stringNumber == '-') {
        return $stringNumber;
    }

    $stringNumber = str_replace(' ', '', $stringNumber);
    if (is_numeric($stringNumber) && !str_contains($stringNumber, ',')) {
        return (int)$stringNumber;
    }

    return $stringNumber;
}


function getDiffBetweenObjects($document, $document_new) {
    // Check key by key
    $new_key_values = array();
    foreach ($document_new as $key => $value) {
        if ($value == null) {
            continue;
        }
        if (!isset($document->$key)) {
            // -> New key
            $new_key_values[$key] = $value;
            continue;
        }

        if (is_array($value)) {
            // -> Diff on keys
            foreach ($value as $key2 => $value2) {
                if ($value2 == null) {
                    continue;
                }

                if (!isset($document->$key[$key2]) || $document->$key[$key2] != $value2) {
                    // -> Non existing key i orignal document OR changed value
                    if (!isset($new_key_values[$key])) {
                        $new_key_values[$key] = array();
                    }
                    $new_key_values[$key][$key2] = $value2;
                }
            }
        }
        /* elseif (is_object($document->$key)) {
             $diff = getDiffBetweenObjects($document->$key, $value);
             if (count($diff) > 0) {
                 $new_key_values[$key] = $diff;
             }
         }*/
        elseif ($document->$key != $value) {
            $new_key_values[$key] = $value;
        }
    }

    return $new_key_values;
}