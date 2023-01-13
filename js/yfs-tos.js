$('body').on('click', '.accept-cb', function() {
 	$('.accept-btn').attr('disabled',!this.checked);
 	$('.tos-content-container').css({height : '65%' });
 	$('.clear-sig').show();
	$("#ySig").show();

 	yfs.user_signature = $('#sig').signature({disabled : !this.checked});
});

$('body').on('click', '.accept-btn', function() {
	$(this).prop('disabled', true);
 	yfs.accept();
});

$('body').on('click', '.clear-sig', function() {
 	yfs.user_signature.signature('clear'); 
});

var yfs = {
	user_signature : null,
	host_url : (location.hostname === "localhost" || location.hostname === "127.0.0.1") ? 'http://localhost/dominique-app/' : 'App.30daycra.com/',
	tos_text : null,
	tos_url : null,
	properties : null,
	init : function (data) {
		this.properties = data;
		this.tos_url = data.tos_url;
		this.verify();
	},
	verify : function () {
		$.ajax({
		    url: yfs.host_url+"action.php?entity=yfs_user&action=verify",
		    method: 'POST',
		    data : {
		    	id : yfs.properties.id
		    },
		    success: function(response){

		    	response = JSON.parse(response);

		    	console.log(response.success)

		    	if (!response.success) {
		    		yfs.showPopup();
		    	}
		    	
		    }
		});
	},
	showPopup: function() {
		$('.container, footer').hide();
		$('body').addClass('popup-block');
		$('body').prepend(yfs.terms_and_condition);
	},
	accept : function() {
		var sig = yfs.user_signature.signature('toSVG');

		if (yfs.user_signature.signature('isEmpty')) {
			alert("Please sign your signature");
		}else{

			yfs.properties.signature = sig;

			$.ajax({
			    url: yfs.host_url+"action.php?entity=yfs_user&action=accept",
			    method: 'POST',
			    data : yfs.properties,
			    success: function(response){

			    	response = JSON.parse(response);

			    	if (response.success) {
			    		window.location = window.location.href.replace('#','');
			    	}else{
			    		alert(response.msg);
			    	}
			    }
			});
		}
	},
	sendEmail : function() {
		$.ajax({
		    url: yfs.host_url+"action.php?entity=yfs_user&action=send_email",
		    method: 'POST',
		    data : {
		    	email : this.email,
		    },
		    success: function(response){
		    	if (response.success) {
		    		return true;
		    	}else{
		    		alert(response.msg);
		    	}
		    }
		});
	},
	store_tos : function(tos) {
		this.tos_text = tos;
	},
	terms_and_condition : function() {
		
		return '<div class="terms_of_service_container">'+
					'<div class="tos-content-container">' +
						'<iframe src="'+yfs.tos_url+'" width="100%" height="100%" id="tosIframe"></iframe>'+
					'</div>'+
					'<div class="accept-container">'+
						'<div class="accept-content">'+
							'<div style="padding-left: 30px;"><input type="checkbox" class="accept-cb"> I accept the terms and services</div>'+
							'<div id="ySig" style="display: none">Your Signature:<br></div><div id="sig"></div><button class="clear-sig">Clear</button>'+
							'<button class="accept-btn" disabled>Accept</button>'+
						'</div>'+
					'</div>'+
				'</div><style>#container-42304{display:none;}</style>';
	}
}