<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/contact", 'POST', function($vars) use ($app, $jatbi, $setting) {
    // $app->header(['Content-Type' => 'application/json']);
    header(['Content-Type' => 'application/json']);


    // Lấy dữ liệu và xử lý XSS
    $name     = $app->xss($_POST['name'] ?? '');
    $phone    = $app->xss($_POST['phone'] ?? '');
    $email    = $app->xss($_POST['email'] ?? '');
    $province = $app->xss($_POST['province'] ?? '');
    $title  = $app->xss($_POST['title'] ?? '');
    $note  = $app->xss($_POST['note'] ?? '');

    $recaptcha_secret = "6LcFPU4rAAAAAJjSN8K80z_LKRlP283pkrxyvctY";
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptcha_response)) {
        echo json_encode(["status" => "error", "content" => "Vui lòng xác nhận bạn không phải robot."]);
        return;
    }

    // Gửi request đến Google để xác minh
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
    $captcha_success = json_decode($verify);

    if (!$captcha_success->success) {
        echo json_encode(["status" => "error", "content" => "Xác minh reCAPTCHA thất bại."]);
        return;
}

    // Kiểm tra dữ liệu bắt buộc
    if (empty($name) || empty($phone) ) {
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

    // Chuẩn bị dữ liệu lưu
    $insert = [
        "name"     => $name,
        "phone"    => $phone,
        "email"    => $email,
        "province" => $province,
        "title"  => $title,
        "note"  => $note,
        "datetime" => date("Y-m-d H:i:s")
    ];

    try {
        $result = $app->insert("contact", $insert);
        if (!$result) {
            echo json_encode(["status" => "error", "content" => "Không thể lưu dữ liệu."]);
            return;
        }

        echo json_encode(["status" => "success", "content" => $jatbi->lang("Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.")]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
});





