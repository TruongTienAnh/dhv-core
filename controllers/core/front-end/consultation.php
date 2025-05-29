<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/consultation-add", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy dữ liệu từ form và kiểm tra XSS
    $name             = $app->xss($_POST['name'] ?? '');
    $phone            = $app->xss($_POST['phone'] ?? '');
    $email            = $app->xss($_POST['email'] ?? '');
    $company          = $app->xss($_POST['company'] ?? '');
    $note               = $app->xss($_POST['note'] ?? '');
    $date             = $app->xss($_POST['date'] ?? '');
    $time             = $app->xss($_POST['time'] ?? '');
    $service_package  = $app->xss($_POST['service_package'] ?? '');
    $consult_method   = $app->xss($_POST['consult_method'] ?? '');

    // Kiểm tra dữ liệu bắt buộc
    if (empty($name) || empty($phone) || empty($service_package) || empty($consult_method)) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Vui lòng điền đầy đủ thông tin bắt buộc.")
        ]);
        return;
    }

    // Chuyển đổi sang định dạng DATETIME
    $datetime = null;
    if (!empty($date) && !empty($time)) {
        $d = DateTime::createFromFormat('d/m/Y H:i', $date . ' ' . $time);
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
        $insert = [
            "name"            => $name,
            "phone"           => $phone,
            "email"           => $email,
            "company"         => $company,
            "note"            => $note,
            "datetime"        => $date,
            "time"            => $time,
            "service"         => $service_package,
            "consult_method"  => $consult_method,
        ];

        $result = $app->insert("appointments", $insert);

        if ($result) {
            echo json_encode(["status" => "success", "content" => $jatbi->lang("Gửi yêu cầu tư vấn thành công")]);
        } else {
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Lưu dữ liệu thất bại")]);
        }

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['consultation']);




