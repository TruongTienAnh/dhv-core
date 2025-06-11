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
    // Thiết lập header JSON
    $app->header(['Content-Type' => 'application/json']);

    // Lấy dữ liệu từ form
    $name = $app->xss($_POST['name'] ?? '');
    $phone = $app->xss($_POST['phone'] ?? '');
    $email = $app->xss($_POST['email'] ?? '');
    $slug = $vars['slug'] ?? '';

    // Kiểm tra dữ liệu
    if (empty($name) || empty($phone) || empty($email)) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Vui lòng điền đầy đủ thông tin")
        ]);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Email không hợp lệ")
        ]);
        return;
    }

    // Lấy thông tin tài liệu từ database
    $resources = $app->select("resources", ["file_url"], ["slug" => $slug]);

    // Kiểm tra kết quả truy vấn
    if (empty($resources) || empty($resources[0]['file_url'])) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Tài liệu không tồn tại")
        ]);
        return;
    }

    // Lấy đường dẫn file
    $file_path = $resources[0]['file_url'];
    $upload_base_path = 'templates'; // Đảm bảo thư mục này đúng với cấu trúc server
    $server_file_path = $upload_base_path . '/' . ltrim($file_path, '/');

    // Debug: Ghi log đường dẫn
    error_log('Database file_path: ' . $file_path);
    error_log('Server file path: ' . $server_file_path);

    // Kiểm tra file tồn tại
    if (!file_exists($server_file_path)) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("File không tồn tại trên server"),
            "debug" => "Checked path: $server_file_path"
        ]);
        return;
    }

    // Tạo tên file gợi ý (dựa trên slug hoặc tên file từ database)
    $filename = basename($file_path); // Lấy tên file từ đường dẫn
    if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'pdf') {
        $filename = 'document_' . $slug . '.pdf'; // Fallback tên file
    }

    // Tạo URL đầy đủ cho file
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $link_pdf = $base_url . '/templates/' . ltrim($file_path, '/');
    error_log('PDF URL: ' . $link_pdf);

    // Lưu thông tin người dùng
    $insert = [
        "name" => $name,
        "phone" => $phone,
        "email" => $email,
        "slug" => $slug,
        "created_at" => date('Y-m-d H:i:s')
    ];

    try {
        $app->insert("appointments", $insert);
        echo json_encode([
            "status" => "download", // Sửa thành "download" để khớp với sendAjaxRequest
            "content" => $jatbi->lang("Tải tài liệu thành công"),
            "file" => $link_pdf,
            "filename" => $filename
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "content" => "Lỗi: " . $e->getMessage()
        ]);
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


