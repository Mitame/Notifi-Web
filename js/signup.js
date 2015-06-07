
function hideStatus(){
	$(".status_msg").slideUp();
};


$(document).ready(function() {
	var form = $("#signup");
	var pass_box = $(".pass_msg");
	var fail_box = $(".fail_msg");

	// var instruct_box = $(".instruct_box")

	fail_box.hide();
	pass_box.hide();
	


	//make status messages dissapear when clicked
	$(".status_msg:not(.instruct_msg)").click(function() {
		$(this).slideUp();
	});

	form.on("submit", function(event) {

		event.preventDefault();

		var username = $("#username").val();
		var password = $("#password").val();
		var password_conf = $("#password-conf").val();
		var captcha = grecaptcha.getResponse();

		if (password != password_conf){
			hideStatus()
			fail_box.text("Passwords did not match.");
			fail_box.show();
		};

		$.post("signup.php", {
			username: username,
			password: password,
			'g-recaptcha-response': captcha
		}, function(res){
			
			grecaptcha.reset()
			if (res["messages"][0]["msgType"] == "success"){
				$("#username").val("");
				$("#password").val("");
				$("#password-conf").val("");

				hideStatus();
				pass_box.text(res["messages"][0]["message"]);
				pass_box.show();
			}
			else if (res["messages"][0]["msgType"] == "fail") {
				hideStatus();
				fail_box.text(res["messages"][0]["message"]);
				fail_box.slideDown();
			}
			else{
				hideStatus();
				fail_box.text(res);
				fail_box.slideDown();
			}
		})
	});
});