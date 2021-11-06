<?php
session_start();

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

if (file_exists(ROOT . DS . 'config.inc.php'))
    include_once(ROOT . DS . 'config.inc.php');
else exit("Set up config.inc.php first");

include_once(ROOT . DS . 'core.php');

$template = file_get_contents(ROOT . DS . 'template.html');

// CONTROLLER
if ($_POST['submit'] == 'Login') {
    $user = trim($_POST['user']);
    $pw = trim($_POST['pass']);
    $correct = verifyLdapUser($user, $pw);
    if ($correct) {
        $_SESSION['user'] = $user;
        $out = container(renderMessage('Login erfolgreich', 'Sie werden gleich weitergeleitet..', 'success')) . '<script>window.location.href=\'?\';</script>';
    } else {
        $out = container(renderMessage('Fehler', 'Login fehlgeschlagen oder Zugang nicht berechtigt')) . renderLogin();
    }
    $template = str_replace('%%CONTAINER%%', $out, $template);
} else if ($_SESSION['user'])
{ 
    //logged in user
    if ($_GET['a'] == 'logout') {
        session_destroy();
        $out = '<script>window.location.href=\'?\';</script>';
    } else {
        
        $out  = '<form method="POST">';
        $out .= managePostData();
        $out .= container(card('<h3>Guten Tag!</h3><h4 style="margin-left:30px;">welche Klassen dürfen heute ins WLAN?</h4> <div class="text-right"><a href="?a=logout" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a> <a href="?"  class="btn btn-success"><i class="fas fa-sync"></i> Refresh</a></div>'));
        $out .= container(renderClassList());
        $out .= '</form>';
    }
    $template = str_replace('%%CONTAINER%%', $out, $template);
    
} else
    $template = str_replace('%%CONTAINER%%', renderLogin(), $template);


echo $template;
