<?php

if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$servicesHandler = function($vars) use ($app, $jatbi, $setting) {
    $type = $vars['type'] ?? '';

    if (empty($type)) {
        http_response_code(400);
        echo "Thiếu loại dịch vụ.";
        return;
    }

    // Xác định tiêu đề và template dựa trên loại dịch vụ
    if ($type === 'business') {
        $vars['title'] = $jatbi->lang('Dịch vụ doanh nghiệp');
        $template = 'templates/dhv/business-services.html';
        $serviceType = 'Doanh nghiệp';
    } elseif ($type === 'event') {
        $vars['title'] = $jatbi->lang('Dịch vụ tổ chức sự kiện');
        $template = 'templates/dhv/event-services.html';
        $serviceType = 'Tổ chức sự kiện';
    } else {
        http_response_code(400);
        echo "Loại dịch vụ không hợp lệ.";
        return;
    }

    // Phân trang
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 6;
    $offset = ($page - 1) * $limit;

    // Lấy từ khóa tìm kiếm
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $vars['search_query'] = $searchQuery;

    // Lấy danh mục từ filter
    $categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
    $vars['category_filter'] = $categoryFilter;

    // Lấy danh sách danh mục cho filter
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
    $vars['categories'] = $categories ?? [];

    // Điều kiện truy vấn
    $conditions = [
        "services.type" => $serviceType,
        "services.status" => 'A'
    ];
    if (!empty($searchQuery)) {
        $conditions["OR"] = [
            "services.title[~]" => "%{$searchQuery}%",
            "services.description[~]" => "%{$searchQuery}%",
            "categories.name[~]" => "%{$searchQuery}%"
        ];
    }
    if (!empty($categoryFilter)) {
        $conditions["services.category_id"] = $categoryFilter;
    }

    // Tổng số dịch vụ để tính tổng số trang
    $totalServices = $app->count("services", $conditions);
    $totalPages = ceil($totalServices / $limit);

    // Lấy danh sách dịch vụ giới hạn theo phân trang
    $services_data = [];
    try {
        $services = $app->select("services", [
            "[>]categories" => ["category_id" => "id"]
        ], [
            "services.id",
            "services.image",
            "services.title",
            "services.slug",
            "services.description",
            "services.type",
            "services.category_id",
            "categories.name(category_name)"
        ], array_merge($conditions, [
            "LIMIT" => [$offset, $limit],
            "ORDER" => ["services.id" => "ASC"]
        ]));

        if ($services === false || $services === null || empty($services)) {
            $vars['content'] = $jatbi->lang("Không tìm thấy dịch vụ nào.");
        } else {
            foreach ($services as $service) {
                $description_items = explode("\n", $service['description'] ?? '');
                $formatted_items = array_map('trim', $description_items);

                $image_path = $service['image'] ?? '';
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

                $services_data[] = [
                    'type' => $service['type'] ?? '',
                    'image' => $relative_image_path,
                    'category_name' => $service['category_name'] ?? '',
                    'title' => $service['title'] ?? '',
                    'slug' => $service['slug'] ?? '',
                    'description_items' => $formatted_items,
                    'id' => $service['id'] ?? ''
                ];
            }
        }
    } catch (Exception $e) {
        $vars['content'] = $jatbi->lang("Lỗi: " . $e->getMessage());
    }

    echo $app->render($template, [
        'services_data' => $services_data,
        'categories' => $categories ?? [],
        'current_page' => $page,
        'total_pages' => $totalPages,
        'search_query' => $vars['search_query'],
        'category_filter' => $vars['category_filter'],
        'setting' => $setting,
        'title' => $vars['title'],
        'content' => $vars['content'] ?? null
    ]);
};

// Đăng ký 2 route riêng biệt, dùng chung handler
$app->router("/business-services", 'GET', function($vars) use ($servicesHandler) {
    $vars['type'] = 'business';
    $servicesHandler($vars);
});

$app->router("/event-services", 'GET', function($vars) use ($servicesHandler) {
    $vars['type'] = 'event';
    $servicesHandler($vars);
});