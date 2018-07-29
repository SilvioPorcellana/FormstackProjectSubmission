<?php

require_once '../autoload.php';


if (! isset($_GET['document_id']))
{
    echo "Please provide a document_id";
    die;
}

if (isset($_GET['export_to']) && $_GET['export_to'])
{
    echo \Models\DocumentExport::exportTo($_GET['document_id'], $_GET['export_to']);
}
else
{
    echo \Models\DocumentExport::export($_GET['document_id'], (isset($_GET['format']) ? $_GET['format'] : false));
}

?>