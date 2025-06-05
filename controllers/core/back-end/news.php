<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');
$common = $app->getValueData('common');
$permission = $app->getValueData('permission');

// Route danh sách tin tức
$app->router("/admin/news", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Quản lý tin tức");
    $vars['add'] = '/admin/news-add';
    $vars['deleted'] = '/admin/news-deleted';
    $vars['categories'] = $app->select("categories_news", "*", ["deleted" => 0, "status" => 'A']);
    echo $app->render('templates/backend/news-projects/news.html', $vars);
})->setPermissions(['news']);

// Route lấy dữ liệu danh sách tin tức (AJAX)
$app->router("/admin/news", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    // Lấy dữ liệu từ DataTable
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');
    $status = $_POST['status'] ?? '';
    $category_id = $_POST['category_id'] ?? '';

    // Danh sách cột hợp lệ
    $validColumns = ["checkbox", "title", "category_id", "published_at", "views", "created_at", "updated_at", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "title";

    // Điều kiện lọc cơ bản
    $where = [
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Xây dựng điều kiện AND
    $andConditions = [];

    // Lọc theo trạng thái
    if (!empty($status)) {
        $andConditions["news.status"] = $status;
    }

    // Lọc theo category
    if (!empty($category_id)) {
        $andConditions["news.category_id"] = $category_id;
    }

    // Tìm kiếm
    if (!empty($searchValue)) {
        $andConditions["OR"] = [
            "news.title[~]" => $searchValue,
            "categories_news.name[~]" => $searchValue
        ];
    }

    if (!empty($andConditions)) {
        $where["AND"] = $andConditions;
    }

    // =========================
    // Lấy tổng số dòng phù hợp (vì có JOIN nên không dùng count() trực tiếp)
    // =========================
    $ids = $app->select("news", [
        "[>]categories_news" => ["category_id" => "id"]
    ], "news.id", $where);
    $count = count($ids);

    // =========================
    // Lấy dữ liệu trang hiện tại
    // =========================
    $datas = [];

    $app->select("news", [
        "[>]categories_news" => ["category_id" => "id"]
    ], [
        'news.id',
        'news.title',
        'news.slug',
        'news.image_url',
        'news.published_at',
        'news.views',
        'news.status',
        'news.created_at',
        'news.updated_at',
        'news.content',
        'categories_news.name(category_name)',
    ], $where, function ($data) use (&$datas, $app, $jatbi) {
        $datas[] = [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "title" => $data['title'],
            "categories" => $data['category_name'] ?? 'Chưa xác định',
            "image" => $data['image_url'] ? '<img src="/uploads/images/' . $data['image_url'] . '" alt="' . $data['title'] . '" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">' : 'Chưa có ảnh',
            "published_at" => $data['published_at'] ? date('d/m/Y H:i', strtotime($data['published_at'])) : 'Chưa xuất bản',
            "views" => $data['views'],
            "created_at" => date('d/m/Y H:i', strtotime($data['created_at'])),
            "updated_at" => $data['updated_at'] ? date('d/m/Y H:i', strtotime($data['updated_at'])) : '',
            "status" => $app->component("status", ["url" => "/admin/news-status/" . $data['id'], "data" => $data['status'], "permission" => ['news.edit']]),
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['news.edit'],
                        'action' => ['data-url' => '/admin/news-edit/' . $data['id'], 'data-action' => 'modal']
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['news.deleted'],
                        'action' => ['data-url' => '/admin/news-deleted?box=' . $data['id'], 'data-action' => 'modal']
                    ],
                ]
            ]),
        ];
    });

    // Trả kết quả về DataTable
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $datas
    ]);
})->setPermissions(['news']);
