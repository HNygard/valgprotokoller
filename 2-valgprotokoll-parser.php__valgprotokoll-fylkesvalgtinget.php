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
        throw new Exception('Unknown lines.');
    }


    return $obj;
}