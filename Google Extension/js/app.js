angular.module('cpi-app', [])
.constant('API', {
    ACTION_URL: (localStorage['1devPpbHELnRan5nawe66amQSsHs98bMmtestG0']) ? "http://localhost/dominique-app/action.php" : "https://app.30daycra.com/action.php",
})
.service("APIService", ["$http", "$q", "API", function($http, $q, API) {
    return {
        MakeRequest : function(url, method, data = {}) {
            var d = $q.defer();

            data._token = localStorage['gp8YlEeTGqG166QY4IU8815MdeQOxSaHtF'];
            data.user_id = localStorage['userID'];

            $http({
                method: method,
                url: API.ACTION_URL + url,
                data: data,
                headers: {
                    'Content-Type' : 'application/x-www-form-urlencoded',
                },
                transformRequest: function(obj) {
                    var str = [];
                    for(var p in obj)
                    str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
                    return str.join("&");
                }
            }).then(function (response){
                d.resolve(response.data);
            },function (error){
                d.reject(error);
            });

            return d.promise;
        }
    }
}])
.factory("AuthFactory",  ['$http', '$q', 'APIService', function ($http, $q, APIService) {
    return {
        AuthLogin : function(data = []) {
            return APIService.MakeRequest("?entity=auth&action=login_member", "POST", data);
        },
        ResetPassword : function(data = []) {
            return APIService.MakeRequest("?entity=auth&action=reset_password", "POST", data);
        },
    }
}])
.factory("UserFactory",  ['$http', '$q', 'APIService', function ($http, $q, APIService) {
    return {
        GetUser : function(data = []) {
            return APIService.MakeRequest("?entity=user&action=get_user", "POST", data);
        },
    }
}])
.filter('range', function() {
    return function(input, total) {
        total = parseInt(total);

        for (var i=0; i<total; i++) {
            input.push(i);
        }

        return input;
    };
})
.filter('cut', function () {
    return function (value, wordwise, max, tail) {
        if (!value) return '';

        max = parseInt(max, 10);
        if (!max) return value;
        if (value.length <= max) return value;

        value = value.substr(0, max);
        if (wordwise) {
            var lastspace = value.lastIndexOf(' ');
            if (lastspace !== -1) {
              //Also remove . and , so its gives a cleaner result.
              if (value.charAt(lastspace-1) === '.' || value.charAt(lastspace-1) === ',') {
                lastspace = lastspace - 1;
              }
              value = value.substr(0, lastspace);
            }
        }

        return value + (tail || ' …');
    };
})
.filter('rename', function() {
    return function(value) {

        var name = '';

        var strings = value.split(/(?=[A-Z])/);

        for (var i = 0; i < strings.length; i++) {
            name += strings[i]+" ";
        }

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        return capitalizeFirstLetter(name);

    }
})
.controller('AuthController', function AuthController($scope, $http, $sce, AuthFactory, UserFactory) {

    $scope.auth = {
        login : {},
        reset : {},
        user : {},
    }; 

    _getUserdata();

    function _getUserdata() {
        if (typeof localStorage['auth_user'] !== 'undefined') {
            $scope.auth.user = angular.fromJson(localStorage['auth_user']);
        }

        let payload = {
            id : $scope.auth.user.id,
        }

        $scope.is_loading = true;
        UserFactory.GetUser(payload).then(function(response) {

            $scope.is_loading = false;

            if (response.success){

                $scope.auth.user.active_mail = response.data.active_mail;

            }
            else{
                //alert(response.msg);
            }
        });
    }

    $scope.is_loading = false;
    $scope.is_logged_in = (localStorage['loggedIn'] == 'yes') ? true : false;
    $scope.callback = {
        success : false,
        msg : null,
    };

    $scope.login = function() {

        $scope.is_loading = true;
        $scope.auth.login.login_type = 'extension';

        AuthFactory.AuthLogin($scope.auth.login).then(function(response) {
            if (response.success) {
                $scope.is_logged_in = true;

                localStorage['loggedIn'] = 'yes';
                let user_data = {
                    name : response.data.name,
                    email : $scope.auth.login.email,
                    id : response.data.id,
                    active_mail : response.data.active_mail
                };
                localStorage['auth_user'] = JSON.stringify(user_data);
                $scope.auth.user = user_data;
                localStorage['userID'] = response.data.id;
                localStorage['gp8YlEeTGqG166QY4IU8815MdeQOxSaHtF'] = response.data.token;

                let storage_data = {
                    userID : response.data.id,
                    gp8YlEeTGqG166QY4IU8815MdeQOxSaHtF : response.data.token,
                    auth_user : user_data,
                    loggedIn : 'yes',
                    active_mail : user_data.active_mail
                };

                chrome.storage.sync.set(storage_data, function() {
                  console.log('Data saved');
                });

            }else{
                $scope.callback = response;
                $scope.callback.msg = $sce.trustAsHtml($scope.callback.msg);
            }
            $scope.is_loading = false;
        });
    }

    $scope.resetPassword = function() {

        $scope.is_loading = true;

        let payload = {
            email : $scope.auth.reset.email,
            user_type : 'users',
        }

        AuthFactory.ResetPassword(payload).then(function(response) {
            $scope.callback = response;
            $scope.callback.msg = $sce.trustAsHtml($scope.callback.msg);
            $scope.is_loading = false;
        });
    }

    $scope.logout = function() {

        $scope.is_logged_in = false;
        $scope.auth.user = {}
        localStorage.removeItem("loggedIn");
        localStorage.removeItem("auth_user");
        localStorage.removeItem("userID");
        localStorage.removeItem("gp8YlEeTGqG166QY4IU8815MdeQOxSaHtF");

        let toRemove = ['loggedIn', 'auth_user', 'userID', 'gp8YlEeTGqG166QY4IU8815MdeQOxSaHtF'];
        chrome.storage.sync.remove(toRemove, function(items) {

        }); 

    }

})
.controller('UserController', function UserController($scope, $http, UserFactory) {

    $scope.user = {};

    _getUserData();

    function _getUserData() {

        $scope.user = {};

        let payload = {
            id : localStorage['userID']
        }

        UserFactory.GetUser(payload).then(function(response) {
            if (response.success)
                $scope.user = JSON.stringify(response)
            else
                alert(response.msg);
        });
    }

})






