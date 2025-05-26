<?php
if (!defined('ECLO')) die("Hacking attempt");
$requests = [
    'home' => "controllers/core/front-end/home.php",
];

foreach ($requests as $key => $controller) {
    $setRequest[] = [
        "key" => $key,
        "controllers" =>  $controller,
    ];
}