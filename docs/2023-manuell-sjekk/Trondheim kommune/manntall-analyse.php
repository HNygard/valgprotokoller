<?php

$lines = explode("\n", file_get_contents(__DIR__ . '/Stemmegivninger Trondheim 2023 sladdet.csv'));

$stemmer = 0;
$stemmemottakere = array();
$stemmer_per_time = array();
$stemmemottakere_stemmested = array();
$første_stemme_per_dag = array();
$siste_stemme_per_dag = array();
foreach($lines as $i => $line) {
	if (trim($line) == '') {
		continue;
	}

	$cells = explode("\t", trim($line));
	if (count($cells) != 3) {
		var_dump($cells);
		throw new Exception($i . ' Too many cells.');
	}
	if ($i == 0) {
		if (
			$cells[0] != 'Stemmegivningstidspunkt'
			|| $cells[1] != 'Stemmested'
			|| $cells[2] != 'Stemmemottaker'
		) {
			var_dump($cells);
			throw new Exception($i . ' Heading wrong.');
		}
		continue;
	}
	
	// Totalt antall stemmer
	$stemmer++;
	
	// Totalt antall stemmer per stemmemottaker
	if (!isset($stemmemottakere[$cells[2]])) {
		$stemmemottakere[$cells[2]] = 0;
	}
	$stemmemottakere[$cells[2]]++;
	
	// Stemmer per time per stemmemottaker
	$per_time = $cells[2] . ' - ' 
		. date('Y-m-d', strtotime($cells[0]))
		. ' kl '
		. date('H', strtotime($cells[0]));
	if (!isset($stemmer_per_time[$per_time])) {
		$stemmer_per_time[$per_time] = 0;
	}
	$stemmer_per_time[$per_time]++;
	
	// Stemmested per stemmemottaker
	if (!isset($stemmemottakere_stemmested[$cells[2]])) {
		$stemmemottakere_stemmested[$cells[2]] = array();
	}
	if (!isset($stemmemottakere_stemmested[$cells[2]][$cells[1]])) {
		$stemmemottakere_stemmested[$cells[2]][$cells[1]] = 0;
	}
	$stemmemottakere_stemmested[$cells[2]][$cells[1]]++;
	
	// Første/siste stemme
	$time = strtotime($cells[0]);
	if ($cells[1] != 'Konvolutt - sentral registrering') {
		if (!isset($første_stemme[date('Y-m-d', $time)])) {
			$første_stemme[date('Y-m-d', $time)] = array(time(), '');
			$siste_stemme[date('Y-m-d', $time)] = array(0, '');
		}
		if ($første_stemme[date('Y-m-d', $time)][0] > $time) {
			$første_stemme[date('Y-m-d', $time)] = array($time, $cells);
		}
		if ($siste_stemme[date('Y-m-d', $time)][0] < $time) {
			$siste_stemme[date('Y-m-d', $time)] = array($time, $cells);
		}
	}
}

function cleanprint($string) {
	$cell = explode("\t", $string);
	return $cell[0] . "    " . str_pad($cell[1], 23) . $cell[2];
}

echo "----------- ANALYSE - KRYSS I MANNTALLET TIL TRONDHEIM KOMMUNE - 2023 ------------\n";
echo "\n\n";
echo "Kryss i manntallet - CSV-fil ........................... : $stemmer\n";
echo "Kryss i manntallet - Valgprotokoll valgresultater.no ... : 111009\n";
echo "Antall stemmemottakere ................................. : " . count($stemmemottakere) . "\n";


echo "\n\n";
echo "-- 10 tilfeldige linjer fra datasettet\n";
echo cleanprint($lines[0]) . "\n";
echo cleanprint($lines[1]) . "\n";
echo cleanprint($lines[2]) . "\n";
echo cleanprint($lines[2000]) . "\n";
echo cleanprint($lines[4000]) . "\n";
echo cleanprint($lines[10000]) . "\n";
echo cleanprint($lines[20000]) . "\n";
echo cleanprint($lines[30000]) . "\n";
echo cleanprint($lines[40000]) . "\n";
echo cleanprint($lines[100000]) . "\n";

/*
echo "\n\n";
foreach($stemmemottakere as $stemmemottaker => $count) {
	echo "$stemmemottaker - $count\n";
}
*/

echo "\n\n";

echo "-- Stemmemotakere som i løpet av en klokketime krysset over 80 velgere:\n";
ksort($stemmer_per_time);
foreach($stemmer_per_time as $stemmemottaker => $count) {
	if ($count > 80) {
		$stemmesteder_per_stemmemottaker = $stemmemottakere_stemmested[explode(' ', $stemmemottaker)[0]];
		$strings = array();
		foreach ($stemmesteder_per_stemmemottaker as $stemmested => $count2) {
			$strings[] = $stemmested . ' ('.$count2.')';
		}
		$tekst = "Stemmemottaker $stemmemottaker" 
			. " - ". str_pad($count, 4, ' ', STR_PAD_LEFT) . " stemmer"
			. " (" . number_format(round($count / 60, 4), 4, ',', ' ') . " stemme/min)" 
			. " (" . str_pad(number_format(round(3600 / $count, 4), 4, ',', ' '), 7, ' ', STR_PAD_LEFT) . " sekunder/stemme)" 
			. " - " . implode(', ', $strings)
			. "\n";
			
		if (str_contains(implode('', $strings), 'Konvolutt - sentral registrering')) {
			$gruppe2[] = $tekst;
		}
		else {
			$gruppe1[] = $tekst;
		}
	}
}
echo implode('', $gruppe1);
echo implode('', $gruppe2);

echo "\n\n";

echo "-- Første og siste stemme per dag:\n";
ksort($første_stemme);
echo "Dato        Første     Siste               Sted første                                                      Sted siste\n";
foreach($første_stemme as $dato => $verdi) {
	echo $dato . '  ' . date('H:i:s', $verdi[0]) . " - " . date('H:i:s', $siste_stemme[$dato][0]);
	echo "            " . str_pad($verdi[1][1], 50) . "        -      " . $siste_stemme[$dato][1][1];
	echo "\n";
}
