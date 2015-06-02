<?php
function getHeader($headerKey) {
	$headerValue = @$_GET[$headerKey];
    if (is_null($headerValue)) {$headerValue = @$headers[$headerKey];};
    if (is_null($headerValue)) {error("$headerKey missing");};
    return $headerValue;
};

function error($message) {
    http_response_code(500);
    header("Content-Type: application/json",true);
    sendJSON(json_encode(array(
            'messages'=>array(
                array(
                    'msgID'=>-1,
                    'msgFrom'=>'Server',
                    'msgType'=>'error',
                    'message'=>$message,
                    'sig'=>'b0658c468ad633cbd81c794e380dd115d2167cb76b0133de95d49f7d59be2beb'
                    )
                )
        ),JSON_PRETTY_PRINT));
    
    die();
};

function sendJSON($json){
    header("Content-Type: application/json",true);
    echo $json;
}

function apikeyIsValid($conn,$apikey) {
    $query = "SELECT * FROM apiKeys WHERE apikey = '$apikey' LIMIT 1";
    $res = mysqli_fetch_array(mysqli_query($conn, $query));
    if (!$res){error("incorrect apikey");};

    $now = time();
    $from = strtotime($res["validFrom"]);
    $to = strtotime($res["validTo"]);
    
    if ($from <= $now and $now <= $to){return true;}
    elseif ($from >= $now){error("key not yet valid");}
    elseif ($to <= $now){error("key expired");}
    else {error("unknown key error");};
};
?>