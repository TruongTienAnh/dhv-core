<?php
if (!defined('ECLO')) die("Hacking attempt");
$requests = [
    'home' => "controllers/core/front-end/home.php",
    'consultation' => "controllers/core/front-end/consultation.php",
    'contact' => "controllers/core/front-end/contact.php",

    'library' => "controllers/core/front-end/library.php",

    'services' => "controllers/core/front-end/services.php",

    'news' => "controllers/core/front-end/news.php",
    'projects' => "controllers/core/front-end/projects.php",



    // 'admin' => "controllers/core/back-end/admin.php",
    // 'main' => "controllers/core/back-end/main.php",
    // 'users' => "controllers/core/back-end/users.php",
];

foreach ($requests as $key => $controller) {
    $setRequest[] = [
        "key" => $key,
        "controllers" =>  $controller,
    ];
}