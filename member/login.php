<?php
if (!isset($_REQUEST['plugin']) && isset($_SESSION['memberSessionKey']) && isset($_SESSION['memberLoggedIN'])) {
    header('Location: edit-profile.php?uid='.session_id());
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>YFS Academy Mailer</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../lib/css/font-awesome-4.7.0/css/font-awesome.css">
    <!-- Bootstrap core CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- Material Design Bootstrap -->
    <link href="../css/mdb.css" rel="stylesheet">
    <!-- Custom styles -->
    <link href="../css/style.css" rel="stylesheet">
</head>

<body class="fixed-sn" ng-app="cpi-app">

    <div class="row justify-content-center" ng-controller="AuthController">
        <div class="col-md-4 col-md-offset-4">
            <?php
                if (isset($_GET['redirectToRC']) && $_GET['redirectToRC'] == 1) {
                    echo '
                        <div class="alert alert-warning text-center" style="margin-top: 100px;">
                            <p>
                                Please use the form below to login first to your 30DayCRA App, and after that we will redirect you to login to your RingCentral account.
                            </p>
                        </div>
                    ';
                }
            ?>
            <div class="card" style="<?php echo (!(isset($_GET['redirectToRC']) && $_GET['redirectToRC'] == 1)) ? 'margin-top: 150px;' : '' ?>">
                <div class="card-header" style="text-align: center; background: #131825;">
                    <img src="../lib/images/logo_title.png" style="width: 150px">
                </div>
                <div class="card-body">
                    <h6>EMAIL</h6>
                    <input type="email" id="email" class="form-control" ng-model="auth.email">
                    <h6 style="margin-top: 25px">PASSWORD</h6>
                    <input type="password" id="password" class="form-control" ng-model="auth.password">

                    <div style="text-align: center;margin-top: 20px;">
                        <a href="#" class="btn btn-primary" ng-click="login()">Log in</a>
                    </div>

                    <p style="text-align: center; margin-top: 30px;"></p>

                    <div style="text-align: center;margin-top: 50px;">
                        <a href="reset-password.php" class="">Lost Password? Click Here...</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php require_once "includes/footer.php"; ?>
<?php include_once("includes/scripts.php"); ?>




