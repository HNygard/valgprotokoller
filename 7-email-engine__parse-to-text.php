<?php

require __DIR__ . '/vendor/autoload.php';
use Symfony\Component\DomCrawler\Crawler;

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$election_year = '2023';


$files = getDirContents(__DIR__ . '/email-engine-data-store/raw-' . $election_year);
foreach($files as $file) {
    if (str_ends_with($file, '.html')) {
        $file_txt = str_replace('.html', '.txt', $file);
        if (!file_exists($file_txt)) {
            echo 'HTML-to-TEXT: ' .$file . chr(10);
            $crawler = new Crawler(file_get_contents($file));
            file_put_contents($file_txt, $crawler->text(null, false));
        }
    }
}









function getDirContents($dir) {
    $command = 'find "' . $dir . '"';
    exec($command, $find);
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