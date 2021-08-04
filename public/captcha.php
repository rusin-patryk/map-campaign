<?php
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

if (!$captcha) {
    print_r('error:no_captcha');
} else {
    $secret   = '6Lfk3tsbAAAAACTQ9GTqVI2QDkWQDZ88iz00U2Le';
    $response = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']
    );
    $response = json_decode($response);

//     print_r($response);

    if ($response->success === false) {
        print_r('error:bad_captcha');
    }
}

if ($response->success==1 && $response->score > 0) {
    print_r('access_token=pk.eyJ1IjoibmFub2l0IiwiYSI6ImNrcnZ2cW42ejBhZXQybm44ZXdnenRzbGsifQ.29MhI2aCgJMB93atv6eGtQ');
}
?>
