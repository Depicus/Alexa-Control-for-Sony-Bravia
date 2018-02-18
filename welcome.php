<?php

include_once 'config.inc';

function debugToEmail($data) {
    $allinfo = $data . '<br />';
    foreach ($_GET as $name => $value) {
        $allinfo .= "_GET = $name : $value<br>";
    }
    foreach ($_POST as $name => $value) {
        $allinfo .= "_POST = $name : $value<br>";
    }
    foreach (filter_input_array(INPUT_COOKIE) as $name => $value) {
        $allinfo .= "_COOKIE = $name : $value<br>";
    }
    /* foreach (filter_input_array(INPUT_SERVER) as $name => $value) {
      $allinfo.= "_SERVER = $name : $value<br>";
      } */

    $to = $emailaddress;
    $subject = "Debug Data from Alexa";
    $headers = 'From: debug@depicus.com' . "\r\n" . 'Reply-To: debug@depicus.com' . "\r\n" . 'X-Mailer: PHP/' . \phpversion();
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $body = $allinfo;
    mail($to, $subject, $body, $headers);
}

$postedjson = file_get_contents('php://input');
$data = json_decode($postedjson, true);


$intentrequest = $data['request']['intent']['name'];

//validate app id if not error and die

if ($applicationId != $data['session']['application']['applicationId']) {
    header("HTTP/1.1 406 I am afraid I can't do that Dave.");
    die();
}

$now = new DateTime;
$timesent = new DateTime($data['request']['timestamp']);
$difference = $now->getTimestamp() - $timesent->getTimestamp();
if ($difference > 10) {
    header("HTTP/1.1 406 This mission is too important for me to allow you to jeopardize it.");
    die();
}


switch ($intentrequest) {
    case "BBCONE":
        $reply = "changing channel to BBC One";
        if (intval(sendircc($one)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    case "BBCTWO":
        $reply = "changing channel to BBC Two";
        if (intval(sendircc($two)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    case "ITV":
        $reply = "changing channel to ITV";
        if (intval(sendircc($three)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    case "CHANNELFOUR":
        $reply = "changing channel to channel four";
        if (intval(sendircc($four)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    case "ITVTWO":
        $reply = "changing channel to ITV Two";
        if (intval(sendircc($one)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        if (intval(sendircc($seven)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;


    case "EFOUR":
        $reply = "changing channel to E FOUR";
        if (intval(sendircc($two)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        if (intval(sendircc($five)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    case "FILMFOUR":
        $reply = "changing channel to film four";
        if (intval(sendircc($two)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        if (intval(sendircc($nine)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    case "SKYNEWS":
        $reply = "changing channel to Sky News";
        if (intval(sendircc($four)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        if (intval(sendircc($six)) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;

    case "MUTE":
        $reply = "changing the sound level sir";
        if (intval(sendircc("AAAAAAQAAAAEAAAAUAw==")) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    case "TURNOFF":
        $reply = "turning off the telly, time to walk the dogs";
        if (intval(sendircc("AAAAAQAAAAEAAAAVAw==")) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    case "TURNON":
        $reply = "sit back relax and enjoy the show";
        if (intval(sendircc("AAAAAQAAAAEAAAAVAw==")) != 200) {
            $reply = "I think you know what the problem is just as well as I do.";
        }
        break;
    default;
        $reply = "I have just picked up a fault in the AE-35 unit";
        debugToEmail(json_encode($data) . ' <br /> ' . $intentrequest);
        break;
}


$sessionAttributes = array('key' => 'type');
$outputSpeech = array('type' => 'PlainText', 'text' => $reply, 'ssml' => "<speak>SSML text string to speak</speak>");
$card = array('type' => 'Standard', 'title' => 'Telly Controller', 'content' => 'You asked for ' . $intentrequest, 'text' => $reply);

$directives = array('type' => 'InterfaceName.Directive');

$reply = array('version' => '1.0', 'sessionAttributes' => $sessionAttributes, 'response' => array('outputSpeech' => $outputSpeech), 'card' => $card, 'directives' => $directives, 'shouldEndSession' => true);

header("Content-Type: application/json", true);
echo json_encode($reply, JSON_UNESCAPED_SLASHES);

function sendircc($ircc) {
    $xml = "<?xml version=\"1.0\"?>";
    $xml .= "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\" s:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">";
    $xml .= "<s:Body><u:X_SendIRCC xmlns:u=\"urn:schemas-sony-com:service:IRCC:1\">";
    $xml .= "<IRCCCode>$ircc</IRCCCode>";
    $xml .= "</u:X_SendIRCC></s:Body></s:Envelope>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://192.168.43.201/sony/IRCC");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/xml",
        "X-Auth-PSK: 0000",
        "SOAPACTION: \"urn:schemas-sony-com:service:IRCC:1#X_SendIRCC\"",
        "User-Agent: TVSideview/2.0.1 CFNetwork/672.0.8Darwin/14.0.0"
    ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpcode;
}

//sendircc("http://192.168.43.201/sony/IRCC", $xml);

//debugToEmail(json_encode($data) . ' <br /> ' . json_encode($reply, JSON_UNESCAPED_SLASHES));