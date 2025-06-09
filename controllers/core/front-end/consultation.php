<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/consultation", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy dữ liệu và xử lý XSS
    $name            = $app->xss($_POST['name'] ?? '');
    $phone           = $app->xss($_POST['phone'] ?? '');
    $email           = $app->xss($_POST['email'] ?? '');
    $company         = $app->xss($_POST['name_business'] ?? '');
    $note            = $app->xss($_POST['note'] ?? '');
    $date            = $app->xss($_POST['date'] ?? '');
    $time            = $app->xss($_POST['time'] ?? '');
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

    // Kiểm tra định dạng số điện thoại
    if (!preg_match('/^[0-9]{8,15}$/', $phone)) {
        echo json_encode([
            "status" => "error",
            "content" => "Số điện thoại không hợp lệ."
        ]);
        return;
    }

    // Kiểm tra và xử lý ngày giờ
    $datetime = null;
    if (!empty($date) && !empty($time)) {
        $d = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
        if ($d) {
            $datetime = $d->format('Y-m-d H:i:s'); 
        } else {
            echo json_encode([
                "status" => "error",
                "content" => "Ngày hoặc giờ không hợp lệ."
            ]);
            return;
        }
    } else {
        echo json_encode([
            "status" => "error",
            "content" => "Vui lòng chọn ngày và giờ tư vấn."
        ]);
        return;
    }

    try {
        // Lấy thông tin dịch vụ và gói

        $service_package_data = $app->select("services",["title","type"],["id" => $service_package]);
        $service_title = $service_package_data[0]['title'] ?? 'Không xác định';
        $service_type = $service_package_data[0]['type'] ?? 'Không xác định';


        // Dữ liệu cần insert
        $insert = [
            "name"           => $name,
            "phone"          => $phone,
            "email"          => $email,
            "name_business"  => $company,
            "note"           => $note,
            "datetime"       => $datetime,
            "service"        => $service_package,
            "method"         => $consult_method,
        ];
    
        $insert_2 = array_merge($insert, ["service_title"  => $service_title,"service_type" => $service_type]);

        $result = $app->insert("appointments", $insert);

        if (!$result) {
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Không thể lưu dữ liệu.")]);
            return;
        }

        echo json_encode([
            "status"  => "appointment",
            "content" => $jatbi->lang("Yêu cầu đã được lên lịch "),
            "data"    =>$insert_2     
        ]);


    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "content" => "Lỗi: " . $e->getMessage()
        ]);
    }
});





