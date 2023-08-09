<?php

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$mxRecords = (array)json_decode(file_get_contents(__DIR__ . '/docs/data-store/json/mx-records-' . date('Y') . '.json'));
$entitiesArray = json_decode(file_get_contents(__DIR__ . '/entities.json'))->entities;

$summary = array(
    'microsoft' => 0,
    'trendmicro' => 0,
    'google' => 0,
    'bedsys' => 0,
    'egen_domene' => 0,
    'sing.no' => 0,
    'heimdalsecurity' => 0,
    'staysecuregroup' => 0,
    'comendosystems' => 0,
);
$init = $summary;
foreach($entitiesArray as $entity) {

    if(isset($entity->entityExistedToAndIncluding)) {
        continue;
    }

    $mxRecord = $mxRecords[$entity->entityId];
    if (!is_array($mxRecord)) {
        $mxRecord = $mxRecord->digOutput;
    }

    $entries = $init;
    foreach($mxRecord as $record) {
        $record = strtolower($record);
        if (str_contains($record, 'protection.outlook.com')) {
            $entries['microsoft']++;
        }
        elseif (str_contains($record, 'messaging.microsoft.com')) {
            $entries['microsoft']++;
        }
        elseif (str_contains($record, 'emailsecurity.trendmicro.com')) {
            $entries['trendmicro']++;
        }
        elseif (str_contains($record, 'trendmicro.eu')) {
            $entries['trendmicro']++;
        }
        elseif (str_contains($record, 'l.google.com')) {
            $entries['google']++;
        }
        elseif (str_contains($record, 'googlemail.com')) {
            $entries['google']++;
        }
        elseif (str_contains($record, 'bedsys.net')) {
            $entries['bedsys']++;
        }
        elseif (str_contains($record, 'sing.no')) {
            $entries['sing.no']++;
        }
        elseif (str_contains($record, 'heimdalsecurity.com')) {
            $entries['heimdalsecurity']++;
        }
        elseif (str_contains($record, 'staysecuregroup')) {
            $entries['staysecuregroup']++;
        }
        elseif (str_contains($record, 'comendosystems')) {
            $entries['comendosystems']++;
        }
        elseif (str_contains($record, $entity->entityDomain)) {
            $entries['egen_domene']++;
        }
        else {
//            var_dump($record);
            $domainParts = explode('.', explode(' ', $record)[1]);
            $domain = $domainParts[count($domainParts) - 3] . '.' . $domainParts[count($domainParts) - 2];

            if (!isset($entries[$domain])) {
                $entries[$domain] = 0;
            }
            $entries[$domain]++;
        }
    }


    $mxRecords[$entity->entityId] = new stdClass();
    $mxRecords[$entity->entityId]->digOutput = $mxRecord;
    $mxRecords[$entity->entityId]->emailServer = new stdClass();

    foreach ($entries as $entry => $value) {
        if ($value > 0) {
            if (!isset($summary[$entry])) {
                $summary[$entry] = 0;
            }

            $summary[$entry]++;
            $mxRecords[$entity->entityId]->emailServer->$entry = true;
        }
    }
}

file_put_contents(__DIR__ . '/docs/data-store/json/mx-records-' . date('Y') . '.json', json_encode($mxRecords, JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES ^ JSON_UNESCAPED_UNICODE));

asort($summary);

foreach($summary as $entry => $sum) {
    echo $sum .'   ' . $entry .chr(10);
}
