<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/index.html', $vars);
});