<?php

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../sessionHandler.php";
require __DIR__ . "/../preload.php";

$handler = new DatastoreSessionHandler();

session_set_save_handler($handler);

session_start();

$_SESSION['test'] = microtime();