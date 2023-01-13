<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>


<button class="btn">click me</button>
</body>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script type="text/javascript">
	

	$('.btn').click(function() {

		var login = {
			userName : 'dbrown2006@gmail.com',
			password : 'm1A%^%$VE7@A'
		};

		console.log(JSON.stringify(login));

		$.post({
			url: "https://prod.postalocity.com/user/login", 
			data : JSON.stringify(login),
			headers : { 
				'Content-Type': 'application/json',
				'Accept': 'application/json'
			},
			success: function(response){
				console.log(response);
			},
			failure: function(error) {
				console.log(error);
			}

		});

	})

</script>

</html>