<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

// Đăng ký route để hiển thị danh sách dịch vụ
$app->router("/business-services", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'text/html; charset=utf-8']);

    // Khởi tạo $services mặc định là mảng rỗng để tránh lỗi
    $services = [];
    $content = '';

    try {
        // Truy vấn tất cả dữ liệu từ bảng services
        $result = $app->select('services', '*');

        // Gỡ lỗi: Kiểm tra kết quả truy vấn
        // var_dump($result); // Bỏ comment để kiểm tra dữ liệu trả về

        // Kiểm tra kết quả truy vấn
        if ($result === false || $result === null || empty($result)) {
            $content = $jatbi->lang("Không tìm thấy dịch vụ nào.");
        } else {
            $services = (array) $result; // Đảm bảo $services là mảng
        }

    } catch (Exception $e) {
        $content = $jatbi->lang("Lỗi: " . $e->getMessage());
        // Gỡ lỗi: Hiển thị chi tiết lỗi
        // var_dump($e->getMessage()); // Bỏ comment để kiểm tra lỗi
    }

    // Gỡ lỗi: Kiểm tra giá trị của $services trước khi render
    // var_dump($services); // Bỏ comment để kiểm tra

    // Render giao diện với dữ liệu
    echo $app->render('templates/dhv/business-services.html', [
        'services' => $services, // Đảm bảo $services luôn là mảng
        'setting' => $setting,
        'content' => $content
    ]);
});