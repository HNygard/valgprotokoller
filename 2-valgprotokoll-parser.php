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

$election_year = '2025';

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
    $line = trim($line, "\n\r");
    $line = str_replace('    ', "\t", $line);
    $nynorskString = explode("\t", $line)[0];
    $bokmaalString = explode("\t", $line)[1];
    $nynorskToBokmaal[$nynorskString] = $bokmaalString;
}

// Path contains:
$ignore_files = array(
    // Svarbrev
    'pdfs-2023/INNSYN---entityId=1818-heroy-i-nordland-kommune&threadId=valgprotokoll_2023%2C_her%C3%B8y_kommune_%28nordland%29&attachment=2023-09-13_082308+-+IN+-+att+1-cd5c12a65e0bb674811533ca4bfb1e0e.pdf.pdf.layout.txt',
    'pdfs-2023/INNSYN---entityId=1865-vagan-kommune&threadId=valgprotokoll_2023%2C_v%C3%A5gan_kommune&attachment=2023-09-14_081146+-+IN+-+att+1-95a27584948d5b6310c9c9bc3f5edad2.pdf.pdf.layout.txt',
);

$files_written = array();

$summary_numbers = array();

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

    if (isset($obj->documentType)) {
        $data_dir_for_document_type = __DIR__ . '/docs/data-store/json/' . $obj->documentType . '/';
        $file_name_for_document_type = $data_dir_for_document_type . '/'
        . (isset($obj->municipalityNameFromUrl) ? $obj->municipalityNameFromUrl . ' - ' : '')
        . basename(str_replace('.layout.txt', '', $file))
        . '.json';
    }

    $data_dir = __DIR__ . '/docs/data-store/json/error-no-content';
    if (!file_exists($data_dir)) {
        mkdir($data_dir, 0777, true);
    }
    $error_json_file__no_content = $data_dir . '/' . basename(str_replace('.layout.txt', '', $file)) . '.json';
    $data_dir = __DIR__ . '/docs/data-store/json/error-unknown-doc-type/' . $election_year;
    if (!file_exists($data_dir)) {
        mkdir($data_dir, 0777, true);
    }
    $error_json_file__unknown_doc_type = $data_dir . '/' . basename(str_replace('.layout.txt', '', $file)) . '.json';
    $data_dir = __DIR__ . '/docs/data-store/json/error/' . $election_year;
    if (!file_exists($data_dir)) {
        mkdir($data_dir, 0777, true);
    }
    $error_json_file = $data_dir . '/' . basename(str_replace('.layout.txt', '', $file)) . '.json';

    $json_file = null;
    if (isset($obj->election) && isset($obj->county) && ($obj->documentType != 'valgprotokoll-fylkesvalgstyret')) {
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

        unlink_if_exists($file_name_for_document_type);
        unlink_if_exists($error_json_file__no_content);
        unlink_if_exists($error_json_file__unknown_doc_type);
        unlink_if_exists($error_json_file);
    }
    elseif (isset($obj->election) && isset($obj->county) && ($obj->documentType == 'valgprotokoll-fylkesvalgstyret')) {
        $data_dir = __DIR__ . '/docs/data-store/json/' . $obj->election;
        if (!file_exists($data_dir)) {
            mkdir($data_dir, 0777, true);
        }
        $json_file = $data_dir . '/' . $obj->county . '.json';
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

        unlink_if_exists($file_name_for_document_type);
        unlink_if_exists($error_json_file__no_content);
        unlink_if_exists($error_json_file__unknown_doc_type);
        unlink_if_exists($error_json_file);
    }
    elseif (isset($obj->documentType)) {
        if (!file_exists($data_dir_for_document_type)) {
            mkdir($data_dir_for_document_type, 0777, true);
        }
        file_put_contents(
            $file_name_for_document_type,
            json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE)
        );
    }
    elseif ($obj->error && $obj->errorMessage == 'No content. Might contain scanned image.') {
        file_put_contents(
            $error_json_file__no_content,
            json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE)
        );
    }
    elseif ($obj->error && $obj->errorMessage == 'Unknown file.') {
        file_put_contents(
            $error_json_file__unknown_doc_type,
            json_encode($obj, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE)
        );
    }
    elseif ($obj->error) {
        file_put_contents(
            $error_json_file,
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

// Write summary to file
$summary_file = __DIR__ . '/docs/data-store/status-' . $election_year . '.json';
// Count total, success, and error files
$summary_numbers['files_total'] = count($files);
$summary_numbers['files_successful'] = 0;
$summary_numbers['files_error'] = 0;
$summary_numbers['error_messages']  = array();

foreach ($files_written as $obj) {
    if (isset($obj->error) && $obj->error) {
        $summary_numbers['files_error']++;

        if (isset($obj->errorMessage)) {
            if (!isset($summary_numbers['error_messages'][$obj->errorMessage])) {
                $summary_numbers['error_messages'][$obj->errorMessage] = 0;
            }
            $summary_numbers['error_messages'][$obj->errorMessage]++;
        }

    }
    else {
        $summary_numbers['files_successful']++;
    }
}

file_put_contents($summary_file, json_encode((object)$summary_numbers, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));

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
    if (str_contains(substr($file_content, 0, 100), 'Signering av protokoll for stortingsvalget 2025')) {
        $obj->documentType = 'valgprotokoll-digitalt-signert-st2025';
        $obj->error = false;
        logInfo('Ignoring. Digitalt signert valgprotokoll.');
        return;
    }
    if (str_contains(substr($file_content, 0, 100), 'Valprotokoll for fylkesvalstyret')
        || str_contains(substr($file_content, 0, 100), 'Valgprotokoll for fylkesvalgstyret')) {
        require_once __DIR__ . '/2-valgprotokoll-parser.php__valgprotokoll-fylkesvalgtinget.php';

        readValgprotokollFylkesvalgting($file_content, $obj, $election_year);
        return;
    }
    if (str_contains(substr($file_content, 0, 100), 'Valprotokoll for valstyret')
        || str_contains(substr($file_content, 0, 100), 'Valgprotokoll for valgstyret')) {
        require_once __DIR__ . '/2-valgprotokoll-parser.php__valgprotokoll-kommune.php';

        readValgprotokollKommune($file_content, $obj, $election_year);
        return;
    }
    if (
        !isset($obj->documentType)
        && str_contains(substr($file_content, 0, 100), 'Stortingsvalget 2025')
        && (
            str_contains(substr($file_content, 0, 100), 'Protokoll for valstyret')
            || str_contains(substr($file_content, 0, 100), 'Protokoll for valgstyret')
        )
    ) {
        require_once __DIR__ . '/2-valgprotokoll-parser.php__valgprotokoll-stortinget.php';

        readValgprotokollStortinget($file_content, $obj, $election_year);
        return;
    }


    $obj->error = true;
    $obj->errorMessage = 'Unknown file.';


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
            (
                // Stop picking up lines, if there are empty lines
                strlen($lines[$i]) > 3

                // 2023, the g in "Stemmegivningen er ikke kommet inn til valgstyret innen kl. 17 dagen etter 10-1 (1) g"
                // got it's own line
                || trim($lines[$i]) == 'g'
            )

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
            if ($i == $j) {
                echo '-> LINES[' . $j . ']: ';
            }
            else {
                echo '   lines[' . $j . ']: ';
            }
            echo $lines[$j] . chr(10);
        }
        echo "Start of row keywords .... : " . json_encode($start_of_row_keywords) . "\n";
        echo "Row regex used ........... : $rowRegex\n";
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

            if (empty($lines[$i])) {
                // Skip empty line
                $i++;
            }
            if (str_contains($lines[$i], $text_heading)) {
                // -> Next page. New heading.
                regexAssertAndReturnMatch('/^' . $text_heading . '\s*' . $column1 . '\s*' . $column2 . '$/', trim($lines[$i++]));
            }
            if (empty($lines[$i])) {
                // Skip empty line
                $i++;
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


function readTable_threeColumns_subheadings(&$obj, &$lines, $i, $current_heading, $text_heading,
                                            $column1, $column2, $column3,
                                            $table_ending, $start_of_row_keywords, $subheadings) {
    $obj->numbers[$current_heading] = array();
    $i = assertLine_trim($lines, $i, $current_heading);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $header_length = strlen($lines[$i]);
    regexAssertAndReturnMatch('/^' . $text_heading . '\s*' . $column1 . '\s*' . $column2 . '\s*' . $column3 . '$/', trim($lines[$i++]));

    $current_subheading = "-ingen-underoverskrift-";
    while (!str_starts_with(trim($lines[$i]), $table_ending)) {
        foreach ($subheadings as $subheading) {
            if (
                str_starts_with(trim($lines[$i]), $subheading)
                || str_starts_with(trim($lines[$i + 1]), $subheading)

            ) {
                $i = removeLineIfPresent_andEmpty($lines, $i);
                $current_subheading = $subheading;
                $i = assertLine_trim($lines, $i, $subheading);
                $i = removeLineIfPresent_andEmpty($lines, $i);
            }
        }

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

        if (!isset($obj->numbers[$current_heading][$current_subheading])) {
            $obj->numbers[$current_heading][$current_subheading] = array();
        }

        $obj->numbers[$current_heading][$current_subheading][$row['text']] = array(
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

    // Switch U+2212 (minus sign) to normal minus used in JSON numbers
    $stringNumber = str_replace('−', '-', $stringNumber);
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

function unlink_if_exists($file) {
    if (file_exists($file)) {
        unlink($file);
    }
}