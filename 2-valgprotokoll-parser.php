<?php
/**
 * Parse 'valgprotokoll' PDFs
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

    // => Parse this file. Line by line.
    logInfo('Parsing [' . str_replace(__DIR__ . '/', '', $file) . '].');

    $obj = new stdClass();
    $lines = file($file);
    $i = 0;

    // --- START page 1

    // TODO: Handle multiple
    echo $i . ': ' . trim($lines[$i]) . "\n";
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

    $match = regexAssertAndReturnMatch('/^Kommune: \s*([A-Za-zÆØÅæøå]*)\s*$/', $lines[$i++]);
    $obj->municipality = $match[1];
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $match = regexAssertAndReturnMatch('/^Fylke: \s*([A-Za-zÆØÅæøå]*)\s*$/', $lines[$i++]);
    $obj->county = $match[1];
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
    $column_heading = 'Antall stemmegivninger';
    $column1 = 'Forkastet';
    $column2 = 'Godkjente';
    $sum_row1 = 'Totalt antall';
    $i = readTable($obj, $lines, $i, $current_heading, $column_heading, $column1, $column2, $sum_row1);


    var_dump($obj);

    assertLine($lines, $i, 'asdf');

    $unknown_lines = false;
    for (; $i < count($lines); $i++) {
        $unknown_lines = true;
    }

    if ($unknown_lines) {
        logError('Unknown lines in [' . $file . '].');
        // TODO: throw exception here!
    }

    // TODO: write file

    var_dump($obj);
}

function readTable(&$obj, &$lines, $i, $current_heading, $column_heading, $column1, $column2, $sum_row1) {
    $obj->numbers[$current_heading] = array();
    $i = assertLine_trim($lines, $i, $current_heading);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    $i = assertLine_trim($lines, $i, $column_heading);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    regexAssertAndReturnMatch('/^\s*' . $column1 . ' \s* ' . $column2 . '$/', $lines[$i++]);

    $readTable_twoColNumbers = function ($lines, $i) {
        // One line.
        $line = $lines[$i++];

        // Line 2
        if (strlen($lines[$i]) > 3) {
            $line .= str_replace("\r", '', $lines[$i++]);
        }

        // Line 3
        if (strlen($lines[$i]) > 3) {
            $line .= str_replace("\r", '', $lines[$i++]);
        }

        $line = str_replace("\n", '', $line);

        // Status:
        // - All on one line
        // - Numbers all the way to the right

        $match = regexAssertAndReturnMatch('/^(.*)\s+([0-9]* ?[0-9]+)\s\s\s+([0-9]* ?[0-9]+)\s*$/', $line);

        $i = removeLineIfPresent_andEmpty($lines, $i);

        return array(
            'i' => $i,
            'line' => $line,
            'text' => trim($match[1]),
            'numberColumn1' => $match[2],
            'numberColumn2' => $match[3]
        );
    };
    while (!str_starts_with($lines[$i], 'Totalt antall')) {
        $row = $readTable_twoColNumbers($lines, $i);
        $obj->numbers[$current_heading][$row['text']] = array(
            $column1 => $row['numberColumn1'],
            $column2 => $row['numberColumn2']
        );
        $i = $row['i'];
    }

    $obj->numbers[$current_heading][$sum_row1] = regexAssertAndReturnMatch('/^' . $sum_row1 . ' \s* ([0-9 ]*)$/', $lines[$i++]);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
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