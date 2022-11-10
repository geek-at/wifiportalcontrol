<?php

function managePostData()
{
    $redis = getRedis();
    $cl = explode(',', CLASSES);
    $updated = false;
    foreach ($cl as $klasse) {
        $disable = $_POST['disable-' . $klasse];
        $increase = $_POST['increase-' . $klasse];

        $redisfield = REDIS_PREFIX . 'classes:' . $klasse;

        if ($disable) {
            $updated = true;
            $redis->expire($redisfield, 0);
        } else if ($increase) {
            $updated = true;
            $increase = intval($increase);
            $newttl = 0;
            $ttl = $redis->ttl($redisfield);
            if ($ttl) $newttl = $ttl;
            $newttl += ($increase * 60);
            $redis->setex($redisfield, $newttl, $_SESSION['user']);
        }
    }
    if ($updated !== false)
        updateWifiAccess();
}

function renderClassList()
{
    $table = '
    <table class="table table-dark">
    <thead>
      <tr>
        <th scope="col">Klasse</th>
        <th scope="col">WLAN Status</th>
        <th scope="col">Aktionen</th>
      </tr>
    </thead>
    <tbody>';

    $redis = getRedis();

    $cl = explode(',', CLASSES);
    sort($cl);
    foreach ($cl as $klasse) {
        $redisfield = REDIS_PREFIX . 'classes:' . $klasse;
        $ttl = $redis->ttl($redisfield);

        if ($ttl > 0)
            $status = '<span class="text-success">Freigeschalten bis ' . date("d.m.y H:i", time() + $ttl) . '</span><div id="timer_'.$klasse.'"><script>$( document ).ready(function() {renderCountdown("#timer_'.$klasse.'",'.($ttl*1000).')});</script></div>';
        else if($ttl===-1)
            $status = '<span class="text-success">Bis auf widerruf freigeschalten</span>';
        else
            $status = '<span class="text-danger">Gesperrt</span>';

        $table .= '<tr>
        <th scope="row">' . strtoupper($klasse) . '</th>
        <td>
        ' . (strpos($status,'Gesperrt')===false?$status.'<br/><button klasse="'.$klasse.'" class="disableinternet btn btn-danger">Sperren</button>':$status) . '
        </td>
        <td >';
        if ($ttl !==-1)
        $table.='
            <button klasse="'.$klasse.'" minutes="10" class="addminutes btn btn-primary">+10 Minuten</button>
            <button klasse="'.$klasse.'" minutes="30" class="addminutes btn btn-primary">+30 Minuten</button>
            <button klasse="'.$klasse.'" minutes="60" class="addminutes btn btn-primary">+60 Minuten</button>
            <button klasse="'.$klasse.'" minutes="1440" class="addminutes btn btn-primary">+1 Tag</button>
            <button klasse="'.$klasse.'" minutes="-1" class="addminutes btn btn-primary">Bis auf Widerruf</button>
        ';
        $table.='</td>
      </tr>';
    }

    $table .= '
        </tbody>
    </table>
    <div class="text-center">
    </div>';

    return $table;
}

function renderLogin()
{
    return '<div class="container">
	<div class="d-flex justify-content-center h-100">
		<div class="card" style="margin-right:50px;">
			<div class="card-header">
				<h3>Freischaltungsplattform für Lehrkräfte</h3>
			</div>
			<div class="card-body">
				<form method="POST">
					<div class="input-group form-group">
						<div class="input-group-prepend">
							<span class="input-group-text"><i class="fas fa-user"></i></span>
						</div>
						<input type="text" name="user" class="form-control" placeholder="Benutzername">
						
					</div>
					<div class="input-group form-group">
						<div class="input-group-prepend">
							<span class="input-group-text"><i class="fas fa-key"></i></span>
						</div>
						<input type="password" name="pass" class="form-control" placeholder="Passwort">
					</div>
					<div class="form-group">
						<input type="submit" name="submit" value="Login" class="btn float-right login_btn">
					</div>
				</form>
			</div>
        </div>
        
        <div class="card">
			<div class="card-header">
				<h3>Im WLAN anmelden</h3>
			</div>
			<div class="card-body">
            <a href="' . URL_AUTHENTICATE . '" class="btn btn-success btn-block">WLAN Login</a>
            <a href="' . URL_LOGOUT . '" class="btn btn-danger btn-block">WLAN Logout</a>
			</div>
		</div>
	</div>
</div>';
}

function getRedis()
{
    $redis  = new Redis();
    $redis->connect(REDIS_SERVER, REDIS_PORT, 2.5);
    if (defined('REDIS_PASS') && REDIS_PASS)
        $redis->auth(REDIS_PASS);

    return $redis;
}

function renderMessage($title, $message, $type = 'danger')
{
    return '<div class="alert alert-' . $type . '" role="alert">
    <h2>' . $title . '</h2>
    ' . $message . '
  </div>';
}

