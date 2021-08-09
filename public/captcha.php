<?php
include('config.php');

    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
if (isset($_GET['token'])) {
    $captcha = $_GET['token'];
} else {
    $captcha = false;
}

$url = '//' . $_SERVER['SERVER_NAME'];
$secret = DEV_CAPTCHA_SECRET;
$mapbox_token = DEV_MAPBOX_TOKEN;

if (strpos($url, '//www.oddajubrania.pl') !== false || strpos($url, '//oddajubrania.pl') !== false) {
    $secret = CAPTCHA_SECRET;
    $mapbox_token = MAPBOX_TOKEN;
}

if (!$captcha) {
    print_r('error:no_captcha');
} else {
    $response = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']
    );
    $response = json_decode($response);

    if ($response->success === false) {
        print_r('error:bad_captcha');
    }
}

if ($response->success==1 && $response->score > 0) {
    print_r($mapbox_token);
}
?>
