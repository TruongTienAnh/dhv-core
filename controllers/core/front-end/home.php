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
        "services.slug(service_slug)",
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

$app->router("/register-post", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Đăng ký nhận tư vấn");
    $services = $app->select("services", ["id","title", "type"], [
        "status" => "A",
        "ORDER" => ["id" => "ASC"]
    ]);
    $vars['service_packages']= $services ;  

    echo $app->render('templates/dhv/register-post.html', $vars, 'global');
});

$app->router("/register-post", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

        // Lấy dữ liệu và xử lý XSS
        $name            = $app->xss($_POST['name'] ?? '');
        $phone           = $app->xss($_POST['phone'] ?? '');
        $email           = $app->xss($_POST['email'] ?? '');
        $company         = $app->xss($_POST['name_business'] ?? '');
        $note            = $app->xss($_POST['note'] ?? '');
        $service_package = $app->xss($_POST['service_package'] ?? '');
        $consult_method  = $app->xss($_POST['consult_method'] ?? '');

        // Kiểm tra dữ liệu bắt buộc
        if (empty($name) || empty($phone) || empty($service_package) || empty($consult_method)) {
            echo json_encode([
                "status" => "error",
                "content" => $jatbi->lang("Vui lòng điền đầy đủ thông tin bắt buộc.")
            ]);
            return;
        }

        // Kiểm tra định dạng email nếu có
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                "status" => "error",
                "content" => "Địa chỉ email không hợp lệ."
            ]);
            return;
        }

        // Kiểm tra định dạng số điện thoại (tùy chỉnh theo yêu cầu thực tế)
        if (!preg_match('/^[0-9]{8,15}$/', $phone)) {
            echo json_encode([
                "status" => "error",
                "content" => "Số điện thoại không hợp lệ."
            ]);
            return;
        }

        // Thực hiện lưu dữ liệu
        try {
            $insert = [
                "name"     => $name,
                "phone"    => $phone,
                "email"    => $email,
                "name_business"  => $company,
                "note"     => $note,
                "service"  => $service_package,
                "method"   => $consult_method,
            ];

            $result = $app->insert("appointments", $insert);

            if (!$result) {
                echo json_encode(["status" => "error","content" => $jatbi->lang("Không thể lưu dữ liệu.")]);
                return;
            }

        echo json_encode([
            "status" => "success",
            "content" => $jatbi->lang("Yêu cầu đã được lên lịch "),
        ]);

        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "content" => "Lỗi: " . $e->getMessage()
            ]);
        }
});


$app->router("/contact", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/contact.html', $vars);
}); 

$app->router("/consultation", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $services = $app->select("services", ["id","title", "type"], [
        "status" => "A",
        "ORDER" => ["id" => "ASC"]
    ]);
    $vars['service_packages']= $services ;  
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

$app->router("/event-services", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/event-services.html', $vars);
});

$app->router("/services-detail", 'GET', function($vars) use ($app, $jatbi, $setting) {
    echo $app->render('templates/dhv/services-detail.html', $vars);
});