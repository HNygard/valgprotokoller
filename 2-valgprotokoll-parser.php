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
    $text_heading = null;
    $column_heading = 'Antall stemmegivninger';
    $column1 = 'Forkastet';
    $column2 = 'Godkjente';
    $sum_row1 = 'Totalt antall';
    $sum_row2 = null;
    $i = readTable($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2);


    // ---- Table - B1.2 Behandlede forhåndsstemmegivninger
    $current_heading = 'B1.2 Behandlede forhåndsstemmegivninger';
    $text_heading = 'Forkastelser';
    $column_heading = 'Antall stemmegivninger';
    $column1 = 'Innenriks';
    $column2 = 'Utenriks';
    $sum_row1 = 'Godkjente forhåndsstemmegivninger (skal være lik sum av B2.1.1 og B2.2.1)';
    $sum_row2 = 'Totalt antall forhåndsstemmegivninger';
    $i = readTable($obj, $lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2);


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

function readTable(&$obj, &$lines, $i, $current_heading, $text_heading, $column_heading, $column1, $column2, $sum_row1, $sum_row2) {
    $obj->numbers[$current_heading] = array();
    $i = assertLine_trim($lines, $i, $current_heading);
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);

    if ($text_heading == null) {
        $i = assertLine_trim($lines, $i, $column_heading);
        $i = removeLineIfPresent_andEmpty($lines, $i);
        $header_length = strlen($lines[$i]);
        regexAssertAndReturnMatch('/^\s*' . $column1 . ' \s* ' . $column2 . '$/', $lines[$i++]);
    }
    else {
        $header_length = strlen($lines[$i]);
        regexAssertAndReturnMatch('/^' . $text_heading . '\s*' . $column1 . '\s*' . $column2 . '$/', trim($lines[$i++]));
    }
    $readTable_twoColNumbers = function ($lines, $i, $header_length, $sum_row1) {
        // One line.
        $row_lines = array($lines[$i++]);

        // Line 2
        if (strlen($lines[$i]) > 3 && !str_starts_with(trim($lines[$i]), $sum_row1)) {
            $row_lines[] = str_replace("\r", '', $lines[$i++]);
        }

        // Line 3
        if (strlen($lines[$i]) > 3 && !str_starts_with(trim($lines[$i]), $sum_row1)) {
            $row_lines[] = str_replace("\r", '', $lines[$i++]);
        }


        // Status:
        // - All on one line
        // - Numbers all the way to the right

        $row_line = '';
        var_dump($row_lines);
        foreach ($row_lines as $line) {
            if (strlen($line) >= ($header_length - 10)) {
                // -> Numbers line
                $match = regexAssertAndReturnMatch('/^(.*)\s+([0-9]* ?[0-9]+)\s\s\s+([0-9]* ?[0-9]+)\s*$/', $line);
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
            'numberColumn2' => $match[3]
        );
    };
    while (!str_starts_with(trim($lines[$i]), $sum_row1)) {
        $row = $readTable_twoColNumbers($lines, $i, $header_length, $sum_row1);
        $obj->numbers[$current_heading][$row['text']] = array(
            $column1 => $row['numberColumn1'],
            $column2 => $row['numberColumn2']
        );
        var_dump($obj);
        $i = $row['i'];
    }

    $obj->numbers[$current_heading][$sum_row1] = regexAssertAndReturnMatch('/^'
        . str_replace('(', '\(',
            str_replace(')', '\)',
                $sum_row1
            ))
        . ' \s* ([0-9 ]*)$/', trim($lines[$i++]));
    $i = removeLineIfPresent_andEmpty($lines, $i);
    $i = removeLineIfPresent_andEmpty($lines, $i);
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