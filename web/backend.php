<?php
session_start();

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

if (file_exists(ROOT . DS . 'config.inc.php'))
    include_once(ROOT . DS . 'config.inc.php');
else exit("Set up config.inc.php first");

include_once(ROOT . DS . 'core.php');

$a = $_GET['a'];
$klasse = $_GET['kl'];

//general purpose
$redis = getRedis();
$klassen = explode(',', CLASSES);
$redisfield = REDIS_PREFIX . 'classes:' . $klasse;

$o = [];

switch($a)
{
    case 'disableaccess':
        $redis->expire($redisfield, 0);
        updateWifiAccess();

        $o = ['status'=>'OK'];
    break;

    case 'addminutes':
        $increase = intval($_GET['minutes']);
        $newttl = 0;
        $ttl = $redis->ttl($redisfield);
        if ($ttl) $newttl = $ttl;
        $newttl += ($increase * 60);
        $redis->setex($redisfield, $newttl, $_SESSION['user']);
        updateWifiAccess();

        $o = ['status'=>'OK'];
    break;

    default:
        $o = ['status'=>'err'];
}

echo json_encode($o);