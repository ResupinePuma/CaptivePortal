
<?php
require_once 'elements/db.php';
if (isset($_GET["redirect"])) { Portal::$targetDomain = $_GET["redirect"]; }
Portal::ErrorDispatcher($_GET);

if (isset($_POST["fn"])){
    $fname = htmlspecialchars($_POST["fn"]);
    $lname = htmlspecialchars($_POST["ln"]);
    $addr = htmlspecialchars($_POST["addr"]);
    $mail = htmlspecialchars($_POST["mail"]);
    $phone = intval(htmlspecialchars($_POST["ph"]));
    $usr = htmlspecialchars($_POST["usr"]);
    $pas = htmlspecialchars($_POST["psw"]);    
    $pass = password_hash($pas, PASSWORD_DEFAULT);
    switch (Portal::AddUser($usr, $pass, $fname, $lname, $addr, $mail, $phone)){
        case 0:
            break;
        case 441:
            header('HTTP/1.1 441 Bad data');
            exit();
        case 442:
            header('HTTP/1.1 442 Bad data');
            exit();
    }
    die();       
}
?>


<div id=data_form>
    <div id=data>
    <div id=bckg style="height: 100%;width: 100%;position: absolute;">
        <p id=left class="bar leftbar"></p>
        <p id=right class="bar rightbar"></p>
    </div>
        <form id=form style="visibility: hidden; margin-top: -30px;" method="post">
                <p>First Name<input type="text" autocomplete="fistname" name="fn" required="required"></p>
                <p>Last Name<input type="text" autocomplete="lastname" name="ln" required="required"></p>
                <p>Address<input type="text" autocomplete="address" name="addr" required="required"></p>
                <p>E-mail<input type="email" autocomplete="email" name="mail" required="required"></p>
                <p>Phone number<input type="tel" autocomplete="phone" name="ph" required="required" required pattern="8[0-9]{10}"></p>
                <p>Username<input type="text" autocomplete="username" name="usr" required="required"></p>
                <p>Password<input type="password" autocomplete="password" name="psw" required="required"></p>

                <input type="checkbox" class=chkb  name="agr" checked><span style="font-size: 10px; text-align: justify;">  I confirm the correctness of the entered data and I understand that if I enter incorrect data I will be poisoned into the chamber with neurotoxin or until the end of my days I will work at Aperture Laboratories as a test subject. I also agree to receive mailings and be an organ donor in the event of an unforeseen death.<Br> 
                <button type="submit" class="btn btn-block">Submit</button>
        </form>
        <a href="javascript:void(0);"><p id=tologin style="text-align: center;"><u>or login</u></p></a>
    </div>
</div>
<script>

$('#form').submit(function (e) {    
    e.preventDefault();
    var b=$("#form").serialize();    
    $.ajax({
        type: "POST",
        url: "index.php?reg",
        data: b,
        success: $("#data_form").load("index.php?wait"),
        statusCode:{
            200:function(){ document.getElementById("#data_form"); $("#main_block").load("<?php echo Portal::$targetDomain == NULL ? "index.php?login" : "index.php?login&redirect=".Portal::RedirectUriToString(); ?>");},
            441:function(){ window.location.replace("<?php echo Portal::$targetDomain == NULL ? "index.php?reg&err=101" :"index.php?reg&err=101&redirect=".Portal::RedirectUriToString() ?>");},
            442:function(){ window.location.replace("<?php echo Portal::$targetDomain == NULL ? "index.php?reg&err=102" :"index.php?reg&err=102&redirect=".Portal::RedirectUriToString() ?>");}
        },
        dataType: 'json'
    });    
}); 
$('#tologin').click(function() {
        $("#main_block").load("index.php?login");
    });
aperture_enable=false;
border_large("bckg");


$(document).ready(function() {
    $('.chkb').mousedown(function() {
        if ($(this).is(':checked')) {
            this.checked = false;
            $(this).trigger("change");
        }
    });
});

</script>
