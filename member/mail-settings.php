<?php
require_once "logincheck.php";
require_once "includes/header.php"; 
?>

<!--Main layout-->
<main ng-controller="MailSettingsController">
    <loader-directive></loader-directive>
    <div class="container-fluid">

        <section class="card card-cascade narrower mb-5" style="box-shadow:none;background: #f8f9fa;">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <h6 class="card-header font-weight-bold text-uppercase py-4">Mail Settings</h6>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <form class="row">
                                        
                                        <div class="col-sm-6" ng-show="false">
                                            <div class="form-group">
                                                <span>Active Mailing System: <span class="badge badge-success badge-pill" ng-bind="current_active_mail"></span></span>
                                            </div>
                                        </div>

                                        <div class="col-sm-12">
                                            <button type="button" class="btn btn-primary pull-right btn-sm" ng-click="saveUserMailSettings()">Save changes</button>
                                        </div>

                                        <div class="col-md-12">
                                            <hr>
                                        </div>

                                        <div class="col-sm-4 col-md-4">
                                            
                                            <div class="card p-3">
                                                <div class="form-group" ng-show="false">
                                                    <label>Select Mailing System</label>
                                                    <select class="form-control" ng-model="settings.active_mail">
                                                        <option value="lob">Lob</option>
                                                        <option value="postalocity">Postalocity</option>
                                                    </select>

                                                    <hr>
                                                </div>

                                                <input type="hidden" ng-model="settings.active_mail = 'lob'">

                                                <div class="form-group" ng-if="settings.active_mail == 'lob'">
                                                    <h4>Lob</h4>
                                                    <label>API Key</label>
                                                    <input type="text" class="form-control" placeholder="Enter Production Key" ng-model="settings.lob_api_key">
                                                </div>

                                                <div ng-if="settings.active_mail == 'postalocity'">
                                                    <div class="form-group">
                                                        <h4>Postalocity</h4>
                                                        <label>Username</label>
                                                        <input type="text" class="form-control" placeholder="Enter Username" ng-model="settings.postalocity_username">
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Password</label>
                                                        <input type="text" class="form-control" placeholder="Enter Password" ng-model="settings.postalocity_password">
                                                    </div>
                                                </div>
                                            
                                            </div>
                                   
                                        </div>

                                        <div class="col-sm-12 col-md-4" ng-show="false">
                          
                                            <div class="card p-3">
                                                
                                                <div class="form-group">
                                                    <center><h4>RingCentral</h4></center>
                                                </div>

                                                <div class="form-group">                                                    
                                                    <label>Transunion</label>
                                                    <input type="text" class="form-control" placeholder="Enter Username" ng-model="settings.transunion_faxnumber">
                                                </div>

                                                <div class="form-group">                                                    
                                                    <label>Equifax</label>
                                                    <input type="text" class="form-control" placeholder="Enter Username" ng-model="settings.equifax_faxnumber">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Experian</label>
                                                    <input type="text" class="form-control" placeholder="Enter Password" ng-model="settings.experian_faxnumber">
                                                </div> 

                                            </div>
                                   
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
<!--Main layout-->

<?php require_once "includes/footer.php"; ?>
<?php include_once("includes/scripts.php"); ?>




