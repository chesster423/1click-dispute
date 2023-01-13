<?php
require_once "logincheck.php";
require_once "includes/header.php"; 
?>

<!--Main layout-->
<main ng-controller="MailLogController">
    <loader-directive></loader-directive>
    <div class="container-fluid">
        <section class="card card-cascade narrower mb-5" style="box-shadow:none;background: #f8f9fa;">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <h6 class="card-header font-weight-bold text-uppercase py-4">{{ user.name }} - LOGS</h6>
                        <div class="card-body">
                            <div id="table" class="table-editable">
                                <table class="table table-bordered">
                                    <thead >
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Transaction Code</th>
                                            <th scope="col">Mailing System</th>
                                            <th scope="col">Mail Attempts</th>
                                            <th scope="col">Date</th>
                                            <th scope="col">Options</th>
                                        </tr>
                                  </thead>
                                  <tbody>
                                        <tr ng-repeat="(k, v) in log.logs track by $index">
                                            <td scope="row">{{ $index+1 }}</td>
                                            <td scope="row" ng-bind="k"></td>
                                            <td scope="row">{{ log.logs[k][0].mail_system }}</td>
                                            <td scope="row">{{ v.length }}</td>
                                            <td scope="row">{{ log.logs[k][0].simple_date }}</td>
                                            <td scope="row">
                                                <button class="btn-sm btn btn-info" ng-click="viewLogs(v)" data-target="#logsModal" data-toggle="modal">View Logs</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- CREATE MODAL -->
    <div class="modal fade" id="logsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Transaction Logs</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-sm">
                        <thead >
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Transaction Name</th>
                                <th scope="col">Success</th>
                                <th scope="col">Time</th>
                            </tr>
                      </thead>
                      <tbody>
                            <tr ng-repeat="(k, v) in log.log_entries track by $index">
                                <td scope="row">{{ $index+1 }}</td>
                                <td scope="row" ng-bind="v.transaction_name"></td>
                                <td scope="row">
                                    <i class="fa fa-check text-success" ng-if="v.success == 1"></i>
                                    <i class="fa fa-close text-danger" ng-if="v.success == 0"></i>
                                </td>
                                <td scope="row" ng-bind="v.created_at"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-sm btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- END CREATE MODAL -->

</main>
<!--Main layout-->

<?php require_once "includes/footer.php"; ?>
<?php include_once("includes/scripts.php"); ?>




