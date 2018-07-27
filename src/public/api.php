<?php

/**
 *
 * This is the "controller" when API calls are made.
 *
 * This PHP script is the entry point and the main controller for API calls.
 * If "pretty URLs" want to be used, the correct .htaccess (or corresponding config file for the webserver used)
 * file needs to be created, redirecting all API calls to this script
 *
 * @author Silvio Porcellana
 * @version 1.0.0
 *
 */


require_once '../autoload.php';
use Models\DocumentAPI;

/**
 * The DocumentAPI class is a RESTable class that processes API calls through the "processAPI()" method
 */
$document_api = new DocumentAPI();
echo $document_api->processAPI();
