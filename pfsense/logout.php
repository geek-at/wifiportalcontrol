<?php
// create in /usr/local/captiveportal/logout.php

require_once("captiveportal.inc");
require_once("auth.inc");
require_once("functions.inc");

global $g, $config, $cpzone, $cpzoneid;



/* Are there any portals  ? */
if (is_array($config['captiveportal'])) {
	/* For every portal (cpzone), do */
	foreach ($config['captiveportal'] as $cpkey => $cp)
		/* Sanity check */
		if (is_array($config['captiveportal'][$cpkey])) 
			/* Is zone enabled ? */
			if (array_key_exists('enable', $config['captiveportal'][$cpkey])) {
				$cpzone = $cpkey;
				$cpzoneid = $cp['zoneid'];
				$client_ip = $_SERVER['REMOTE_ADDR'];
				$cpentry = array();
				$cpentry = captiveportal_isip_logged($client_ip);
				if ( array_key_exists(5, $cpentry) ) {
				captiveportal_disconnect_client($cpentry[5], 1, "USER LOGOUT");
				}
			}
	}
?>