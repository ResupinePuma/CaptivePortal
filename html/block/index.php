<!DOCTYPE html>
<html lang="en" >

<head>
  <meta charset="UTF-8">
  <title><?php echo $_SERVER['SERVER_NAME']; ?> is blocked</title>  
    <link rel="stylesheet" href="css/normalize.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src='js/jquery.min.js'></script>
    <script src="js/aanim.js"></script>
    
</head>

<body>
<input type="checkbox" id="switch" checked>

<div class="container">
    <div id=main_block>
        <div id=login_form>
            <div id=login>
                <p style="font-size: 60px"><?php echo $_SERVER['SERVER_NAME']; ?> is blocked</p>
                <p>Place the device on the ground, then lie on your stomach with your arms at your sides.<br> 
                A party associate will arrive shortly to collect you for your party.</p>                
            </div>
        </div>
    </div>
  <div class="overlay">AV-1</div>
</div>
</body>
</html>
