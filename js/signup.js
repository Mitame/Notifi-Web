$(document).ready(function() {
	var form = $("#signup");
	
	var signup_pass_msg = $("#signup_pass_msg");
	var signup_fail_msg = $("#signup_fail_msg");
	var signup_instruct_msg = $("#signup_instruct_msg");

	var inline_user_msg = $("#user_status");
	var inline_user_error = $("#user_error");
	var inline_pass_msg = $("#pass_status");
	var inline_pass_error = $("#pass_error");
	var inline_pass_conf_msg = $("pass_conf_status");
	var inline_pass_conf_error = $("pass_conf_status");

	var input_user = $("#username");
	var input_pass = $("#password");
	var input_pass_conf = $("#password_conf");

	var signup_body = $("#signup_body");
	var signup_header = $("#signup_header");
	var signup_footer = $("#signup_footer");


	//hide everything
	signup_fail_msg.hide();
	signup_pass_msg.hide();

	signup_body.hide()
	signup_instruct_msg.hide()

	form.animate({width: 0, opacity: "0"},0)
	form.animate({width: "95%", opacity: "1"},1000,function() {
		signup_body.slideDown(1000);
		signup_instruct_msg.slideDown(1000);
	});

	$(".inline_status").hide();
	$(".inline_error").hide();

	//make inline instructions appear when text box is in focus
	input_user.focusin(function() {
		inline_user_msg.slideDown();
	});

	input_user.focusout(function() {
		inline_user_msg.slideUp();
		var status = checkUsername($(this).val());
		if (!status[0]) {
			inline_user_error.text(status[1]);
			inline_user_error.slideDown();
		}
		else {
			inline_user_error.slideUp();
		};
	});


	input_pass.focusin(function() {
		inline_pass_msg.slideDown();
	});


	input_pass.on("input", function() {
		var checkboxes = document.getElementsByClassName("pass_checkbox")
		var strength = passwordStrength(input_pass.val());
		
		// checkboxes[0].checked = true


		index = 0;
		
		// alert(checkboxes[0].checked);
		while (index < checkboxes.length){
			checkboxes[index].checked = strength[index];
			index += 1;
		};
	});

	input_pass.focusout(function() {
		inline_pass_msg.slideUp();
		if (input_pass.val() == ""){
			inline_pass_error.text("A password must be set").slideDown()
		} else {inline_pass_error.slideUp()}
	});


	input_pass_conf.focusin(function() {
		inline_pass_conf_msg.slideDown();
	});

	input_pass_conf.focusout(function() {
		inline_pass_conf_msg.slideUp();

		var confcheck = checkPassConf(input_pass.val(),input_pass_conf.val())

		if (confcheck[0]){
			inline_pass_conf_error.slideUp();
		}
		else {
			inline_pass_conf_error.text(confcheck[1]).slideDown()
		}
	});

	//make status messages dissapear when clicked
	$(".status_msg:not(#signup_instruct_msg)").click(function() {
		$(this).slideUp();
	});

	form.submit(function(event) {

		event.preventDefault();

		var username = $("#username").val();
		var password = input_pass.val();
		var password_conf = input_pass_conf.val();
		var captcha = grecaptcha.getResponse();

		var usernameCheck = checkUsername(username)
		if (!usernameCheck[0]) {
			hideStatus(500, function() {
				signup_fail_msg.text(usernameCheck[1]).slideDown();
			});
			return;
		}

		var passwordCheck = password.length != 0
		if (!passwordCheck) {
			hideStatus(500, function() {
				signup_fail_msg.text("A password must be set.").slideDown();
			});
			return;	
		}

		var confCheck = password == password_conf
		if (!confCheck){
			hideStatus(500, function() {
				signup_fail_msg.text("Passwords did not match.").slideDown();
			});
			return;
		};

		if (grecaptcha.getResponse() == "") {
			hideStatus(500, function() {
				signup_fail_msg.text("reCAPTCHA not performed.").slideDown();
			});
			return;
		}

		$.post("signup.php", {

			username: username,
			password: password,
			'g-recaptcha-response': captcha
		}, function(res){
			
			grecaptcha.reset()
			if (res["messages"][0]["msgType"] == "success"){
				$("#username").val("");
				input_pass.val("");
				input_pass_conf.val("");

				hideStatus(500, function() {
					signup_pass_msg.text(res["messages"][0]["message"]).slideDown();
				});
			}
			else if (res["messages"][0]["msgType"] == "fail") {
				hideStatus(500, function() {
				signup_fail_msg.text(res["messages"][0]["message"]).slideDown();
				});
			}
			else{
				hideStatus(500, function() {
					signup_fail_msg.text(res).slideDown();
				});
			}
		})
	});
});


function hideStatus(length,callback){
	$(".status_msg").slideUp(length,callback);
};


function checkUsername(username) {
	// username must be between 2 and 30 characters in length
	if (!(2 <= username.length && username.length <= 30)) { return [false,"Username must contain between 2 and 30 characters."]; };

	// username must only contain printable ascii characters (\x20_\x7e)
	if (/[^\x20-\z7E]+/.test(username)) { return [false, "Username can only contain printable ASCII characters."]; };

	// username cannot contain < , > or spaces 
	if (/[\<\> ]+/.test(username)) { return [false, "Username cannot contain '<', '>' or a space"];};

	return [true,"Error"]; //something is wrong if the message box still shows after username is fine
};


function passwordStrength(password) {

	length8 = password.length >= 8;
	length16 = password.length >= 16;
	hasUpper = /[A-Z]+/.test(password);
	hasLower = /[a-z]+/.test(password);
	hasSymbol = /[\x21-\x2F]+|[\x3A-\x40]+|[\x5B-\x60]+|[\x7B-\x7E]+/.test(password);
	hasNumber = /[0-9]+/.test(password);

	return [length8,length16,hasUpper,hasLower,hasNumber,hasSymbol];
};


function checkPassConf(password,password_conf) {
	if (password != password_conf) {
		return [false, "The two passwords do not match."]
	}
	else {return [true, "Error"]}
};