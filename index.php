<!DOCTYPE html>
<?php
    if (isset($_GET['code']) && isset($_GET['state'])) {
        
    } elseif (isset ($_GET['error']) && isset ($_GET['error_descrption'])) {
        header('Location: http://localhost/FAUpload/error.php');
        die();
    }
    
?>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel='stylesheet' href='Bootstrap/css/bootstrap.css' type='text/css'/>
        <link rel='stylesheet' href='Bootstrap/css/bootstrap-theme.css' type='text/css'/>
        <title></title>
    </head>
    <body>
        <h1>Form Assembly Box Integration</h1>
        <a href='authenticate.php' class="btn btn-lg btn-success">Log in to Box Account</a>
        <?php
        // put your code here
        ?>
    </body>
</html>

<script src="JQuery/js/jquery-1.11.3.js"></script>
<script src="Bootstrap/js/bootstrap.min.js"></script>