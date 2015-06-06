<?php
function getHeader($headerKey,$fail=true) {
	$headerValue = @$_GET[$headerKey];
    if (is_null($headerValue)) {$headerValue = @$headers[$headerKey];};
    if (is_null($headerValue)) { if ($fail) { error("$headerKey missing"); }; };
    return $headerValue;
};

function postHeader($headerKey,$fail=true) {
    $headerValue = @$_POST[$headerKey];
    if (is_null($headerValue)) {$headerValue = @$headers[$headerKey];};
    if (is_null($headerValue)) { if ($fail) { error("$headerKey missing"); }; };
    return $headerValue;
};

function error($message) {
    http_response_code(500);
    serverMessage("error","$message");
    die();
};

function serverMessage($msgType,$message){
    $sig = 'b0658c468ad633cbd81c794e380dd115d2167cb76b0133de95d49f7d59be2beb'; //TODO: Implement GPG signing by the server
    sendAsJSON(array(
            'messages'=>array(
                array(
                    'msgFrom'=>'Server',
                    'msgType'=>$msgType,
                    'message'=>$message,
                    'sig'=>$sig
                )
            )
        )
    );
};

function sendAsJSON($array){
    header("Content-Type: application/json",true);
    echo json_encode($array,JSON_PRETTY_PRINT);
};

function apikeyIsValid($conn,$apikey,$setLogin=true) {
    $esc_apikey = mysqli_real_escape_string($conn,$apikey);

    $query = "SELECT * FROM apiKeys WHERE apikey = '$esc_apikey' LIMIT 1";
    $res = mysqli_fetch_array(mysqli_query($conn, $query));

    if (!$res){error("incorrect apikey");};

    $now = time();
    $from = strtotime($res["validFrom"]);
    $to = strtotime($res["validTo"]);
    
    if ($from <= $now and $now <= $to){
        $userID = $res["iduser"];

        //set last login for user if required
        if ($setLogin) {
            $query = "UPDATE users SET lastLogin=NOW() WHERE iduser = $userID";
            mysqli_query($conn,$query);
        };

        //set last use for apikey
        $query = "UPDATE apikeys SET lastUsed=NOW() WHERE apikey = '$esc_apikey'";
        mysqli_query($conn,$query);

        return $res;
    }
    elseif ($from >= $now){ error("key not yet valid"); }
    elseif ($to <= $now){ error("key expired"); }
    else { error("unknown key error"); };
};

function getUserFromID($conn,$userID) {
    $query = "SELECT * FROM users WHERE iduser = $userID LIMIT 1";
    $res = mysqli_fetch_array(mysqli_query($conn, $query));

    return $res;
};

function getUserFromUsername($conn,$username) {
    $username = mysqli_real_escape_string($conn,$username);

    $query = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
    $res = mysqli_fetch_array(mysqli_query($conn, $query));

    return $res;
};

function getFullNameFromApikey($conn,$apikey) {
    $esc_apikey = mysqli_real_escape_string($apikey);

    $query = "SELECT appName FROM apiKeys WHERE apikey = '$esc_apikey' LIMIT 1";
    $appname = mysqli_fetch_array(mysqli_query($conn,$query));

    $user = apikeyIsValid($conn,$apikey,false);

    return $user."/".$appname["appName"];
};

function getFullNameFromIdApikey($conn,$id) {
    $query = "SELECT iduser,appName,apikey FROM apiKeys WHERE idapikey = $id LIMIT 1";
    $res = mysqli_fetch_array(mysqli_query($conn,$query));

    $appname = $res["appName"];

    $user = getUserFromID($conn,$res["iduser"])["username"];

    return $user."-".$appname;
};
?>

