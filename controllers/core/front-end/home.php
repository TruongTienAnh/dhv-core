<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/index.html', $vars);
});

// $app->router("/news", 'GET', function($vars) use ($app, $jatbi, $setting) {
//     echo $app->render('templates/dhv/news.html', $vars);
// }); 

$app->router("/contact", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/contact.html', $vars);
}); 

$app->router("/consultation", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/consultation.html', $vars);
}); 

// $app->router("/project", 'GET', function($vars) use ($app, $jatbi, $setting) {
//     echo $app->render('templates/dhv/project.html', $vars);
// }); 

// $app->router("/login", 'GET', function($vars) use ($app, $jatbi, $setting) {
//     echo $app->render('templates/dhv/login.html', $vars);
// }); 

// $app->router("/library", 'GET', function($vars) use ($app, $jatbi, $setting) {
//     echo $app->render('templates/dhv/library.html', $vars);
// }); 

$app->router("/project-detail", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/project-detail.html', $vars);
}); 

$app->router("/news-detail", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/news-detail.html', $vars);
}); 

$app->router("/library-detail", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/library-detail.html', $vars);
}); 

$app->router("/about", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/about.html', $vars);
}); 

$app->router("/business-services", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/business-services.html', $vars);
}); 

$app->router("/event-services", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/event-services.html', $vars);
});

$app->router("/services-detail", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/services-detail.html', $vars);
});