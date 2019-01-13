<?php
//phpinfo();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'elements/db.php';
Portal::Init();
//Portal::CheckSession(Portal::GetMac());

$domain = Portal::$thisdomain;
if ($_SERVER['SERVER_NAME']!= $domain['name'].".".$domain['zone'] && Portal::$targetDomain == NULL) {
    Portal::AutoRedirect('redirect', urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://").$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']));
    die();
}

if (isset($_GET["reg"]))      { Portal::AutoRedirect('regst'); }
if (isset($_GET["wait"]))     { Portal::AutoRedirect('wait'); }
if (isset($_GET["login"]))    { Portal::AutoRedirect('login'); }
if (isset($_GET["redirect"])) { Portal::$targetDomain =  $_GET["redirect"]; }

Portal::CheckAuthCookie();
?>

<!DOCTYPE html>
<html lang="en" >

<head>
  <meta charset="UTF-8">
  <title>Authentication</title>  
    <link rel="stylesheet" href="css/normalize.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src='js/jquery.min.js'></script>
    <script src='js/typed.min.js'></script>
    <script src="js/aanim.js"></script>
    
</head>

<body>
<!--<input type="checkbox" id="switch" checked>-->

<div class="container">
    <div id=main_block>
    <?php 
    require_once 'texts/aperture.php';
    require_once 'texts/auth.php'; ?>
    <a href="javascript:void(0);">
    <?php require_once 'texts/btn.php'; ?>
    </a>
    </div>
  <div class="overlay">AV-1</div>
</div>
 

</body>

<script>
    $('.click').click(function() {
        $("#main_block").load("<?php echo Portal::CheckUserMac(); ?>");
    });
    aperture_enable=true;
    aperture_logo();
</script>
</html>
