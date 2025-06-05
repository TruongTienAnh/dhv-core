<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

// Route cho chi tiết dịch vụ
$app->router("/services-detail/{slug}", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $slug = $vars['slug'] ?? null;

    if (!$slug) {
        http_response_code(400);
        echo "Thiếu slug dịch vụ.";
        return;
    }

    // Truy vấn theo slug
    $services = $app->select("services", [
        "[>]services_detail" => ["id" => "service_id"],
        "[>]categories" => ["category_id" => "id"]
    ], [
        "services.id",
        "services.image",
        "services.title(service_title)",
        "services.slug",
        "services_detail.id",
        "services_detail.title",
        "services_detail.description_title",
        "services_detail.rate",
        "services_detail.min_price",
        "services_detail.max_price",
        "services_detail.original_min_price",
        "services_detail.original_max_price",
        "services_detail.discount",
        "services_detail.content",
        "services_detail.author_box_id",
        "services_detail.service_id",
        "categories.name(category_name)"
    ], [
        "services.slug" => $slug,
        "LIMIT" => 1
    ]);
    

    if (!$services) {
        http_response_code(404);
        echo "Dịch vụ không tồn tại.";
        return;
    }

    $service_detail = $services[0];

    // Xử lý đường dẫn hình ảnh
    $image_path = $service_detail['image'] ?? '';
    $relative_image_path = '';
    if (!empty($image_path)) {
        $template_pos = strpos($image_path, '/templates');
        if ($template_pos !== false) {
            $relative_image_path = substr($image_path, $template_pos);
            $relative_image_path = str_replace('\\', '/', $relative_image_path);
        } else {
            $relative_image_path = str_replace('\\', '/', $image_path);
        }
    }
    $service_detail['image'] = $relative_image_path;

    // Xử lý đánh giá sao
    $rate = (int)($service_detail['rate'] ?? 0);
    $service_detail['stars'] = str_repeat('<i class="fas fa-star star text-2xl"></i>', $rate);

    // Lấy danh sách danh mục để hiển thị sidebar (nếu cần)
    $categories = $app->select("categories", [
        "[>]services" => ["id" => "category_id"]
    ], [
        "categories.id",
        "categories.name",
        "categories.slug",
        "total" => Medoo\Medoo::raw("COUNT(services.id)")
    ], [
        "GROUP" => [
            "categories.id",
            "categories.name",
            "categories.slug"
        ],
        "ORDER" => "categories.name"
    ]);

    echo $app->render('templates/dhv/services-detail.html', [
        'service_detail' => $service_detail,
        'categories' => $categories ?? [],
        'setting' => $setting
    ]);
});