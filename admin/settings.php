<?php
require_once "logincheck.php";
require_once "includes/header.php"; 
?>

<!--Main layout-->
<main ng-controller="SettingsController">
    <loader-directive></loader-directive>
    <div class="container-fluid">

        <section class="card card-cascade narrower mb-5" style="box-shadow:none;background: #f8f9fa;" style="display: none">
            <div class="row">
                <div class="col-md-1 col-sm-12">
                    <div class="list-group">
                        <center>
                            <a href="#!" class="list-group-item list-group-item-action" ng-class="(active_tab.api) ? 'active' : ''" ng-click="switchTab('api')">
                                <i class="fa fa-key"></i>
                                <br>
                                <small>API</small>
                            </a>
                            <a href="#!" class="list-group-item list-group-item-action" ng-class="(active_tab.tos) ? 'active' : ''" ng-click="switchTab('tos')">
                                <i class="fa fa-file"></i>
                                <br>
                                <small>TOS</small>
                            </a>
                            <a href="#!" class="list-group-item list-group-item-action" ng-class="(active_tab.mail) ? 'active' : ''" ng-click="switchTab('mail')">
                                <i class="fa fa-envelope"></i>
                                <br>
                                <small>MAIL</small>
                            </a>
                        </center>
                    </div>
                </div>
                <div class="col-md-11 col-sm-12">

                    <!-- API SETTINGS -->
                    <div class="card" ng-if="active_tab.api">
                        <h6 class="card-header font-weight-bold text-uppercase py-4">API Settings</h6>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <form>
                                        <div class="form-group">
                                            <h4>Stripe</h4>
                                            <input type="hidden" value="stripe" ng-model="devkeys.stripe.name" value="stripe">
                                            <input type="hidden" value="Stripe Key" ng-model="devkeys.stripe.description" value="Stripe Key">
                                            <label>Stripe Production Key</label>
                                            <input type="text" class="form-control" placeholder="Enter Production Key" ng-model="devkeys.stripe.production_key">
                                        </div>

                                        <div class="form-group">
                                            <label>Stripe Development Key</label>
                                            <input type="text" class="form-control" placeholder="Enter Development Key" ng-model="devkeys.stripe.development_key">
                                        </div>

                                        <div class="form-group">
                                            <label class="switch">
                                                <input type="checkbox" ng-model="devkeys.stripe.dev_active">
                                                <span class="slider round"></span>
                                            </label>
                                            <label class="switch-text">Use Stripe development keys</label>  
                                        </div>                                                            
                                    </form>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <h4>Sendgrid</h4>
                                        <input type="hidden" value="stripe" ng-model="devkeys.sendgrid.name" value="sendgrid">
                                        <input type="hidden" value="Stripe Key" ng-model="devkeys.sendgrid.description" value="Sendgrid Key">
                                        <label>Sendgrid Production Key</label>
                                        <input type="text" class="form-control" placeholder="Enter Production Key" ng-model="devkeys.sendgrid.production_key">
                                    </div>

                                    <div class="form-group">
                                        <label>Sendgrid Development Key</label>
                                        <input type="text" class="form-control" placeholder="Enter Development Key" ng-model="devkeys.sendgrid.development_key">
                                    </div>

                                    <div class="form-group">
                                        <label class="switch">
                                            <input type="checkbox" ng-model="devkeys.sendgrid.dev_active">
                                            <span class="slider round"></span>
                                        </label>
                                        <label class="switch-text">Use Sendgrid development keys</label>  
                                    </div>

                                    <div class="form-group">
                                        <button type="button" class="btn btn-primary ml-0 pull-right" ng-click="saveKeys()">Save keys</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END API SETTINGS -->

                    <!-- TOS SETTINGS -->
                    <div class="card" ng-if="active_tab.tos">
                        <h6 class="card-header font-weight-bold text-uppercase py-4">Terms of Service</h6> 

                        <div class="card-body">
                            <div class="form-group"> 
                                Use these variables <span class="text-muted">::DATE::</span>, <span class="text-muted">::EMAIL::</span>, <span class="text-muted">::NAME::</span> which will be replaced with the member's information on Terms of Service page.<br>
                                <i class="text-muted"><small>ex. These Terms and Conditions constitute a legally binding agreement made between you, ::NAME::, with an email of ::EMAIL::, whether personally or on behalf of an entity...</small></i>                               
                                <textarea ck-editor ng-model="tos.content" id="template_textarea"></textarea>
                                <input type="hidden" ng-model="tos.id">
                                <input type="hidden" ng-model="tos.name" value="tos">
                                <center><button class="btn btn-primary ml-0 mt-3" ng-click="saveTOS()"><i class="fa fa-save"></i> Save</button></center>
                            </div>
                        </div>
                    </div>
                    <!-- END TOS SETTINGS -->

                    <!-- MAIL SETTINGS -->
                    <div class="card" ng-if="active_tab.mail">
                        <h6 class="card-header font-weight-bold text-uppercase py-4">Mail Settings</h6> 

                        <div class="card-body">
                            <div class="form-group"> 
                                <label>Mail Content</label>
                                <select class="form-control" ng-model="mail_selected">
                                    <option value="">- Select Mail Content -</option>
                                    <option value="failed_payment">Failed Payment</option>
                                    <option value="new_purchase">New Purchase</option>
                                    <option value="rebill">Re-Bill</option>
                                    <option value="password_reset">Password Reset</option>
                                </select>
                                <br>
                                Use these variables which will be replaced with the member's information : <span class="text-muted">::EMAIL:: ::FIRST_NAME:: ::PASSWORD::</span><br>
                                <i class="text-muted"><small>ex. Dear ::FIRST_NAME::, Your new password is ::PASSWORD::</small></i>      
                            </div>
                            <div ng-if="mail_selected">
                                <div class="form-group">
                                    <hr> 
                                    <label>Subject</label>
                                    <input type="text" class="form-control" ng-model="mail[ mail_selected ].subject">                 
                                </div>
                                <div class="form-group"> 
                                    <label>Body</label>   
                                    <textarea class="form-control" ng-model="mail[ mail_selected ].body" ck-editor></textarea> 
                                    <button class="btn btn-success ml-0" ng-click="saveMailSettings()">Save</button>               
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END MAIL SETTINGS -->

                </div>
            </div>
        </section>
    </div>
</main>
<!--Main layout-->

<?php require_once "includes/footer.php"; ?>
<?php include_once("includes/scripts.php"); ?>



