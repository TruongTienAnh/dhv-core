<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

// Hàm chung để lấy dự án, phân trang và xử lý tìm kiếm
$projectHandler = function($vars) use ($app, $jatbi, $setting) {
    // Tiêu đề trang
    $vars['title'] = $jatbi->lang('Dự án');

    // Số dự án tối đa mỗi trang
    $perPage = 3;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    // Lấy từ khóa tìm kiếm, chuyển - thành khoảng trắng
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $vars['search_query'] = $searchQuery;

    // Điều kiện truy vấn
    $conditions = ["status" => 'A'];
    if (!empty($searchQuery)) {
        $conditions["OR"] = [
            "title[~]" => "%{$searchQuery}%",
            "client_name[~]" => "%{$searchQuery}%",
            "industry[~]" => "%{$searchQuery}%"
        ];
    }

    // Lấy tổng số dự án
    $totalProjects = $app->count("projects", $conditions);
    $totalPages = ceil($totalProjects / $perPage);

    // Lấy danh sách dự án với phân trang
    $offset = ($currentPage - 1) * $perPage;
    $projects = $app->select("projects", [
        "id",
        "title",
        "slug",
        "client_name",
        "start_date",
        "end_date",
        "image_url",
        "industry"
    ], array_merge($conditions, [
        "ORDER" => ["start_date" => "DESC"],
        "LIMIT" => [$offset, $perPage]
    ]));

    // Truyền dữ liệu vào template
    $vars['projects'] = $projects;
    $vars['current_page'] = $currentPage;
    $vars['total_pages'] = $totalPages;
    $vars['setting'] = $setting;
    $vars['app'] = $app;

    echo $app->render('templates/dhv/project.html', $vars);
};

// Đăng ký route
$app->router("/projects", 'GET', $projectHandler);
?>