function container($data)
{
    return '<div class="container">' . $data . '</div>';
}
function card($data)
{
    return '<div class="card card-body">' . $data . '</div>';
}

function verifyLdapUser($username, $password)
{
    //Check to see if LDAP module is loaded.
    if (extension_loaded('ldap')) {

        if ($connect = @ldap_connect(LDAPSERVER)) {
            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3) or die("Could not set LDAP Protocol version");
            if ($bind = @ldap_bind($connect, $username . '@' . LDAPDOMAIN, $password)) {
                //check if this user is in the admin group
                $filter = "(&(objectClass=user)(sAMAccountName=$username)(memberof=" . ADMINGROUP . "))";
                //var_dump($filter);
                $search_result = ldap_search($connect, ADMINBASESEARCH, $filter);
                $entries = ldap_get_entries($connect, $search_result);
                @ldap_close($connect);

                //return true;
                return ($entries["count"] > 0);
            } else {
                //send error message - password incorrect

                @ldap_close($connect);
                return false;
            }
        }
    } else {
        exit("LDAP EXTENSION MISSING");
        return false;
    }

    @ldap_close($connect);
    return false;
}

function ldapdomtopath()
{
    $p = explode('.', LDAPDOMAIN);
    foreach ($p as $part) {
        $o[] = 'DC=' . $part;
    }
    return implode(',', $o);
}

function updateWifiAccess()
{
    $redis = getRedis();
    $domain_username = LDAPUSER . '@' . LDAPDOMAIN;
    $ldap_conn = ldap_connect(LDAPSERVER);

    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3) or die("Could not set LDAP Protocol version");

    // Authenticate the user and link the resource_id with
    // the authentication.
    if ($ldapbind = ldap_bind($ldap_conn, $domain_username, LDAPPASS) == true) {

        $cl = explode(',', CLASSES);
        //todo: smarter. using getActiveClassesOfWifiGroup($ldap_conn)
        $ist = getActiveClassesOfWifiGroup($ldap_conn);
        $soll = [];
        foreach ($cl as $klasse) {
            $redisfield = REDIS_PREFIX . 'classes:' . $klasse;
            if ($redis->ttl($redisfield) > 0 || $redis->ttl($redisfield)===-1)
                $soll[] = strtoupper($klasse);
        }

        /*
        debug stuff
        echo "\n\n\n[i] How it is:\n";
        var_dump($ist);
        echo "\n\n\n[i] How it should be:\n";
        var_dump($soll);

        echo "\n\n";
        */

        //to delete
        $delclasses = array_diff($ist, $soll);
        foreach($delclasses as $klasse) {
            //echo "[D] Deleting $klasse\n";
            $classdn = strtoupper(str_replace('*CLASS*', $klasse, CLASSDN));
            ldap_mod_del($ldap_conn, WIFIGROUP, array("member" => $classdn));
        }

        //to add
        $addclasses = array_diff($soll, $ist);
        foreach($addclasses as $klasse) {
            //echo "[A] Adding $klasse\n";
            $classdn = strtoupper(str_replace('*CLASS*', $klasse, CLASSDN));
            ldap_mod_add($ldap_conn, WIFIGROUP, array('member' => $classdn));
        }

        /*
        //delete all members from wifi group
        ldap_mod_del($ldap_conn, WIFIGROUP, array("member" => array()));
        //add groups to group
        
        foreach ($cl as $klasse) {
            $redisfield = REDIS_PREFIX . 'classes:' . $klasse;
            if ($redis->ttl($redisfield) > 0 || $redis->ttl($redisfield)===-1)
                ldap_mod_add($ldap_conn, WIFIGROUP, array('member' => str_replace('*CLASS*', $klasse, CLASSDN)));
        }
        */
    } else {
        echo "Could not bind to the server. Check the username/password.<br />";
        echo "Server Response:"

            // Error number.
            . "<br />Error Number: " . ldap_errno($ldap_conn)

            // Error description.
            . "<br />Description: " . ldap_error($ldap_conn);
    }

    ldap_close($ldap_conn);
}

function getActiveClassesOfWifiGroup($ldap_conn)
{
    $search = ldap_search($ldap_conn, substr(CLASSDN,11), '(&(objectCategory=group)(memberOf='.WIFIGROUP.'))', ['members']);
    $results = ldap_get_entries($ldap_conn, $search);

    if(!$results) return [];
    $cl = array_map('strtoupper',explode(',', CLASSES));
    $classdns = [];
    foreach($cl as $kl)
        $classdns[] = strtoupper(str_replace('*CLASS*', $kl, CLASSDN));

    $klassen = [];

    for($i=0;$i<$results['count'];$i++)
    {
        $dn = strtoupper($results[$i]['dn']);
        if(in_array($dn, $classdns))
        {
            $parts = explode(',', $dn);
            $klasse = strtoupper(substr($parts[0], 3));
            $klassen[] = $klasse;
        }
        else var_dump("$dn not in list");
        
    }

    return $klassen;
}