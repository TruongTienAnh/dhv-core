<?php
if (!defined('ECLO')) die("Hacking attempt");

// Include file library.php để sử dụng hàm generateSlug()
require_once __DIR__ . '/library.php';

$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

// Route cho quản lý tác giả
$app->router("/admin/author", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Quản lý chuyên gia");
    echo $app->render('templates/backend/author/author.html', $vars);
})->setPermissions(['author']);

$app->router("/admin/author", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';

    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');

    // Danh sách cột hợp lệ để order
    $validColumns = ["checkbox", "name", "image_url", "content", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "name";

    // Điều kiện tìm kiếm
    $where = [
        "AND" => [
            "OR" => [
                "name[~]" => $searchValue,
                "image_url[~]" => $searchValue,
                "content[~]" => $searchValue,
            ]
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Đếm tổng số bản ghi thỏa điều kiện tìm kiếm
    $count = $app->count("author_boxes", ["AND" => $where["AND"]]);
    error_log("Total count: " . $count);

    // Lấy dữ liệu từ bảng author_boxes
    $datas = $app->select("author_boxes", [
        "id",
        "name",
        "image_url",
        "content"
    ], $where) ?? [];

    // Format dữ liệu trả về cho DataTables
    $formattedData = array_map(function($data) use ($app, $jatbi, $setting) {
        $content = $data['content'] ? str_replace("\n", "<br>", wordwrap($data['content'], 50, "<br>", true)) : $jatbi->lang("Không có nội dung");
        $name = $data['name'] ? str_replace("\n", "<br>", wordwrap($data['name'], 40, "<br>", true)) : $jatbi->lang("Không có tên");
        // Xử lý đường dẫn ảnh
        $imageSrc = '';
        if ($data['image_url']) {
            $imageSrc = htmlspecialchars($setting['template'] . '/' . $data['image_url']);
        } else {
            $imageSrc = $jatbi->lang("Không xác định");
        }

        return [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "name" => $name,
            "image_url" => $imageSrc ? '<img src="' . $imageSrc . '" width="50">' : $jatbi->lang("Không xác định"),
            "content" => $content,
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['author'],
                        'action' => [
                            'data-url' => '/admin/author-edit?id=' . $data['id'],
                            'data-action' => 'modal'
                        ]
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['author'],
                        'action' => [
                            'data-url' => '/admin/author-deleted?id=' . $data['id'],
                            'data-action' => 'modal'
                        ]
                    ]
                ]
            ])
        ];
    }, $datas);

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $formattedData
    ]);
})->setPermissions(['author']);

// Thêm tác giả
$app->router("/admin/author-add", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title1'] = $jatbi->lang("Thêm tác giả");
    echo $app->render('templates/backend/author/author-post.html', $vars, 'global');
})->setPermissions(['author']);

$app->router("/admin/author-add", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy dữ liệu từ form (xử lý XSS)
    $name = $app->xss($_POST['name'] ?? '');
    $content = $app->xss($_POST['content'] ?? '');
    $imgFile = $_FILES['image_url'] ?? null;

    // Kiểm tra dữ liệu bắt buộc
    if (empty($name) || empty($content) || !$imgFile) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
        return;
    }

    // Chuẩn bị thư mục upload
    $uploadDir = __DIR__ . '/../../../templates/uploads/author/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Lưu ảnh minh họa
    $imgExt = pathinfo($imgFile['name'], PATHINFO_EXTENSION);
    $imgFilename = time() . '_author.' . $imgExt;
    $imgPath = $uploadDir . $imgFilename;
    if (!move_uploaded_file($imgFile['tmp_name'], $imgPath)) {
        error_log("Upload failed for file: " . $imgFile['name'] . ", error: " . print_r(error_get_last(), true));
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải ảnh minh họa thất bại")]);
        return;
    }

    // Chuẩn bị dữ liệu lưu
    $insert = [
        "name" => $name,
        "content" => $content,
        "image_url" => 'uploads/author/' . $imgFilename
    ];

    // Debug: Log dữ liệu trước khi lưu
    error_log("Insert data: " . print_r($insert, true));

    try {
        // Lưu vào DB (bảng `author_boxes`)
        $app->insert("author_boxes", $insert);

        echo json_encode(["status" => "success", "content" => $jatbi->lang("Thêm thành công")]);
    } catch (Exception $e) {
        error_log("Insert error: " . $e->getMessage());
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['author']);

