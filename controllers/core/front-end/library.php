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
    $vars['title'] = $jatbi->lang("Tài tài liệu");
    echo $app->render('templates/dhv/library-post.html', $vars, 'global');
});

// $app->router("/library-detail/library-add", 'POST', function($vars) use ($app, $jatbi) {
//     $app->header(['Content-Type' => 'application/json']);

//     $title = $app->xss($_POST['title'] ?? '');
//     $description = $app->xss($_POST['description'] ?? '');
//     $category = $app->xss($_POST['category'] ?? '');
//     $pdfFile = $_FILES['file'] ?? null;
//     $imgFile = $_FILES['image'] ?? null;

//     if (empty($title) || empty($category) || !$pdfFile || !$imgFile) {
//         echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
//         return;
//     }

//     $slug = generateSlug($title);
//     $uploadDir = __DIR__ . '/../../uploads/library/';
//     if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

//     // Lưu file PDF
//     $pdfFilename = time() . '_' . basename($pdfFile['name']);
//     $pdfPath = $uploadDir . $pdfFilename;
//     if (!move_uploaded_file($pdfFile['tmp_name'], $pdfPath)) {
//         echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải file PDF thất bại")]);
//         return;
//     }

//     // Lưu ảnh minh họa
//     $imgExt = pathinfo($imgFile['name'], PATHINFO_EXTENSION);
//     $imgFilename = time() . '_cover.' . $imgExt;
//     $imgPath = $uploadDir . $imgFilename;
//     if (!move_uploaded_file($imgFile['tmp_name'], $imgPath)) {
//         echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải ảnh minh họa thất bại")]);
//         return;
//     }

//     // Lưu dữ liệu vào DB
//     $insert = [
//         "title" => $title,
//         "description" => $description,
//         "file_url" => 'uploads/library/' . $pdfFilename,
//         "img_url" => 'uploads/library/' . $imgFilename,
//         "id_category" => $category,
//         "created_at" => date("Y-m-d H:i:s"),
//         "slug" => $slug,
//     ];

//     try {
//         $app->insert("resources", $insert);
//         echo json_encode(["status" => "success", "content" => $jatbi->lang("Thêm thành công")]);
//     } catch (Exception $e) {
//         echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
//     }
// });


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

