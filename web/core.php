<?php

function managePostData()
{
    $redis = getRedis();
    $cl = explode(',',CLASSES);
    foreach($cl as $klasse)
    {
        $disable = $_POST['disable-'.$klasse];
        $increase = $_POST['increase-'.$klasse];

        $redisfield = REDIS_PREFIX.'classes:'.$klasse;

        if($disable)
        {
            $redis->expire($redisfield,0);
        }
        else if($increase)
        {
            $increase = intval($increase);
            $newttl = 0;
            $ttl = $redis->ttl($redisfield);
            if($ttl) $newttl = $ttl;
            $newttl+=($increase*60);
            $redis->setex($redisfield,$newttl,$_SESSION['user']);
        }
    }
}

function renderClassList()
{
    $table= '<form method="POST">
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
      
    $cl = explode(',',CLASSES);
    foreach($cl as $klasse)
    {
        $redisfield = REDIS_PREFIX.'classes:'.$klasse;
        $ttl = $redis->ttl($redisfield);

        if($ttl > 0)
            $status = '<span class="text-success">Freigeschalten bis '.date("d.m.y H:i",time()+$ttl).'</span>';
        else $status = '<span class="text-danger">Gesperrt</span>';

        $table.='<tr>
        <th scope="row">'.strtoupper($klasse).'</th>
        <td>'.$status.'</td>
        <td >
            <div class="form-check">
                <input class="form-check-input" value="1" type="checkbox" value="" id="disable-'.$klasse.'" name="disable-'.$klasse.'">
                <label class="form-check-label" for="disable-'.$klasse.'">
                WLAN für '.strtoupper($klasse).' sperren
                </label>
            </div>

            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                    <input type="checkbox" aria-label="Checkbox for following text input">
                    </div>
                </div>
                <input type="number" name="increase-'.$klasse.'" class="form-control" placeholder="Um Minuten erweitern" aria-label="Text input with checkbox">
            </div>
        </td>
      </tr>';
    }

    $table.='
        </tbody>
    </table>
    <div class="text-center">
    <input type="submit" name="submit" class="btn btn-warning" value="Speichern" /> 
    </div>
    </form>';

    return $table;
    
}

function renderLogin()
{
    return '<div class="container">
	<div class="d-flex justify-content-center h-100">
		<div class="card">
			<div class="card-header">
				<h3>Login</h3>
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
	</div>
</div>';
}

function getRedis(){
    $redis  = new Redis();
    $redis->connect(REDIS_SERVER, REDIS_PORT, 2.5);
    if (defined('REDIS_PASS') && REDIS_PASS)
        $redis->auth(REDIS_PASS);

    return $redis;
}

function renderMessage($title,$message,$type='danger')
{
    return '<div class="alert alert-'.$type.'" role="alert">
    <h2>'.$title.'</h2>
    '.$message.'
  </div>';
}

function container($data){return '<div class="container">'.$data.'</div>';}
function card($data){return '<div class="card card-body">'.$data.'</div>';}

function verifyLdapUser($username, $password)
{
    //Check to see if LDAP module is loaded.
    if (extension_loaded('ldap')) {

        if ($connect = @ldap_connect(LDAPSERVER)) {
            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3) or die("Could not set LDAP Protocol version");
            if ($bind = @ldap_bind($connect, $username.'@'.LDAPDOMAIN, $password)) {

                //check if this user is in the admin group
                $filter = "(&(objectClass=user)(sAMAccountName=$username)(memberof=".ADMINGROUP."))";
                //var_dump($filter);
                $search_result = ldap_search($connect, ldapdomtopath(), $filter);
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
        @ldap_close($connect);
        return false;
    }

    @ldap_close($connect);
    return false;
}

function ldapdomtopath()
{
    $p = explode('.',LDAPDOMAIN);
    foreach($p as $part)
    {
        $o[] = 'DC='.$part;
    }
    return implode(',',$o);
}