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

    // Lấy từ khóa tìm kiếm
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $vars['search_query'] = $searchQuery;

    // Lấy ngành từ filter
    $industryFilter = isset($_GET['industry']) ? trim($_GET['industry']) : '';
    $vars['industry_filter'] = $industryFilter;

    // Lấy danh sách ngành cho filter
    $industries = $app->select("projects", ["industry"], [
        "status" => 'A',
        "deleted" => 0,
        "GROUP" => "industry",
        "ORDER" => ["industry" => "ASC"]
    ]);
    $vars['industries'] = array_column($industries, 'industry');

    // Điều kiện truy vấn
    $conditions = [
        "status" => 'A',
        "deleted" => 0
    ];
    if (!empty($searchQuery)) {
        $conditions["OR"] = [
            "title[~]" => "%{$searchQuery}%",
            "client_name[~]" => "%{$searchQuery}%",
            "industry[~]" => "%{$searchQuery}%"
        ];
    }
    if (!empty($industryFilter)) {
        $conditions["industry"] = $industryFilter;
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
        "excerpt",
        "client_name",
        "description",
        "start_date",
        "end_date",
        "image_url",
        "industry"
    ], array_merge($conditions, [
        "ORDER" => ["start_date" => "DESC"],
        "LIMIT" => [$offset, $perPage]
    ]));

    // Chuẩn hóa dữ liệu dự án
    foreach ($projects as &$project) {
        $project['title'] = mb_strtoupper($project['title'] ?? '', 'UTF-8');
        $project['image_url'] = $project['image_url'] ?: '5.2.Dự án/2.jpg';
        $project['excerpt'] = substr(strip_tags($project['excerpt'] ?? ''), 0, 100) . '...';
        $project['start_date'] = date('m/Y', strtotime($project['start_date'] ?? 'now'));
        $project['end_date'] = date('m/Y', strtotime($project['end_date'] ?? 'now'));
        $project['client_name'] = $project['client_name'] ?? 'Chưa xác định';
        $project['industry'] = $project['industry'] ?? 'Chưa phân loại';
        $project['description'] = htmlspecialchars_decode($project['description'] ?? '', ENT_QUOTES);
    }
    unset($project);

    // Truyền dữ liệu vào template
    $vars['projects'] = $projects;
    $vars['current_page'] = $currentPage;
    $vars['total_pages'] = $totalPages;
    $vars['setting'] = $setting;
    $vars['app'] = $app;

    echo $app->render('templates/dhv/project.html', $vars);
};

// Hàm xử lý chi tiết dự án
$projectDetailHandler = function($vars) use ($app, $jatbi, $setting) {
    // Lấy slug từ URL
    $slug = $vars['slug'] ?? '';

    // Nếu không có slug, trả về 404
    if (empty($slug)) {
        http_response_code(404);
        echo "Không tìm thấy dự án.";
        return;
    }

    // Lấy thông tin dự án theo slug
    $project = $app->get("projects", [
        "id",
        "title",
        "slug",
        "client_name",
        "description",
        "excerpt",
        "start_date",
        "end_date",
        "image_url",
        "industry"
    ], [
        "slug" => $slug,
        "status" => 'A',
        "deleted" => 0
    ]);

    // Nếu không tìm thấy dự án, trả về 404
    if (!$project) {
        http_response_code(404);
        echo "Dự án không tồn tại.";
        return;
    }

    // Chuẩn hóa dữ liệu dự án
    $project['title'] = mb_strtoupper($project['title'] ?? '', 'UTF-8');
    $project['image_url'] = $project['image_url'] ?: '5.2.Dự án/2.jpg';
    $project['start_date'] = date('m/Y', strtotime($project['start_date'] ?? 'now'));
    $project['end_date'] = date('m/Y', strtotime($project['end_date'] ?? 'now'));
    $project['description'] = $project['description'] ?: 'Chưa có thông tin chi tiết.';
    $project['client_name'] = $project['client_name'] ?? 'Chưa xác định';
    $project['industry'] = $project['industry'] ?? 'Chưa phân loại';
    $project['description'] = htmlspecialchars_decode($project['description'] ?? '', ENT_QUOTES);
    $project['excerpt'] = $project['excerpt'] ?? '';

    // Lấy danh sách hình ảnh của dự án
    $projectImages = $app->select("project_images", [
        "image_url",
        "caption"
    ], [
        "project_id" => $project['id'],
        "status" => 'A',
    ]);

    // Truyền dữ liệu vào template
    $vars['title'] = $project['title'];
    $vars['project'] = $project;
    $vars['project_images'] = $projectImages;
    $vars['setting'] = $setting;
    $vars['app'] = $app;

    echo $app->render('templates/dhv/project-detail.html', $vars);
};

// Đăng ký route
$app->router("/projects", 'GET', $projectHandler);
$app->router("/project-detail/{slug}", 'GET', $projectDetailHandler);
?>