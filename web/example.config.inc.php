<?php
date_default_timezone_set('Europe/Vienna');
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors','Off');


// Redis settings
define('REDIS_SERVER', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASS','');
define('REDIS_PREFIX', '');

// LDAP settings
define('LDAPDOMAIN','');
define('LDAPSERVER','');
define('LDAPUSER','');
define('LDAPPASS','');

// the CN of the group that is allowed to use this site
define('ADMINGROUP','CN=wifiadmins,DN=Users,DC=school,DC=local');

// the CN of the group that is to be added to users to enable their wifi
define('WIFIGROUP','CN=wlan,OU=students,DC=school,DC=local');

// template of the CN of the groups that will be replaced with the actual group
// *CLASS* will be replaced with the group name
define('CLASSDN','CN=*CLASS*,OU=students,DC=school,DC=local');