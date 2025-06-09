<?php
if (!defined('ECLO')) die("Hacking attempt");

// Include file library.php để sử dụng hàm generateSlug()
require_once __DIR__ . '/library.php';

$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

// Route cho quản lý dịch vụ
$app->router("/admin/services", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Quản lý dịch vụ");
    echo $app->render('templates/backend/services/services.html', $vars);
})->setPermissions(['services']);

$app->router("/admin/services", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';
    $status = $_POST['status'] ?? '';   

    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');

    // Danh sách cột hợp lệ để order (đồng bộ với template)
    $validColumns = ["checkbox", "title", "description", "image", "status", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "title";

    // Điều kiện tìm kiếm
    $where = [
        "AND" => [
            "OR" => [
                "title[~]" => $searchValue,
                "description[~]" => $searchValue,
                "image[~]" => $searchValue,
            ]
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Thêm điều kiện lọc status
    if (!empty($status)) {
        $where["AND"]["status"] = $status;
    }

    // Đếm tổng số bản ghi thỏa điều kiện tìm kiếm
    $count = $app->count("services", ["AND" => $where["AND"]]);
    error_log("Total count: " . $count);

    // Lấy dữ liệu từ bảng services
    $datas = $app->select("services", [
        "id",
        "title",
        "description",
        "image",
        "status"
    ], $where) ?? [];


    // Format dữ liệu trả về cho DataTables
    $formattedData = array_map(function($data) use ($app, $jatbi, $setting) {
        $description = $data['description'] ? str_replace("\n", "<br>", wordwrap($data['description'], 50, "<br>", true)) : $jatbi->lang("Không có nội dung");
        $title = $data['title'] ? str_replace("\n", "<br>", wordwrap($data['title'], 40, "<br>", true)) : $jatbi->lang("Không có tiêu đề");
        // Xử lý đường dẫn ảnh
        $imageSrc = '';
        if ($data['image']) {
    
            $imageSrc = htmlspecialchars($setting['template'] . '/' . $data['image']);
        } else {
            $imageSrc = $jatbi->lang("Không xác định");
        }

        return [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "title" => $title,
            "description" => $description,
            "image" => $imageSrc ? '<img src="' . $imageSrc . '" width="50">' : $jatbi->lang("Không xác định"),
            "status" => $app->component("status", [
                "url" => "/admin/services-status/" . $data['id'],
                "data" => $data['status'],
                "permission" => ['services']
            ]),
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['services'],
                        'action' => [
                            'data-url' => '/admin/services-edit?id=' . $data['id'],
                            'data-action' => 'modal'
                        ]
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['services'],
                        'action' => [
                            'data-url' => '/admin/services-deleted?id=' . $data['id'],
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
})->setPermissions(['services']);



// Thêm dịch vụ
$app->router("/admin/services-add", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title1'] = $jatbi->lang("Thêm dịch vụ");
    $vars['categories'] = $app->select("categories", ['id', 'name']);
    echo $app->render('templates/backend/services/services-post.html', $vars, 'global');
})->setPermissions(['services']);

$app->router("/admin/services-add", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy dữ liệu từ form (xử lý XSS)
    $title = $app->xss($_POST['title'] ?? '');
    $description = $app->xss($_POST['description'] ?? '');
    $category = $app->xss($_POST['category'] ?? '');
    $type = $app->xss($_POST['type'] ?? '');
    $imgFile = $_FILES['image'] ?? null;

    // Kiểm tra dữ liệu bắt buộc
    if (empty($title) || empty($description) || empty($category) || empty($type) || !$imgFile) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
        return;
    }

    // Kiểm tra giá trị type hợp lệ
    if (!in_array($type, ['Doanh nghiệp', 'Tổ chức sự kiện'])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Loại dịch vụ không hợp lệ")]);
        return;
    }

    // Kiểm tra category_id tồn tại
    if (!$app->has("categories", ["id" => $category])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Danh mục không tồn tại")]);
        return;
    }

    $slug = generateSlug($title);

    // Chuẩn bị thư mục upload
    $uploadDir = __DIR__ . '/../../../templates/uploads/services/';
    // $uploadDir = $setting['template'].'/uploads/services/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Lưu ảnh minh họa
    $imgExt = pathinfo($imgFile['name'], PATHINFO_EXTENSION);
    $imgFilename = time() . '_service.' . $imgExt;
    $imgPath = $uploadDir . $imgFilename;
    if (!move_uploaded_file($imgFile['tmp_name'], $imgPath)) {
        error_log("Upload failed for file: " . $imgFile['name'] . ", error: " . print_r(error_get_last(), true));
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải ảnh minh họa thất bại")]);
        return;
    }

    // Chuẩn bị dữ liệu lưu
    $insert = [
        "title" => $title,
        "description" => $description,
        "category_id" => $category,
        "type" => $type,
        "image" => 'uploads/services/' . $imgFilename,
        "slug" => $slug,
        "status" => 'A',
        // "created_at" => date("Y-m-d H:i:s")
    ];

    // Debug: Log dữ liệu trước khi lưu
    error_log("Insert data: " . print_r($insert, true));

    try {
        // Lưu vào DB (bảng `services`)
        $app->insert("services", $insert);

        echo json_encode(["status" => "success", "content" => $jatbi->lang("Thêm thành công")]);
    } catch (Exception $e) {
        error_log("Insert error: " . $e->getMessage());
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['services']);

// Xóa dịch vụ
$app->router("/admin/services-deleted", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title'] = $jatbi->lang("Xóa dịch vụ");
    echo $app->render('templates/common/deleted.html', $vars, 'global');
})->setPermissions(['services']);

$app->router("/admin/services-deleted", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $idList = [];

    if (!empty($_GET['id'])) {
        $idList[] = $app->xss($_GET['id']);
    } elseif (!empty($_GET['box'])) {
        $idList = array_map('trim', explode(',', $app->xss($_GET['box'])));
    }

    if (empty($idList)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Thiếu ID dịch vụ để xóa")]);
        return;
    }

    try {
        $deletedCount = 0;
        $errors = [];

        foreach ($idList as $id) {
            if (empty($id)) continue;

            $deleted = $app->delete("services", ["id" => $id]);

            if ($deleted) {
                $deletedCount++;
            } else {
                $errors[] = $id;
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                "status" => "error",
                "content" => $jatbi->lang("Một số dịch vụ xóa thất bại"),
                "errors" => $errors
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "content" => $jatbi->lang("Đã xóa thành công") . " $deletedCount " . $jatbi->lang("dịch vụ")
            ]);
        }

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['services']);
// Sửa dịch vụ
$app->router("/admin/services-edit", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title1'] = $jatbi->lang("Sửa dịch vụ");

    $id = isset($_GET['id']) ? $app->xss($_GET['id']) : null;

    if (!$id) {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
        return;
    }

    // Lấy dữ liệu dịch vụ từ DB
    $vars['data'] = $app->select("services", "*", ["id" => $id])[0] ?? null;

    // Lấy danh sách danh mục
    $vars['categories'] = $app->select("categories", ["id", "name"]);

    if ($vars['data']) {
        echo $app->render('templates/backend/services/services-post.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['services']);

$app->router("/admin/services-edit", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy ID dịch vụ từ request
    $id = isset($_POST['id']) ? $app->xss($_POST['id']) : null;
    if (!$id) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("ID không hợp lệ")]);
        return;
    }

    // Lấy dữ liệu cũ từ DB
    $data = $app->select("services", "*", ["id" => $id]);
    if (!$data) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Không tìm thấy dữ liệu")]);
        return;
    }

    // Lấy dữ liệu từ form (xử lý XSS)
    $title = $app->xss($_POST['title'] ?? '');
    $description = $app->xss($_POST['description'] ?? '');
    $category = $app->xss($_POST['category'] ?? '');
    $type = $app->xss($_POST['type'] ?? '');
    $imgFile = $_FILES['image'] ?? null;

    // Kiểm tra dữ liệu bắt buộc
    if (empty($title) || empty($description) || empty($category) || empty($type) || !$imgFile) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
        return;
    }

    // Kiểm tra giá trị type hợp lệ
    if (!in_array($type, ['Doanh nghiệp', 'Tổ chức sự kiện'])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Loại dịch vụ không hợp lệ")]);
        return;
    }

    // Kiểm tra category_id tồn tại
    if (!$app->has("categories", ["id" => $category])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Danh mục không tồn tại")]);
        return;
    }

    $slug = generateSlug($title);

    // Chuẩn bị thư mục upload
  $uploadDir = __DIR__ . '/../../../templates/uploads/services/';

    // Kiểm tra quyền ghi thư mục
    if (!is_writable($uploadDir)) {
        error_log("Directory not writable: $uploadDir at " . date('Y-m-d H:i:s'));
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Thư mục upload không có quyền ghi")]);
        return;
    }

    // Xử lý upload file
    $imagePath = $data[0]['image'] ?? null; // Giữ ảnh cũ nếu không upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imgExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imgFilename = time() . '_service.' . $imgExt;
        $imgPath = $uploadDir . $imgFilename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imgPath)) {
            error_log("Upload failed for file: " . $_FILES['image']['name'] . ", error: " . print_r(error_get_last(), true) . " at " . date('Y-m-d H:i:s'));
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải ảnh minh họa thất bại")]);
            return;
        }

        // Cập nhật đường dẫn ảnh trong DB
        $imagePath = 'uploads/services/' . $imgFilename;
    }

    // Dữ liệu cập nhật
    $update = [
        "title" => $title,
        "description" => $description,
        "category_id" => $category,
        "type" => $type,
        "image" => $imagePath,
        "slug" => $slug,
        "status" => 'A',

    ];

    // Debug: Log dữ liệu trước khi cập nhật
    error_log("Update data: " . print_r($update, true) . " at " . date('Y-m-d H:i:s'));

    try {
        $app->update("services", $update, ["id" => $id]);

        echo json_encode(["status" => "success", "content" => $jatbi->lang("Cập nhật thành công")]);
    } catch (Exception $e) {
        error_log("Update error: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['services']);

// Cập nhật trạng thái dịch vụ
$app->router("/admin/services-status/{id}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
    $data = $app->get("services", "*", ["id" => $vars['id']]);
    if ($data) {
        if ($data['status'] === 'A') {
            $status = "D";
        } elseif ($data['status'] === 'D') {
            $status = "A";
        }
        $app->update("services", ["status" => $status], ["id" => $data['id']]);
        $jatbi->logs('services', 'services-status', $data);
        echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
    } else {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Không tìm thấy dữ liệu")]);
    }
})->setPermissions(['services']);