// Xóa tác giả
$app->router("/admin/author-deleted", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title'] = $jatbi->lang("Xóa tác giả");
    echo $app->render('templates/common/deleted.html', $vars, 'global');
})->setPermissions(['author']);

$app->router("/admin/author-deleted", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $idList = [];

    if (!empty($_GET['id'])) {
        $idList[] = $app->xss($_GET['id']);
    } elseif (!empty($_GET['box'])) {
        $idList = array_map('trim', explode(',', $app->xss($_GET['box'])));
    }

    if (empty($idList)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Thiếu ID tác giả để xóa")]);
        return;
    }

    try {
        $deletedCount = 0;
        $errors = [];

        foreach ($idList as $id) {
            if (empty($id)) continue;

            $deleted = $app->delete("author_boxes", ["id" => $id]);

            if ($deleted) {
                $deletedCount++;
            } else {
                $errors[] = $id;
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                "status" => "error",
                "content" => $jatbi->lang("Một số tác giả xóa thất bại"),
                "errors" => $errors
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "content" => $jatbi->lang("Đã xóa thành công") . " $deletedCount " . $jatbi->lang("tác giả")
            ]);
        }

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['author']);

// Sửa tác giả
$app->router("/admin/author-edit", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title1'] = $jatbi->lang("Sửa tác giả");

    $id = isset($_GET['id']) ? $app->xss($_GET['id']) : null;

    if (!$id) {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
        return;
    }

    // Lấy dữ liệu tác giả từ DB
    $vars['data'] = $app->select("author_boxes", "*", ["id" => $id])[0] ?? null;

    if ($vars['data']) {
        echo $app->render('templates/backend/author/author-post.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['author']);

$app->router("/admin/author-edit", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy ID tác giả từ request
    $id = isset($_POST['id']) ? $app->xss($_POST['id']) : null;
    if (!$id) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("ID không hợp lệ")]);
        return;
    }

    // Lấy dữ liệu cũ từ DB
    $data = $app->select("author_boxes", "*", ["id" => $id]);
    if (!$data) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Không tìm thấy dữ liệu")]);
        return;
    }

    // Lấy dữ liệu từ form (xử lý XSS)
    $name = $app->xss($_POST['name'] ?? '');
    $content = $app->xss($_POST['content'] ?? '');
    $imgFile = $_FILES['image_url'] ?? null;

    // Kiểm tra dữ liệu bắt buộc
    if (empty($name) || empty($content)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
        return;
    }

    // Chuẩn bị thư mục upload
    $uploadDir = __DIR__ . '/../../../templates/uploads/author/';

    // Kiểm tra quyền ghi thư mục
    if (!is_writable($uploadDir)) {
        error_log("Directory not writable: $uploadDir at " . date('Y-m-d H:i:s'));
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Thư mục upload không có quyền ghi")]);
        return;
    }

    // Xử lý upload file
    $imagePath = $data[0]['image_url'] ?? null; // Giữ ảnh cũ nếu không upload
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
        $imgExt = pathinfo($_FILES['image_url']['name'], PATHINFO_EXTENSION);
        $imgFilename = time() . '_author.' . $imgExt;
        $imgPath = $uploadDir . $imgFilename;

        if (!move_uploaded_file($_FILES['image_url']['tmp_name'], $imgPath)) {
            error_log("Upload failed for file: " . $_FILES['image_url']['name'] . ", error: " . print_r(error_get_last(), true) . " at " . date('Y-m-d H:i:s'));
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải ảnh minh họa thất bại")]);
            return;
        }

        // Cập nhật đường dẫn ảnh trong DB
        $imagePath = 'uploads/author/' . $imgFilename;
    }

    // Dữ liệu cập nhật
    $update = [
        "name" => $name,
        "content" => $content,
        "image_url" => $imagePath
    ];

    // Debug: Log dữ liệu trước khi cập nhật
    error_log("Update data: " . print_r($update, true) . " at " . date('Y-m-d H:i:s'));

    try {
        $app->update("author_boxes", $update, ["id" => $id]);

        echo json_encode(["status" => "success", "content" => $jatbi->lang("Cập nhật thành công")]);
    } catch (Exception $e) {
        error_log("Update error: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['author']);