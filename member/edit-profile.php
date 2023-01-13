<?php
require_once 'logincheck.php';
require_once '../config.php';
require_once '../lib/DB.php';
// require_once 'brandSettings.php';

if (isset($_FILES['attachments'])) {
    $msg = "";

    $realName = basename($_FILES['attachments']['name'][0]);
    $extension = pathinfo($realName, PATHINFO_EXTENSION);
    $pictureName = $DB->generateRandomString().".$extension";
    $targetFile = "../uploads/" . $pictureName;
    $allowedExtensions = ['png','jpg','jpeg'];

    if (!in_array($extension, $allowedExtensions))
        $msg = array("status" => 0, "msg" => "File type not allowed!");
    else if (file_exists($targetFile))
        $msg = array("status" => 0, "msg" => "File already exists!");
    else if (move_uploaded_file($_FILES['attachments']['tmp_name'][0], $targetFile)) {
        $msg = array("status" => 1, "msg" => "File Has Been Uploaded", "path" => $targetFile);
        $DB->ExecuteQuery("UPDATE users SET picture = '$pictureName' WHERE id='$userID'");
        $_SESSION['memberPicture'] = $pictureName;
    }

    exit(json_encode($msg));
}

?>
<style type="text/css">
    #dropZone {
        border: 3px dashed #0088cc;
        padding: 50px;
        width: 450px;
        margin-top: 20px;
        height: 250px;
    }

    #files {
        border: 1px dotted #0088cc;
        padding: 20px;
        width: 200px;
        display: none;
    }

    #error {
        color: red;
    }
