<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $services = $app->select("services", [
        "[>]services_detail" => ["id" => "service_id"],
        "[>]categories" => ["category_id" => "id"],
        "[>]author_boxes" => ["services_detail.author_box_id" => "id"]
    ], [
        "services.image(service_image)",
        "services.title(service_title)",
        "categories.name(category_name)",
        "author_boxes.name(author_name)",
        "author_boxes.image_url(author_image)",
        "services_detail.rate"
    ], [
        "ORDER" => ["services_detail.rate" => "DESC"]
    ]);

    $vars['services']= $services ; 
    echo $app->render('templates/dhv/index.html', $vars);
});

$app->router("/contact", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/contact.html', $vars);
}); 

$app->router("/consultation", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/consultation.html', $vars);
}); 


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

$app->router("/bussines-services-detail", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/business-services-detail.html', $vars);
});

$app->router("/event-services", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/event-services.html', $vars);
});

$app->router("/event-services-detail", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/event-services-detail.html', $vars);
});