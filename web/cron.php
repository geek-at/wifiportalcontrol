<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

if (file_exists(ROOT . DS . 'config.inc.php'))
    include_once(ROOT . DS . 'config.inc.php');
else exit("Set up config.inc.php first");

include_once(ROOT . DS . 'core.php');

updateWifiAccess();