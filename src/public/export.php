<?php

require_once '../autoload.php';
$_default_format = 'csv';

$max_created_at = \Models\Document::getMaxCreatedAt();
$max_updated_at = \Models\Document::getMaxUpdatedAt();

$documents = \Models\Document::findAll();
// disable caching
$now = gmdate("D, d M Y H:i:s");
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
header("Last-Modified: {$now} GMT");

// force download
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");

// disposition / encoding on response body
header("Content-Disposition: attachment;filename=documents.csv");
header("Content-Transfer-Encoding: binary");

echo 'Most recent creation date: ' . \Models\Document::formatDate($max_created_at) . "\n";
echo 'Most recent update date: ' . \Models\Document::formatDate($max_updated_at) . "\n";

echo "\n";
echo 'key,value' . "\n";

foreach ($documents as $document)
{
    echo $document->key . ',' . $document->value . "\n";
}

?>