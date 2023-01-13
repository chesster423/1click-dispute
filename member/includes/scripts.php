<script type="text/javascript">

    angular.module('cpi-app', [])
    .constant('API', {
        ACTION_URL: "../action.php?uid=<?php echo isset($uid) ? $uid : '' ?>",
    })
    .factory("UserFactory",  ['$http', '$q', 'APIService', function ($http, $q, APIService) {
        return {
            GetCardDetails: function(data = []) {
                return APIService.MakeRequest("&entity=stripe&action=get_card_details", "POST", data);
            },
            UpdateUserCard: function(data = []) {
                return APIService.MakeRequest("&entity=stripe&action=save_card_details", "POST", data);
            },
            RemoveCard: function(data = []) {
                return APIService.MakeRequest("&entity=stripe&action=remove_card", "POST", data);
            },
            GetUserDetails: function(data = []) {
                return APIService.MakeRequest("&entity=user&action=get_user", "POST", data);
            },
            UpdateUser: function(data = []) {
                return APIService.MakeRequest("&entity=user&action=update_user", "POST", data);
            },
            AcceptUser: function(data = []) {
                return APIService.MakeRequest("&entity=user&action=accept_user", "POST", data);
            },
            GetUserAddresses: function(data = []) {
                return APIService.MakeRequest("&entity=lob&action=get_user_addresses", "POST", data);
            },
            DeleteUserLobAddress: function(data = []) {
                return APIService.MakeRequest("&entity=lob&action=delete_address", "POST", data);
            },
        }
    }])
    .factory("SettingsFactory",  ['$http', '$q', 'APIService', function ($http, $q, APIService) {
        return {
            GetTOS : function(data = []) {
                return APIService.MakeRequest("&entity=setting&action=get_tos", "POST", data);
            },
        }
    }])
    .factory("AuthFactory",  ['$http', '$q', 'APIService', function ($http, $q, APIService) {
        return {
            AuthLogin : function(data = []) {
                return APIService.MakeRequest("&entity=auth&action=login_member", "POST", data);
            },
            RCAuthLogin : function(data = []) {
                return APIService.MakeRequest("&entity=auth&action=login_member&redirectToRC=1", "POST", data);
            },
            ResetPassword : function(data = []) {
                return APIService.MakeRequest("&entity=auth&action=reset_password", "POST", data);
            },
        }
    }])
    .factory("MailLogFactory",  ['$http', '$q', 'APIService', function ($http, $q, APIService) {
        return {
            GetUserLogs : function(data = []) {
                return APIService.MakeRequest("&entity=mail_log&action=get_user_logs", "POST", data);
            },
        }
    }])
    .factory("UserMailSettingsFactory",  ['$http', '$q', 'APIService', function ($http, $q, APIService) {
        return {
            GetUserMailSettings : function(data = []) {
                return APIService.MakeRequest("&entity=user_mail_settings&action=get_user_mail_settings", "POST", data);
            },
            SaveUserMailSettings : function(data = []) {
                return APIService.MakeRequest("&entity=user_mail_settings&action=save_user_mail_settings", "POST", data);
            },
        }
    }])
    .service("APIService", ["$http", "$q", "API", function($http, $q, API) {
        return {
            MakeRequest : function(url, method, data = {}) {
                var d = $q.defer();

                $http({
                    method: method,
                    url: API.ACTION_URL + url,
                    data: data
                }).then(function (response){
                    d.resolve(response.data);
                },function (error){
                    d.reject(error);
                });

                return d.promise;
            }
        }
    }])
    .directive('loaderDirective', function() {
       return {
            template : 
            '<div class="fullscreen-loader" ng-init="is_processing = false;" ng-if="is_processing == true"><div class="loader-content"><img src="../lib/images/processing.gif"><br><span>Please wait...</span></div></div>'
        };
    })
    .directive('addresspageDirective', function() {
       return {
            template : 
            '<div class="row" ng-if="address.addresses.length > 0"><div class="col-sm-12"><button class="btn btn-info btn-sm pull-left ml-0" ng-if="address.previous_url" ng-click="getAddresses(0)"><i class="fa fa-angle-left mr-1"></i>Previous</button><button class="btn btn-info btn-sm pull-right mr-0" ng-if="address.next_url" ng-click="getAddresses(1)">Next<i class="fa fa-angle-right ml-1"></i></button></div></div>'
        };
    })
    .controller('AuthController', function AuthController($scope, $http, $location, AuthFactory) {

        $scope.auth = {};
        $scope.email = null;
        $scope.is_processing = false;
        $scope.is_rc_login = "<?= (isset($_GET['redirectToRC']) && $_GET['redirectToRC'] == 1) ? true : false ?>";

        $scope.login = function() {

            if ($scope.is_rc_login) {
                

                AuthFactory.RCAuthLogin($scope.auth).then(function(response){
                    if (response.success) {
                        var clientId = 'ybdLq3gaR1uNdpiO1XwXzA'; //sandbox
                        var loginDomain = 'platform.devtest.ringcentral.com';

                        if ($scope.auth.email !== 'chesster423@gmail.com') {
                            clientId = 'DtJSdS4dTRSfdXy2J0dYgQ'; //production
                            loginDomain = 'platform.ringcentral.com';
                        }

                        window.location = "https://"+loginDomain+"/restapi/oauth/authorize?response_type=code&client_id="+clientId+"&state=xyz&prompt=login%20consent&redirect_uri=https://app.30daycra.com/oauthRedirect.php";
                    }else{
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.msg,
                        })
                    }
                })

            }else{
               AuthFactory.AuthLogin($scope.auth).then(function(response){
                    if (response.success) {
                        if (response.data.accepted_tos == 0) {
                            window.location = 'tos.php?uid=' + response.data.uid;
                        }else{
                            window.location = 'user_mail_logs.php?uid=' + response.data.uid;
                        }                    
                    }else{
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.msg,
                        })
                    }
                }) 
            }

            
        }

        $scope.resetPassword = function(user_type) {

            $scope.is_processing = true;

            let data = {
                email : $scope.email,
                user_type : user_type
            }

            AuthFactory.ResetPassword(data).then(function(response){
                $scope.is_processing = false;
                if (response.success) {
                    Swal.fire(
                        'Success!',
                        response.msg,
                        'success'
                    );
                }else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.msg,
                    })
                }
            })
        }

    })
    .controller('TOSController', function TOSController($scope, $http, $location, $sce, SettingsFactory, UserFactory) {

        $scope.tos = {};
        $scope.is_processing = true;
        $scope.tos.sig = $('#sig').signature();
        $scope.user_id = "<?= isset($userID) ? $userID : null ?>";
        $scope.uid = "<?= isset($_GET['uid']) ? $_GET['uid'] : null ?>";

        _getTOS();
        
        function _getTOS() {

            let payload = {
                id : $scope.user_id
            }

            SettingsFactory.GetTOS(payload).then(function(response) {
                $scope.is_processing = false;
                if (response.success) {
                    $scope.tos.content = $sce.trustAsHtml(response.data.content);
                }else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.msg,
                    })
                }
            })
        }

        $scope.clearSignature = function() {
            $scope.tos.sig.signature('clear');
        }

        $scope.acceptTOS = function() {

            if ($scope.tos.sig.signature('isEmpty')) {
                alert('Please put your signature');
                return false;
            };

            $scope.tos.sig_svg = angular.copy($scope.tos.sig.signature('toSVG'));

            let payload = {
                id : $scope.user_id,
                accepted_tos : 1,
                signature : $scope.tos.sig_svg
            }

            UserFactory.AcceptUser(payload).then(function(response) {
                if (response.success) {            
                    window.location = 'user_mail_logs.php?uid=' + $scope.uid;
                }else{
                   Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.msg,
                    }) 
                }
            });

        }

    })
    .controller('UserController', function UserController($scope, $http, $location, API, UserFactory) {

        $scope.card = {};
        $scope.card.user_id = "<?= isset($userID) ? $userID : null ?>";
        $scope.user = {};
        $scope.user.id = "<?= isset($userID) ? $userID : null ?>";

        _getCardDetails();
        _getUserDetails();

        function _getCardDetails() {

            $scope.is_processing = true;

            UserFactory.GetCardDetails($scope.card).then(function(response) {
                $scope.is_processing = false;
                if (response.success) {
                    
                    $scope.card.exp_month = response.data.exp_month;
                    $scope.card.exp_year = response.data.exp_year;
                    $scope.card.name = response.data.name;
                    $scope.card.number = "**** **** **** "+response.data.last4;
                    $scope.card.id = response.data.id;

                }                
            })
        }

        function _getUserDetails() {
            UserFactory.GetUserDetails($scope.user).then(function(response) {
                $scope.user.name = response.data.name;
                $scope.user.email = response.data.email;
            })
        }

        $scope.saveCardDetails = function() {

            $scope.is_processing = true;

            UserFactory.UpdateUserCard($scope.card).then(function(response) {
                $scope.is_processing = false;
                if (response.success) {
                    
                    Swal.fire(
                        'Success!',
                        response.msg,
                        'success'
                    );

                    $scope.user.card = {};

                }else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'An error occured',
                    })
                }
                
            })
        }

        $scope.removeCard = function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.value) {
                    $scope.is_processing = true;
                    UserFactory.RemoveCard($scope.card).then(function(response) {
                        $scope.is_processing = false;
                        if (response.success) {
                            $scope.card = {};
                            $scope.card.user_id = "<?= isset($userID) ? $userID : null ?>";
                            Swal.fire(
                                'Success!',
                                'Card successfully removed',
                                'success'
                            );
                        }else{
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'An error occured',
                            })
                        }                        
                    })
                }
            })
        }

        $scope.updateUser = function() {
            $scope.is_processing = true;
            UserFactory.UpdateUser($scope.user).then(function(response) {
                $scope.is_processing = false;
                $scope.user.current_password = null;
                $scope.user.new_password = null;
                $scope.user.confirm_password = null;
                if (response.success) {                    
                    Swal.fire(
                        'Success!',
                        response.msg,
                        'success'
                    );
                }else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.msg,
                    })
                }
                
            })
        }

    })
    .controller('MailLogController', function MailLogController($scope, $http, $location, MailLogFactory) {

        $scope.log = {
            logs : null,
            log_entries : [],
        };
        $scope.user_id = "<?= isset($userID) ? $userID : null ?>";
        $scope.user = {};

        _getUserLogs();

        function _getUserLogs() {
            $scope.is_processing = true;

            let payload = {
                user_id : $scope.user_id
            }

            MailLogFactory.GetUserLogs(payload).then(function(response) {
                $scope.is_processing = false;
                $scope.log.logs = response.data.logs;
                $scope.user = response.data.user;
            })
        }

        $scope.viewLogs = function(data) {
            $scope.log.log_entries = data;
        }

    })
    .controller('MailSettingsController', function MailSettingsController($scope, $http, $location, UserMailSettingsFactory) {

        $scope.settings = {
            active_mail : null
        };

        $scope.current_active_mail = 'lob';

        $scope.user_id = "<?= isset($userID) ? $userID : null ?>";

        _getUserMailSettings();

        function _getUserMailSettings() {
            $scope.is_processing = true;

            let payload = {
                user_id : $scope.user_id
            }

            UserMailSettingsFactory.GetUserMailSettings(payload).then(function(response) {
                $scope.is_processing = false;
                $scope.settings = response.data; 

                if (!$scope.settings.active_mail) {
                    $scope.settings.active_mail = 'lob';
                }

                $scope.current_active_mail = $scope.settings.active_mail;
            })
        }

        $scope.saveUserMailSettings = function() {
            $scope.is_processing = true;

            $scope.settings.user_id = angular.copy($scope.user_id);

            UserMailSettingsFactory.SaveUserMailSettings($scope.settings).then(function(response) {
                $scope.is_processing = false;
                $scope.current_active_mail = $scope.settings.active_mail;
                if (response.success) {                    
                    Swal.fire(
                        'Success!',
                        response.msg,
                        'success'
                    );
                }else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: response.msg,
                    })
                }           
            })
        }

    })
    .controller('AddressController', function AddressController($scope, $http, $location, UserFactory) {


        $scope.user_id = "<?= isset($userID) ? $userID : null ?>";

        $scope.check_all = false;

        $scope.address = {
            addresses : [],
            next_url : null,
            previous_url : null,
        }

        _getUserAddresses();

        function _getUserAddresses() {

            $scope.is_processing = true;

            let payload = {
                user_id : $scope.user_id,
            }

            UserFactory.GetUserAddresses(payload).then(function(response) {

                $scope.is_processing = false;
                $scope.address.addresses = response.data.data; 
                $scope.address.next_url = response.data.next_url;
                $scope.address.previous_url = response.data.previous_url;

            })
        }

        $scope.getAddresses = function(command) {

            $scope.check_all = false;
            $scope.address.addresses = [];
            $scope.is_processing = true;

            let payload = {
                user_id : $scope.user_id,                
            }

            if (command == 0) {
                payload.before = angular.copy($scope.address.previous_url);
            }else if(command == 1) {
                payload.after = angular.copy($scope.address.next_url);
            }

            UserFactory.GetUserAddresses(payload).then(function(response) {

                $scope.is_processing = false;
                $scope.address.addresses = response.data.data; 
                $scope.address.next_url = response.data.next_url;
                $scope.address.previous_url = response.data.previous_url;

            })

        }

        $scope.checkAll = function () {

            $scope.check_all = !$scope.check_all;

            angular.forEach($scope.address.addresses, function (item) {
                item.checked = $scope.check_all;
            });
        }; 
 
        $scope.deleteSelectedAddresses = function() {

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.value) {

                    $scope.is_processing = true;   

                    const selected_addresses = $scope.address.addresses.filter(item => item.checked == true);

                    var index = 0;

                    const deleteAddress = function(index) {

                        if (index >= selected_addresses.length) {
                            $scope.is_processing = false;

                            if ($scope.address.addresses.length == 0) {
                                _getUserAddresses();
                            }

                            return false;
                        }

                        var address_id = selected_addresses[index].id;

                        let payload = {
                            user_id : $scope.user_id,
                            address_id : address_id,                
                        }

                        UserFactory.DeleteUserLobAddress(payload).then(function(response) {

                            let remove_index = $scope.address.addresses.findIndex(x => x.id === address_id);
                            $scope.address.addresses.splice(remove_index, 1);

                            index++;
                            deleteAddress(index);
             
                        })

                    }

                    deleteAddress(index);
                  
                }
            })
        }

    });

</script>
