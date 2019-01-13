<?php
require_once 'elements/db.php';
if (isset($_GET["redirect"])) { Portal::$targetDomain = $_GET["redirect"]; }
Portal::ErrorDispatcher($_GET);
if (isset($_POST["usr"])){
        $usr = htmlspecialchars($_POST["usr"]);
        $psw = htmlspecialchars($_POST["psw"]);    
        if(!Portal::Auth($usr, $psw)){
            header('HTTP/1.1 440 Data error');
            exit();
        }
        die();
    }  
?>




<div id=login_form>
    <div id=login>
    <div id=bck style="height: 100%;width: 450px;position: absolute;">
        <p id=left class="bar leftbar"></p>
        <p id=right class="bar rightbar"></p>
    </div>
        <form id=form style="visibility: hidden; margin-top: -20px;" method="post">
                <p>Username<input type="text" autocomplete="username" name="usr" required="required"></p>
                <p>Password<input type="password" autocomplete="password" name="psw" required="required"></p>                
                <button type="submit" class="btn btn-block">Submit</button>
        </form>
    </div>
</div>
<script>

$('#form').submit(function (e) {
    e.preventDefault();
    var b=$("#form").serialize();
    $.ajax({
        type: "POST",
        url: "index.php?login",
        data: b,
        success: $("#login_form").load("index.php?wait"),
        statusCode: {
            200:function(){ window.location.replace("<?php echo Portal::$targetDomain == NULL ? "index.php" : "index.php?redirect=".Portal::RedirectUriToString(); ?>");},
            440:function(){ window.location.replace("<?php echo Portal::$targetDomain == NULL ? "index.php?login&err=100" : "index.php?login&err=100&redirect=".Portal::RedirectUriToString() ?>");}
        },
        dataType: 'json'});
    }); 
$(document).ready(function() {
    watch_enable=false;
    aperture_enable=false;
border_small("bck");
});

</script>


