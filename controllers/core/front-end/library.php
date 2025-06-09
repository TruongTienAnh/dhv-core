<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$libraryHandler = function($vars) use ($app, $jatbi, $setting) {
    $slug = $vars['slug'] ?? '';

    // Lấy từ khóa tìm kiếm
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

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

    // Xây dựng điều kiện lọc tài liệu
    $conditions = [
        "id_category" => $category['id']
    ];

    // Thêm điều kiện tìm kiếm nếu có từ khóa
    if ($searchQuery !== '') {
        $conditions["OR"] = [
            "title[~]" => "%{$searchQuery}%",
            "description[~]" => "%{$searchQuery}%"
        ];
    }

    // Tổng số tài liệu để tính tổng số trang
    $totalDocuments = $app->count("resources", $conditions);
    $totalPages = ceil($totalDocuments / $limit);

    // Lấy danh sách tài liệu giới hạn theo phân trang và điều kiện tìm kiếm
    $documents = $app->select("resources", "*", [
        "AND" => $conditions,
        "LIMIT" => [$offset, $limit]
    ]);

    // Lấy danh sách danh mục kèm số tài liệu
    $categories = $app->select("categories", [
        "[>]resources" => ["id" => "id_category"]
    ], [
        "categories.id",
        "categories.name",
        "categories.slug",
        "total" => $app->raw("COUNT(resources.id)")
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
        'total_pages' => $totalPages,
        'search_query' => $searchQuery, 
    ]);
};

    
// Đăng ký 2 route riêng biệt, dùng chung handler   
$app->router("/library", 'GET', $libraryHandler);
$app->router("/library/{slug}", 'GET', $libraryHandler);

$app->router("/library-detail/library-add/{slug}", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Tải tài liệu");
    echo $app->render('templates/dhv/library-post.html', $vars, 'global');
});

$app->router("/library-detail/library-add/{slug}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $name = $app->xss($_POST['name'] ?? '');
    $phone = $app->xss($_POST['phone'] ?? '');
    $email = $app->xss($_POST['email'] ?? '');

    $slug = $vars['slug'] ?? '';

    if (empty($name) || empty($phone) || empty($email)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống")]);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Email không hợp lệ")]);
        return;
    }

    // Lấy tài liệu theo slug
    $resources = $app->select("resources", "file_url", ["slug" => $slug]);

    var_dump($resources);

    // Kiểm tra xem có kết quả không
    if (empty($resources)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Tài liệu không tồn tại")]);
        return;
    }

    $link_pdf = "template/" . $resources[0];

    if ($link_pdf && !preg_match('#^https?://#', $link_pdf)) {
        if (strpos($link_pdf, 'uploads/library/') !== 0) {
            $link_pdf = '/uploads/library/' . ltrim($link_pdf, '/');
        } else {
            $link_pdf = '/' . ltrim($link_pdf, '/');
        }
    }


    // Lưu thông tin người dùng đăng ký tải
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
            "file" => $link_pdf
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
});


// $app->router("/library-detail/{slug}", 'GET', function($vars) use ($app) {
//     $slug = $vars['slug'] ?? null;

//     if (!$slug) {
//         http_response_code(400);
//         echo "Thiếu slug tài liệu.";
//         return;
//     }

//     // Truy vấn theo slug
//     $documents = $app->select("resources", "*", [
//         "slug" => $slug
//     ]);

//     if (!$documents) {
//         http_response_code(404);
//         echo "Tài liệu không tồn tại.";
//         return;
//     }

//     $document = $documents[0];

//     // Lấy danh mục để hiển thị sidebar
//     $categories = $app->select("categories", [
//         "[>]resources" => ["id" => "id_category"]
//     ], [
//         "categories.id",
//         "categories.name",
//         "categories.slug",
//         "total" => Medoo\Medoo::raw("COUNT(resources.id)")
//     ], [
//         "GROUP" => [
//             "categories.id",
//             "categories.name",
//             "categories.slug"
//         ],
//         "ORDER" => "categories.name"
//     ]);

//     echo $app->render('templates/dhv/library-detail.html', [
//         'document' => $document,
//         'categories' => $categories ?? []
//     ]);
// });

$app->router("/library-detail/{slug}", 'GET', function($vars) use ($app) {
    $slug = $vars['slug'] ?? null;

    if (!$slug) {
        http_response_code(400);
        echo "Thiếu slug tài liệu.";
        return;
    }

    // Nếu có từ khóa tìm kiếm
    $query = $_GET['q'] ?? null;

    if ($query) {
        // Tìm các tài liệu có tiêu đề chứa từ khóa
        $documents = $app->select("resources", "*", [
            "title[~]" => $query
        ]);

        echo $app->render('templates/dhv/library-search.html', [
            'query' => $query,
            'results' => $documents
        ]);
        return;
    }

    // Truy vấn chi tiết tài liệu theo slug
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
        'categories' => $categories ?? [],
        'search_query' => $_GET['search'] ?? '' // ← dòng này rất quan trọng
    ]);

});


