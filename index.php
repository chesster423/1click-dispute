<?php
    header('Location: member/login.php', 302);
    exit();
?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>YFS Academy</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="lib/css/font-awesome-4.7.0/css/font-awesome.css">
    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body ng-app="yfs-app">

	<div class="container" ng-controller="SubscriptionController">
		<div class="row well pt-5">

			<div class="col-sm-6" ng-repeat="(key, value) in plans track by $index" ng-if="plans.length > 0">
				<div class="card text-center">
					<div class="card-header">
						PLAN {{ key+1 }}
					</div>
					<div class="card-body">
						<h5 class="card-title">{{ value.nickname }}</h5>
						<p class="card-text">Sample Description.</p>
						<a href="#" class="btn btn-primary" ng-click="signup(value)">SIGN UP</a>
					</div>
					<div class="card-footer text-muted">
						{{ value.plan_price }}
					</div>
				</div>
			</div>

		</div>
	</div>
</body>
</html>

<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="js/popper.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/mdb.min.js"></script>
<script type="text/javascript" src="js/sweetalert2.all.min.js"></script>
<script type="text/javascript" src="js/angular.js"></script>
<script src="https://js.stripe.com/v3/"></script>

<script type="text/javascript">
    
    angular.module('yfs-app', [])
    .constant('API', {
        ACTION_URL: "action.php?uid=null",
    })
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
    .factory("StripeFactory",  ['$http', '$q', 'APIService', function ($http, $q, APIService) {
        return {
            GetPlans : function(data = []) {
                return APIService.MakeRequest("&entity=stripe&action=get_plans", "POST", data);
            },
        }
    }])
    .controller('SubscriptionController', function AuthController($scope, $http, $location, StripeFactory) {

        $scope.plans = {};

        _getPlans();

        function _getPlans() {

        	StripeFactory.GetPlans().then(function(response) {
        		if (response.success) {
        			$scope.plans = response.data.data;
        		}
        	})
        }

        $scope.signup = function(data) {

        	let DOMAIN = window.location.hostname;

        	var stripe = Stripe('pk_test_2OlzsOE1BhhUSoHdKQsu6xCr005k5sE2bB');

        	stripe
			.redirectToCheckout({
				items: [{ plan: data.id, quantity: 1 }],
				successUrl : "https://app.30daycra.com/success.html?session_id={CHECKOUT_SESSION_ID}",
				cancelUrl : "https://app.30daycra.com/canceled.html"
			})
			.then(function(response) {
				console.log(response);
			});

        }

    })

</script>
