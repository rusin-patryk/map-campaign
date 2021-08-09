<?php
header('Content-Type: application/json');

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

$captcha = false;
$get_public = false;

if (isset($_GET['token'])) {
    $captcha = $_GET['token'];
} else if (isset($_GET['public'])) {
    $get_public = true;
}

include('config.php');
$url = '//' . $_SERVER['SERVER_NAME'];
$public = DEV_CAPTCHA_PUBLIC;
$secret = DEV_CAPTCHA_SECRET;
$mapbox_token = DEV_MAPBOX_TOKEN;

if (strpos($url, '//dev') === false && strpos($url, '//stagging') === false) {
    $public = CAPTCHA_PUBLIC;
    $secret = CAPTCHA_SECRET;
    $mapbox_token = MAPBOX_TOKEN;
}

if (!$captcha && !$get_public) {
    $return_value = array('data' => 'error:no_captcha');
    print_r(json_encode($return_value));
} else if ($captcha) {
    $response = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']
    );
    $response = json_decode($response);

    if ($response->success === false) {
        $return_value = array('data' => 'error:bad_captcha');
        print_r(json_encode($return_value));
    } else if ($response->success==1 && $response->score > 0) {
        $return_value = array('data' => $mapbox_token);
        print_r(json_encode($return_value));
    }
} else if ($get_public) {
    $return_value = array('data' => $public);
    print_r(json_encode($return_value));
}
?>
