<?php
if (!defined('ECLO'))
    die("Hacking attempt");

// Include file library.php to use generateSlug()
require_once __DIR__ . '/library.php';

$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

// Route to manage service details (list view)
$app->router("/admin/services-detail", 'GET', function ($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Quản lý chi tiết dịch vụ");
    echo $app->render('templates/backend/services/services-detail.html', $vars);
})->setPermissions(['services-detail']);

$app->router("/admin/services-detail", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';
    $objectFilter = $_POST['object'] ?? '';

    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');

    // Valid columns for ordering (sync with template)
    $validColumns = ["checkbox", "title", "description", "service_id", "rate", "min_price", "max_price",  "original_min_price","original_max_price", "discount", "object", "content", "author_box_id", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "service_id";

    // Search conditions
    $where = [
        "AND" => [
            "OR" => [
                "service_id[~]" => $searchValue,
                "rate[~]" => $searchValue,
                "min_price[~]" => $searchValue,
                "max_price[~]" => $searchValue,
                "original_min_price[~]" => $searchValue,
                "original_max_price[~]" => $searchValue,
                "discount[~]" => $searchValue,
                "object[~]" => $searchValue,
                "content[~]" => $searchValue,
                "author_box_id[~]" => $searchValue,
            ]
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Add object filter
    if (!empty($objectFilter)) {
        $where["AND"]["object"] = $objectFilter;
    }

    // Count total records matching the search conditions
    $count = $app->count("services_detail", ["AND" => $where["AND"]]);
    error_log("Total count: " . $count);

    // Fetch data from services_detail table with all columns
    $datas = $app->select("services_detail", [
        "id",
        "service_id",
        "title",
        "description_title",
        "rate",
        "min_price",
        "max_price",
        "original_min_price",
        "original_max_price",
        "discount",
        "object",
        "content",
        "author_box_id",
        "service_id" 
    ], $where) ?? [];
    error_log("Fetched data: " . print_r($datas, true)); // Debug raw data

    // Format data for DataTables
    $formattedData = array_map(function($data) use ($app, $jatbi, $setting) {
        $content = $data['content'] ? str_replace("\n", "<br>", wordwrap($data['content'], 50, "<br>", true)) : $jatbi->lang("Không có nội dung");
        $object = $data['object'] ? $data['object'] : $jatbi->lang("Không xác định");

        return [
            "checkbox" => $app->component("box", ["data" => $data['id']]), 
            "service_id" => $data['service_id'] ?? 'N/A',
            "title" => $data['title'] ?? $jatbi->lang("Chưa có tiêu đề"),
            "description_title" => $data['description_title'] ?? $jatbi->lang("Chưa có mô tả"),
            "rate" => $data['rate'] !== null ? $data['rate'] : $jatbi->lang("Chưa đánh giá"),
            "min_price" => $data['min_price'] !== null ? number_format($data['min_price']) : $jatbi->lang("Không xác định"),
            "max_price" => $data['max_price'] !== null ? number_format($data['max_price']) : $jatbi->lang("Không xác định"),
            "original_min_price" => $data['original_min_price'] !== null ? number_format($data['original_min_price']) : $jatbi->lang("Không xác định"),
            "original_max_price" => $data['original_max_price'] !== null ? number_format($data['original_max_price']) : $jatbi->lang("Không xác định"),
            "discount" => $data['discount'] !== null ? $data['discount'] . '%' : $jatbi->lang("Không có"),
            "object" => $object,
            "content" => $content,
            "author_box_id" => $data['author_box_id'] !== null ? $data['author_box_id'] : $jatbi->lang("Không xác định"),
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['services-detail'],
                        'action' => [
                            'data-url' => '/admin/services-detail-edit?id=' . ($data['id'] ?? ''),
                            'data-action' => 'modal'
                        ]
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['services-detail'],
                        'action' => [
                            'data-url' => '/admin/services-detail-deleted?id=' . ($data['id'] ?? ''),
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
})->setPermissions(['services-detail']);
// Thêm chi tiết dịch vụ
$app->router("/admin/services-detail-add", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title1'] = $jatbi->lang("Thêm chi tiết dịch vụ");
    $vars['services'] = $app->select("services", ['id', 'title']); // Lấy danh sách dịch vụ để chọn service_id
    $vars['author_boxes'] = $app->select("author_boxes", ['id', 'name']); // Lấy danh sách author_boxes để chọn author_box_id
    echo $app->render('templates/backend/services/services-detail-post.html', $vars, 'global');
})->setPermissions(['services-detail']);
$app->router("/admin/services-detail-add", 'POST', function($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy dữ liệu từ form (xử lý XSS)
    $service_id = $app->xss($_POST['service_id'] ?? '');
    $title = $app->xss($_POST['title'] ?? '');
    $description_title = $app->xss($_POST['description_title'] ?? '');
    $rate = $app->xss($_POST['rate'] ?? '');
    $min_price = $app->xss($_POST['min_price'] ?? '');
    $max_price = $app->xss($_POST['max_price'] ?? '');
    $original_min_price = $app->xss($_POST['original_min_price'] ?? '');
    $original_max_price = $app->xss($_POST['original_max_price'] ?? '');
    $discount = $app->xss($_POST['discount'] ?? '');
    $object = $app->xss($_POST['object'] ?? '');
    $content = $app->xss($_POST['content'] ?? '');
    $author_box_id = $app->xss($_POST['author_box_id'] ?? '');

    // Kiểm tra dữ liệu bắt buộc
    $required_fields = ['service_id', 'title', 'description_title', 'rate', 'min_price', 'max_price', 'original_min_price', 'original_max_price', 'object'];
    $empty_fields = array_filter($required_fields, fn($field) => empty($_POST[$field]));
    if (!empty($empty_fields)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
        return;
    }

    // Kiểm tra service_id tồn tại
    if (!$app->has("services", ["id" => $service_id])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Dịch vụ không tồn tại")]);
        return;
    }

    // Kiểm tra xem service_id đã có chi tiết chưa
    if ($app->has("services_detail", ["service_id" => $service_id])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Dịch vụ này đã có chi tiết. Mỗi dịch vụ chỉ được có một chi tiết.")]);
        return;
    }

    // Kiểm tra author_box_id tồn tại nếu được cung cấp
    if (!empty($author_box_id) && !$app->has("author_boxes", ["id" => $author_box_id])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Tác giả không tồn tại")]);
        return;
    }

    // Kiểm tra rate hợp lệ (0-5)
    if (!is_numeric($rate) || $rate < 0 || $rate > 5) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Đánh giá phải từ 0 đến 5")]);
        return;
    }

    // Kiểm tra giá hợp lệ
    if (!is_numeric($min_price) || $min_price < 0 || !is_numeric($max_price) || $max_price < 0) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Giá không hợp lệ")]);
        return;
    }

    // Chuẩn bị dữ liệu lưu
    $insert = [
        "service_id" => $service_id,
        "title" => $title,
        "description_title" => $description_title,
        "rate" => $rate,
        "min_price" => $min_price,
        "max_price" => $max_price,
        "original_min_price" => $original_min_price ?: $min_price,
        "original_max_price" => $original_max_price ?: $max_price,
        "discount" => $discount ?: null,
        "object" => $object,
        "content" => $content ?: null,
        "author_box_id" => $author_box_id ?: null,
    ];

    // Debug: Log dữ liệu trước khi lưu
    error_log("Insert data: " . print_r($insert, true));

    try {
        // Lưu vào DB (bảng `services_detail`)
        $app->insert("services_detail", $insert);
        echo json_encode(["status" => "success", "content" => $jatbi->lang("Thêm thành công")]);
    } catch (Exception $e) {
        error_log("Insert error: " . $e->getMessage());
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['services-detail']);
// Sửa chi tiết dịch vụ
$app->router("/admin/services-detail-edit", 'GET', function ($vars) use ($app, $jatbi) {
    $vars['title1'] = $jatbi->lang("Sửa chi tiết dịch vụ");

    $id = isset($_GET['id']) ? $app->xss($_GET['id']) : null;

    if (!$id) {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
        return;
    }

    // Fetch service detail data from DB
    $vars['data'] = $app->select("services_detail", "*", ["id" => $id])[0] ?? null;

    // Fetch list of services
    $vars['services'] = $app->select("services", ["id", "title"]);
    $vars['author_boxes'] = $app->select("author_boxes", ['id', 'name']);

    if ($vars['data']) {
        echo $app->render('templates/backend/services/services-detail-post.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['services-detail']);
$app->router("/admin/services-detail-edit", 'POST', function ($vars) use ($app, $jatbi, $setting) {
    $app->header(['Content-Type' => 'application/json']);

    // Get ID from request
    $id = isset($_POST['id']) ? $app->xss($_POST['id']) : null;
    if (!$id) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("ID không hợp lệ")]);
        return;
    }

    // Fetch existing data from DB
    $data = $app->select("services_detail", "*", ["id" => $id]);
    if (!$data) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Không tìm thấy dữ liệu")]);
        return;
    }

    // Get form data (with XSS sanitization)
    $service_id = $app->xss($_POST['service_id'] ?? '');
    $title = $app->xss($_POST['title'] ?? '');
    $description_title = $app->xss($_POST['description_title'] ?? '');
    $rate = $app->xss($_POST['rate'] ?? '');
    $min_price = $app->xss($_POST['min_price'] ?? '');
    $max_price = $app->xss($_POST['max_price'] ?? '');
    $original_min_price = $app->xss($_POST['original_min_price'] ?? '');
    $original_max_price = $app->xss($_POST['original_max_price'] ?? '');
    $discount = $app->xss($_POST['discount'] ?? '');
    $object = $app->xss($_POST['object'] ?? '');
    $content = $app->xss($_POST['content'] ?? '');
    $author_box_id = $app->xss($_POST['author_box_id'] ?? '');

    // Validate required fields
    $required_fields = ['service_id', 'title', 'description_title', 'rate', 'min_price', 'max_price', 'original_min_price', 'original_max_price', 'object'];
    $empty_fields = array_filter($required_fields, fn($field) => empty($_POST[$field]));
    if (!empty($empty_fields)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
        return;
    }

    // Validate service_id exists
    if (!$app->has("services", ["id" => $service_id])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Dịch vụ không tồn tại")]);
        return;
    }

    // Kiểm tra xem service_id đã có chi tiết khác chưa (trừ bản ghi hiện tại)
    $existing_detail = $app->select("services_detail", ["id"], ["service_id" => $service_id, "id[!]" => $id]);
    if ($existing_detail) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Dịch vụ này đã có chi tiết khác. Mỗi dịch vụ chỉ có một chi tiết.")]);
        return;
    }

    // Validate author_box_id if provided
    if (!empty($author_box_id) && !$app->has("author_boxes", ["id" => $author_box_id])) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Tác giả không tồn tại")]);
        return;
    }

    // Validate rate
    if (!is_numeric($rate) || $rate < 0 || $rate > 5) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Đánh giá phải từ 0 đến 5")]);
        return;
    }

    // Validate price
    if (!is_numeric($min_price) || $min_price < 0 || !is_numeric($max_price) || $max_price < 0) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Giá không hợp lệ")]);
        return;
    }

    // Data to update
    $update = [
        "service_id" => $service_id,
        "title" => $title,
        "description_title" => $description_title,
        "rate" => $rate,
        "min_price" => $min_price,
        "max_price" => $max_price,
        "original_min_price" => $original_min_price ?: $min_price,
        "original_max_price" => $original_max_price ?: $max_price,
        "discount" => $discount ?: null,
        "object" => $object,
        "content" => $content ?: null,
        "author_box_id" => $author_box_id ?: null,
    ];

    // // Debug: Log data before update
    // error_log("Update data: " . print_r($update, true));

    try {
        $app->update("services_detail", $update, ["id" => $id]);
        echo json_encode(["status" => "success", "content" => $jatbi->lang("Cập nhật thành công")]);
    } catch (Exception $e) {
        error_log("Update error: " . $e->getMessage());
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['services-detail']);


// Delete service detail
$app->router("/admin/services-detail-deleted", 'GET', function ($vars) use ($app, $jatbi) {
    $vars['title'] = $jatbi->lang("Xóa chi tiết dịch vụ");
    echo $app->render('templates/common/deleted.html', $vars, 'global');
})->setPermissions(['services-detail']);

$app->router("/admin/services-detail-deleted", 'POST', function ($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $idList = [];

    if (!empty($_GET['id'])) {
        $idList[] = $app->xss($_GET['id']);
    } elseif (!empty($_GET['box'])) {
        $idList = array_map('trim', explode(',', $app->xss($_GET['box'])));
    }

    if (empty($idList)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Thiếu ID chi tiết dịch vụ để xóa")]);
        return;
    }

    try {
        $deletedCount = 0;
        $errors = [];

        foreach ($idList as $id) {
            if (empty($id))
                continue;

            $deleted = $app->delete("services_detail", ["id" => $id]);

            if ($deleted) {
                $deletedCount++;
            } else {
                $errors[] = $id;
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                "status" => "error",
                "content" => $jatbi->lang("Một số chi tiết dịch vụ xóa thất bại"),
                "errors" => $errors
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "content" => $jatbi->lang("Đã xóa thành công") . " $deletedCount " . $jatbi->lang("chi tiết dịch vụ")
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['services-detail']);

