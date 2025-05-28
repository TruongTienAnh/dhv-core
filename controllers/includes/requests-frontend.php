<?php
if (!defined('ECLO')) die("Hacking attempt");
$requests = [
    'home' => "controllers/core/front-end/home.php",
    'admin' => "controllers/core/back-end/admin.php",
    'main' => "controllers/core/back-end/main.php",
    'users' => "controllers/core/back-end/users.php",
];

foreach ($requests as $key => $controller) {
    $setRequest[] = [
        "key" => $key,
        "controllers" =>  $controller,
    ];
}