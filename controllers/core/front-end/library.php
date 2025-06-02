<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$libraryHandler = function($vars) use ($app, $jatbi, $setting) {
    $slug = $vars['slug'] ?? 'van-hoa';

    $category = $app->get("categories", "*", [
        "slug" => $slug
    ]);

    if (!$category) {
        http_response_code(404);
        echo "Danh mục không tồn tại.";
        return;
    }

    // Phân trang
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 16;
    $offset = ($page - 1) * $limit;

    // Tổng số tài liệu để tính tổng số trang
    $totalDocuments = $app->count("resources", [
        "id_category" => $category['id']
    ]);
    $totalPages = ceil($totalDocuments / $limit);

    // Lấy danh sách tài liệu giới hạn theo phân trang
    $documents = $app->select("resources", "*", [
        "id_category" => $category['id'],
        "LIMIT" => [$offset, $limit]
    ]);

    // Lấy danh sách danh mục kèm số tài liệu
    $categories = $app->select("categories", [
        "[>]resources" => ["id" => "id_category"]
    ], [
        "categories.id",
        "categories.name",
        "categories.slug",
        "total" => Medoo\Medoo::raw("COUNT(resources.id)")
    ], [
        "GROUP" => [
            "categories.id",
            "categories.name",
            "categories.slug"
        ],
        "ORDER" => "categories.name"
    ]);

    echo $app->render('templates/dhv/library.html', [
        'documents' => $documents ?? [],
        'categories' => $categories ?? [],
        'current_category' => $category,
        'current_page' => $page,
        'total_pages' => $totalPages
    ]);
};

// Đăng ký 2 route riêng biệt, dùng chung handler
$app->router("/library", 'GET', $libraryHandler);
$app->router("/library/{slug}", 'GET', $libraryHandler);

$app->router("/library-detail", 'GET', function($vars) use ($app) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo "Thiếu ID tài liệu.";
        return;
    }

    $documents = $app->select("resources", "*", [
        "id" => $id
    ]);
    
    if (!$documents) {
        http_response_code(404);
        echo "Tài liệu không tồn tại.";
        return;
    }

    $document = $documents[0]; 

    // Lấy danh mục để hiển thị sidebar
    $categories = $app->select("categories", [
        "[>]resources" => ["id" => "id_category"]
    ], [
        "categories.id",
        "categories.name",
        "categories.slug",
        "total" => Medoo\Medoo::raw("COUNT(resources.id)")
    ], [
        "GROUP" => [
            "categories.id",
            "categories.name",
            "categories.slug"
        ],
        "ORDER" => "categories.name"
    ]);

    echo $app->render('templates/dhv/library-detail.html', [
        'document' => $document,
        'categories' => $categories ?? []
    ]);
});

