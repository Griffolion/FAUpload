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
        <form action='https://app.box.com/api/oauth2/authorize' method='POST'>
            <input type="hidden" name="response_type" value="Code"  />
            <input type="hidden" name="client_id" value="z4cbqfi5tj82xr0wujz7q4cgi88cs6ag"  />
            <input type='hidden' name='redirect_uri' value='http://localhost/FAUpload/index.php' />
            <input type="hidden" name="state" value="secret22" />
            <input value="Click to link Box to your Form Assembly account" type="submit" class="btn-lg btn btn-success"  />
        </form>
        <?php
        // put your code here
        ?>
    </body>
</html>

<script src="JQuery/js/jquery-1.11.3.js"></script>
<script src="Bootstrap/js/bootstrap.min.js"></script>