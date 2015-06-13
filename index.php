<?php
    session_start();
    require('vendor/autoload.php');
    include('BoxManager.php');
    $BoxManager = new BoxManager(isset($_SESSION['box']) ? $_SESSION['box'] : NULL);
    $folders = $BoxManager->getFolders();
    
    if (isset($_FILES['file'])) {
        $BoxManager->uploadFile();
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel='stylesheet' href='Bootstrap/css/bootstrap.css' type='text/css'/>
        <link rel='stylesheet' href='Bootstrap/css/bootstrap-theme.css' type='text/css'/>
        <title></title>
    </head>
    <body>
        <h1>Form Assembly Box Integration</h1>
        <iframe src="https://app.box.com/embed_widget/s/07slxnln8nx83v6i1f14uxodl7neo8xb?view=list&sort=name&direction=ASC&theme=dark" width="500" height="400" frameBorder="0" allowfullscreen webkitallowfullscreen mozallowfullscreen oallowfullscreen msallowfullscreen></iframe>
        <?php
        // put your code here
        ?>
        <form action="index.php" method="POST" enctype="multipart/form-data">
            Select image to upload:
            <input type="file" name="file">
            <select name="folder">
                <?php 
                    foreach ($folders as $key => $value) {
                        echo '<option value="' . $value . '">' . $key . '</option>';
                    }
                 ?>
            </select>
            <input type="submit" value="Upload" name="submit">
        </form>
    </body>
</html>

<script src="JQuery/js/jquery-1.11.3.js"></script>
<script src="Bootstrap/js/bootstrap.min.js"></script>