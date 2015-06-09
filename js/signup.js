$(document).ready(function() {
	var form = $("#signup");
	var pass_box = $("#signup-pass-msg");
	var fail_box = $("#signup-fail-msg");

	//hide everything
	fail_box.hide();
	pass_box.hide();

	$("#signup-body").hide()
	$("#signup-instruct-msg").hide()

	$("#signup").animate({width: 0, opacity: "0"},0)
	$("#signup").animate({width: "95%", opacity: "1"},1000,function() {
		$("#signup-body").slideDown(1000);
		$("#signup-instruct-msg").slideDown(1000);
	});
	// $(".form-body").hide().slideDown("slow")


	$(".inline-status").hide();
	$(".inline-error").hide();

	//make inline instructions appear when text box is in focus
	$("#username").focusin(function() {
		$("#user_status").slideDown();
	});

	$("#username").focusout(function() {
		$("#user_status").slideUp();
		var status = checkUsername($(this).val());
		if (!status[0]) {
			$("#user_error").text(status[1]);
			$("#user_error").slideDown();
		}
		else {
			$("#user_error").slideUp();
		};
	});


	$("#password").focusin(function() {
		$("#pass_status").slideDown();
	});

	$("#password").on("input", function() {
		var checkboxes = document.getElementsByClassName("pass_checkbox")
		var strength = passwordStrength($("#password").val());
		
		// checkboxes[0].checked = true


		index = 0;
		
		// alert(checkboxes[0].checked);
		while (index < checkboxes.length){
			checkboxes[index].checked = strength[index];
			index += 1;
		};
	});

	$("#password").focusout(function() {
		$("#pass_status").slideUp();
		if ($("#password").val() == ""){
			$("#pass_error").text("A password must be set").slideDown()
		} else {$("#pass_error").slideUp()}
	});


	$("#password-conf").focusin(function() {
		$("#pass_conf_status").slideDown();
	});

	$("#password-conf").focusout(function() {
		$("#pass_conf_status").slideUp();

		var confcheck = checkPassConf($("#password").val(),$("#password-conf").val())

		if (confcheck[0]){
			$("#pass_conf_error").slideUp();
		}
		else {
			$("#pass_conf_error").text(confcheck[1]).slideDown()
		}
	});

	//make status messages dissapear when clicked
	$(".status_msg:not(#instruct_msg)").click(function() {
		$(this).slideUp();
	});

	form.submit(function(event) {

		event.preventDefault();

		var username = $("#username").val();
		var password = $("#password").val();
		var password_conf = $("#password-conf").val();
		var captcha = grecaptcha.getResponse();



		

		var usernameCheck = checkUsername(username)
		if (!usernameCheck[0]) {
			hideStatus(500, function() {
				fail_box.text(usernameCheck[1]).slideDown();
			});
			return;
		}

		var passwordCheck = password.length != 0
		if (!passwordCheck) {
			hideStatus(500, function() {
				fail_box.text("A password must be set.").slideDown();
			});
			return;	
		}

		var confCheck = password == password_conf
		if (!confCheck){
			hideStatus(500, function() {
				fail_box.text("Passwords did not match.").slideDown();
			});
			return;
		};

		if (grecaptcha.getResponse() == "") {
			hideStatus(500, function() {
				fail_box.text("reCAPTCHA not performed.").slideDown();
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
				$("#password").val("");
				$("#password-conf").val("");

				hideStatus(500, function() {
					pass_box.text(res["messages"][0]["message"]).slideDown();
				});
			}
			else if (res["messages"][0]["msgType"] == "fail") {
				hideStatus(500, function() {
				fail_box.text(res["messages"][0]["message"]).slideDown();
				});
			}
			else{
				hideStatus(500, function() {
					fail_box.text(res).slideDown();
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

	// username must only contain printable ascii characters (\x20-\x7e)
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