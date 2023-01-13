<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Password Reset | YFS Academy Mailer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css"
          integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body ng-app="cpi-app">
    <div class="container">
        <div class="row justify-content-center" ng-controller="AuthController">
            <div class="col-md-6 col-md-offset-3">
                <div class="card" style="margin-top: 150px">
                    <div class="card-header" style="text-align: center; background: #131825;">
                        <img src="../lib/images/logo_title.png" style="width: 150px">
                    </div>
                    <div class="card-body">
                        <h6>EMAIL</h6>
                        <input type="email" id="email" class="form-control" ng-model="email">

                        <div style="text-align: center;margin-top: 20px;">
                            <button ng-click="resetPassword('admins')" class="btn btn-primary" ng-disabled="is_processing == true" style="cursor: pointer;">Reset Password</button>
                        </div>

                        <p style="text-align: center; margin-top: 30px;"></p>

                        <div style="text-align: center;margin-top: 50px;">
                            <a href="login.php" class="">Didn't Lose Password? Click Here to Login...</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once "includes/footer.php"; ?>
<?php include_once("includes/scripts.php"); ?>
