#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Src\Parser\XmlProductImporter;

$importer = new XmlProductImporter();
$importer->import(__DIR__ . '/storage/catalog.xml');