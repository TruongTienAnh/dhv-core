<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$libraryHandler = function($vars) use ($app, $jatbi, $setting) {
    $slug = $vars['slug'] ?? '';

    if (empty($slug)) {
        // Lấy danh mục đầu tiên nếu slug rỗng
        $category = $app->get("categories", "*", [
            "ORDER" => ["id" => "ASC"]
        ]);
        if (!$category) {
            http_response_code(404);
            echo "Không có danh mục nào.";
            return;
        }
    } else {
        // Tìm danh mục theo slug
        $category = $app->get("categories", "*", [
            "slug" => $slug
        ]);
        if (!$category) {
            http_response_code(404);
            echo "Danh mục không tồn tại.";
            return;
        }
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

$app->router("/library-detail/library-add", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Tải tài liệu");
    echo $app->render('templates/dhv/library-post.html', $vars, 'global');
});

$app->router("/library-detail/library-add", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $name = $app->xss($_POST['name'] ?? '');
    $phone = $app->xss($_POST['phone'] ?? '');
    $email = $app->xss($_POST['email'] ?? '');
    $slug = $app->xss($_POST['slug'] ?? '');

    echo $slug ;
    exit ;


    if (empty($name) || empty($phone) || empty($email)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống")]);
        return;
    }

    // Kiểm tra định dạng email hợp lệ
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Email không hợp lệ")]);
        return;
    }

    // Lưu dữ liệu vào DB
    $insert = [
        "name" => $name,
        "phone" => $phone,
        "email" => $email,
        
    ];

    try {
        $app->insert("appointments", $insert);
        echo json_encode([
            "status" => "success",
            "content" => $jatbi->lang("Tải thành công"),
            "file" => "/path/to/your/file.pdf" 
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }

});


$app->router("/library-detail/{slug}", 'GET', function($vars) use ($app) {
    $slug = $vars['slug'] ?? null;

    if (!$slug) {
        http_response_code(400);
        echo "Thiếu slug tài liệu.";
        return;
    }

    // Truy vấn theo slug
    $documents = $app->select("resources", "*", [
        "slug" => $slug
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

