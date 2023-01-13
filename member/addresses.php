<?php
require_once "logincheck.php";
require_once "includes/header.php"; 
?>

<!--Main layout-->
<main ng-controller="AddressController">
<!-- CONTENT HERE -->
	<loader-directive></loader-directive>
    <div class="container-fluid">
        <section class="card card-cascade narrower mb-5" style="box-shadow:none;background: #f8f9fa;">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <h6 class="card-header font-weight-bold text-uppercase py-4">ADDRESS LIST</h6>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <button class="btn btn-danger pull-right mr-0 mb-3" ng-click="deleteSelectedAddresses()" ng-if="address.addresses.length > 0"><i class="fa fa-trash"></i> Delete Selected</button>
                                </div>
                            </div>

                            <addresspage-directive></addresspage-directive>

                            <div id="table" class="table-editable" ng-if="address.addresses.length > 0">
                                <table class="table table-bordered">
                                    <thead >
                                        <tr>
                                            <th scope="col"><input type="checkbox" class="check-all" ng-model="check_all" ng-click="checkAll()"></th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Address Line 1</th>
                                            <th scope="col">Country</th>
                                            <th scope="col">City</th>
                                            <th scope="col">Date Created</th>
             
                                        </tr>
                                  </thead>
                                  <tbody>
                                        <tr ng-repeat="(k, v) in address.addresses track by $index">
                                            <td scope="row"><input type="checkbox" ng-model="v.checked"></td>
                                            <td scope="row" ng-bind="v.name"></td>
                                            <td scope="row" ng-bind="v.address_line1"></td>
                                            <td scope="row" ng-bind="v.address_country"></td>
                                            <td scope="row" ng-bind="v.address_city"></td>
                                            <td scope="row" ng-bind="v.date_created"></td>                    
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <addresspage-directive></addresspage-directive>

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




