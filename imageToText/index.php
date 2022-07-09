<?php
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
require 'vendor/autoload.php';
use thiagoalessio\TesseractOCR\TesseractOCR;
echo (new TesseractOCR('img/testeng.jpg'))
    ->run();
//echo (new TesseractOCR())->version();