</style>

        <?php require_once "includes/header.php"; ?>

        <!--Main layout-->
        <main ng-controller="UserController">
            <loader-directive></loader-directive>
            <div class="container-fluid">
                <!--Section: Main panel-->
                <section class="card card-cascade narrower mb-5" style="box-shadow:none;background: #f8f9fa;">
                    <!--Grid row-->
                    <div class="row">

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <center>
                                    <?php
                                        require_once '../lib/Mobile_Detect.php';
                                        $detect = new Mobile_Detect();

                                        if (!$detect->isMobile()) {
                                    ?>
                                            <h1 id="error"></h1>
                                            <h1 id="progress"></h1>
                                            <div id="dropZone">
                                                <h1>Upload Your Profile Picture</h1>
                                                <input type="file" id="fileupload" name="attachments[]" multiple>
                                            </div><br><br>
                                            <div id="files"></div>
                                    <?php
                                        }
                                    ?>
                                    </center>                                    
                                </div>
                            </div>

                            <article class="card mt-3">                            
                                <div class="card-body p-5">
                                    <center>
                                        <h4>Edit Profile</h4>
                                    </center>
                                    <form role="form">
                                        <div class="form-group">
                                            <label for="username">Name</label>
                                            <input type="text" class="form-control" placeholder="Name" ng-model="user.name">  
                                        </div> <!-- form-group.// -->

                                        <div class="form-group">
                                            <label for="username">Email</label>
                                            <input type="text" class="form-control" placeholder="Name" ng-model="user.email" autocomplete="off"> 
                                        </div> <!-- form-group.// -->

                                        <button class="subscribe btn btn-primary btn-block" type="button" ng-click="updateUser()">Update</button>
                                        <hr>
                                        <center>
                                            <h4>Update Password</h4>
                                        </center>
                                        <div class="form-group">
                                            <label for="username">Current Password</label>
                                            <input type="password" class="form-control" placeholder="Enter current password" ng-model="user.current_password"> 
                                        </div> <!-- form-group.// -->

                                        <div class="form-group">
                                            <label for="username">New Password</label>
                                            <input type="password" class="form-control" placeholder="Enter new password" ng-model="user.new_password"> 
                                        </div> <!-- form-group.// -->

                                        <div class="form-group">
                                            <label for="username">Confirm New Password</label>
                                            <input type="password" class="form-control" placeholder="Confirm new password" ng-model="user.confirm_password"> 
                                        </div>

                                        <button class="subscribe btn btn-primary btn-block" type="button" ng-click="updateUser()">Save</button>
                                    </form>
                                </div>
                            </article>
                        </div>

                        <!--Grid column-->
                        <div class="col-md-6">

                            <article class="card d-none">
                                <div class="card-body p-5">
                                    <button class="btn btn-danger pull-right btn-sm mr-0" ng-if="card.id" ng-click="removeCard()">Remove Card</button>
                                    <h4>Card Information</h4>

                                    <form role="form">
                                        <div class="form-group">
                                            <label for="username">Full name (on the card)</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                                </div>
                                                <input type="text" class="form-control" name="username" placeholder="Enter name found in your card" required="" ng-model="card.name">                                                
                                            </div> <!-- input-group.// -->
                                        </div> <!-- form-group.// -->

                                        <div class="form-group">
                                            <label for="cardNumber">Card number</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-credit-card"></i></span>
                                                </div>
                                                <input type="text" class="form-control" name="cardNumber" placeholder="**** **** **** ****" ng-model="card.number">
                                            </div> <!-- input-group.// -->
                                        </div> <!-- form-group.// -->

                                        <div class="row">
                                            <div class="col-sm-8">
                                                <div class="form-group">
                                                    <label><span class="hidden-xs">Expiration</span> </label>
                                                    <div class="form-inline">
                                                        <input type="text" class="form-control" style="width:45%" placeholder="MM" ng-model="card.exp_month">
                                                        <span style="width:10%; text-align: center"> / </span>
                                                        <input type="text" class="form-control" style="width:45%" placeholder="YY" ng-model="card.exp_year">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="form-group">
                                                    <label data-toggle="tooltip" title="" data-original-title="3 digits code on back side of the card">CVC<i class="fa fa-question-circle"></i></label>
                                                    <input class="form-control" required="" type="text" ng-model="card.cvc" ng-disabled="card.id">
                                                </div> <!-- form-group.// -->
                                            </div>
                                        </div> <!-- row.// -->
                                        <button class="subscribe btn btn-primary btn-block" type="button" ng-click="saveCardDetails()"> Confirm  </button>
                                    </form>
                                </div> <!-- card-body.// -->
                            </article> <!-- card.// -->
                        </div>
                        <!--Grid column-->
                    </div>
                    <!--Grid row-->
                </section>
                <!--Section: Main panel-->
            </div>
        </main>
        <!--Main layout-->

        <?php require_once "includes/footer.php"; ?>
        <?php include_once("includes/scripts.php"); ?>
        <script src="../lib/js/vendor/jquery.ui.widget.js" type="text/javascript"></script>
        <script src="../lib/js/jquery.iframe-transport.js" type="text/javascript"></script>
        <script src="../lib/js/jquery.fileupload.js" type="text/javascript"></script>
        <script type="text/javascript">
            $(document).ready(function () {

                $("#fileupload").fileupload({
                    url: 'edit-profile.php?uid=<?php echo $uid ?>',
                    dropZone: '#dropZone',
                    dataType: 'json',
                    autoUpload: false
                }).on('fileuploadadd', function (e, data) {
                    var fileTypeAllowed = /.\.(jpg|png|jpeg)$/i;
                    var fileName = data.originalFiles[0]['name'];
                    var fileSize = data.originalFiles[0]['size'];

                    if (!fileTypeAllowed.test(fileName))
                        $("#error").html('Only images are allowed!');
                    else if (fileSize > 5000000)
                        $("#error").html('Your file is too big! Max allowed size is: 5MB');
                    else {
                        $("#error").html("");
                        data.submit();
                    }
                }).on('fileuploaddone', function(e, data) {
                    var status = data.jqXHR.responseJSON.status;
                    var msg = data.jqXHR.responseJSON.msg;

                    if (parseInt(status)) {
                        window.location = window.location;
                    } else
                        $("#error").html(msg);
                }).on('fileuploadprogressall', function(e,data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    $("#progress").html("Completed: " + progress + "%");
                });

            });
        </script>
    </body>
</html>
