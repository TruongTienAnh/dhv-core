<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/admin/consultation", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Lịch Tư Vấn");
    echo $app->render('templates/backend/consultation/consultation.html', $vars);
})->setPermissions(['consultation']);

$app->router("/admin/consultation", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    // Nhận tham số từ POST
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';
    $method = $_POST['method'] ?? '';
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';

    // Xử lý sắp xếp
    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');
    $validColumns = ["checkbox", "name", "phone", "email", "name_business", "datetime", "service", "method", "status", "note", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "datetime";

    // Xây dựng điều kiện WHERE
    $where = [
        "AND" => [
            "OR" => [
                "name[~]" => $searchValue,
                "phone[~]" => $searchValue,
                "email[~]" => $searchValue,
                "name_business[~]" => $searchValue,
            ]
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Thêm bộ lọc method
    if (!empty($method)) {
        $where["AND"]["appointments.method"] = $method;
    }

    // Thêm bộ lọc ngày tháng
    if (!empty($dateFrom)) {
        $where["AND"]["appointments.datetime[>=]"] = $dateFrom;
    }
    if (!empty($dateTo)) {
        // Đảm bảo date_to bao gồm cả ngày cuối (thêm 23:59:59)
        $where["AND"]["appointments.datetime[<=]"] = $dateTo . ' 23:59:59';
    }

    // Đếm bản ghi
    $count = $app->count("appointments", ["AND" => $where["AND"]]);

    // Truy vấn dữ liệu
    $datas = $app->select("appointments", [
        "[>]services" => ["service" => "id"]
    ], [
        "appointments.id",
        "appointments.name",
        "appointments.phone",
        "appointments.email",
        "appointments.name_business",
        "appointments.datetime",
        "appointments.method",
        "appointments.note",
        "services.title(service_title)",
        "services.type(service_type)"
    ], $where) ?? [];

    // Map dữ liệu
    $formattedData = array_map(function($data) use ($app, $jatbi) {
        $methodLabels = [
            "online" => $jatbi->lang("Trực tuyến"),
            "offline" => $jatbi->lang("Trực tiếp")
        ];

        $statusLabels = [
            "pending" => $jatbi->lang("Chờ xử lý"),
            "confirmed" => $jatbi->lang("Đã xác nhận"),
            "cancelled" => $jatbi->lang("Đã hủy")
        ];

        return [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "name" => !empty($data['name']) ? $data['name'] : '–',
            "phone" => !empty($data['phone']) ? $data['phone'] : '–',
            "email" => !empty($data['email']) ? $data['email'] : '–',
            "name_business" => !empty($data['name_business']) ? $data['name_business'] : '–',
            "datetime" => !empty($data['datetime']) ? date("d/m/Y H:i", strtotime($data['datetime'])) : '–',
            "service" => (!empty($data['service_title']) || !empty($data['service_type'])) ? "{$data['service_title']} - {$data['service_type']}" : '–',
            "method" => !empty($methodLabels[$data['method']]) ? $methodLabels[$data['method']] : (!empty($data['method']) ? $data['method'] : '–'),
            "status" => $app->component("status", [
                "url" => "/admin/consultation-status/" . $data['id'],
                "data" => $data['status'] ?? '',
                "permission" => ['consultation.edit']
            ]),
            "note" => !empty($data['note']) ? $data['note'] : '–',
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['consultation'],
                        'action' => ['data-url' => '/admin/consultation-edit?id=' . $data['id'], 'data-action' => 'modal']
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['consultation'],
                        'action' => ['data-url' => '/admin/consultation-deleted?id=' . $data['id'], 'data-action' => 'modal']
                    ]
                ]
            ])
        ];
    }, $datas);

    // Trả về JSON
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $formattedData
    ]);
})->setPermissions(['consultation']);


$app->router("/admin/consultation-deleted", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title'] = $jatbi->lang("Xóa");
    echo $app->render('templates/common/deleted.html', $vars, 'global');
})->setPermissions(['consultation']);

$app->router("/admin/consultation-deleted", 'POST', function($vars) use ($app, $jatbi) {
        $app->header(['Content-Type' => 'application/json']);

        $idList = [];

        if (!empty($_GET['id'])) {
            $idList[] = $app->xss($_GET['id']);
        } elseif (!empty($_GET['box'])) {
            $idList = array_map('trim', explode(',', $app->xss($_GET['box'])));
        }

        if (empty($idList)) {
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Thiếu ID lịch để xóa")]);
            return;
        }

        try {
            $deletedCount = 0;
            $errors = [];

            foreach ($idList as $id) {
                if (empty($id)) continue;

                $deleted = $app->delete("appointments", ["id" => $id]);

                $jatbi->logs('appointments', 'appointments-deleted', $deleted);

            
                if ($deleted) {
                    $deletedCount++;
                } else {
                    $errors[] = $id;
                }
            }

            if (!empty($errors)) {
                echo json_encode([
                    "status" => "error",
                    "content" => $jatbi->lang("Một số thư viện xóa thất bại"),
                    "errors" => $errors
                ]);
            } else {
                echo json_encode([
                    "status" => "success",
                    "content" => $jatbi->lang("Đã xóa thành công") . " $deletedCount " . $jatbi->lang("thông tin")
                ]);
            }

        } catch (Exception $e) {
            echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
        }
    })->setPermissions(['consultation']);

    
$app->router("/admin/consultation-edit", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title1'] = $jatbi->lang("Sửa lịch tư vấn");

    $id = isset($_GET['id']) ? $app->xss($_GET['id']) : null;

    if (!$id) {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
        return;
    }

    // Lấy dữ liệu lịch tư vấn từ DB
    $appointment = $app->select("appointments", "*", ["id" => $id])[0] ?? null;

    if (!$appointment) {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
        return;
    }

    // Lấy dữ liệu dịch vụ dựa theo service_id trong lịch tư vấn
    $service = null;
    if (!empty($appointment['service'])) { // hoặc 'service_id' tùy DB
        $service = $app->select("services", ["title", "type"], ["id" => $appointment['service']])[0] ?? null;
    }

    // Gán dữ liệu cho biến $vars để truyền sang template
    $vars['data'] = $appointment;
    $vars['service_info'] = $service;
    $vars['services'] = $app->select("services", ["id", "title", "type"]);

    echo $app->render('templates/backend/consultation/consultation-post.html', $vars, 'global');
})->setPermissions(['consultation']);




$app->router("/admin/consultation-edit", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $id = $app->xss($_POST['id'] ?? '');
    if (empty($id)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Thiếu ID để cập nhật")]);
        return;
    }

    $name = $app->xss($_POST['name'] ?? '');
    $phone = $app->xss($_POST['phone'] ?? '');
    $email = $app->xss($_POST['email'] ?? '');
    $company = $app->xss($_POST['name_business'] ?? '');
    $note = $app->xss($_POST['note'] ?? '');
    $datetime = $app->xss($_POST['datetime'] ?? '');
    $service_package = (int)$app->xss($_POST['service_package'] ?? '');
    $consult_method = $app->xss($_POST['method'] ?? '');


    $update = [
        "name" => $name,
        "phone" => $phone,
        "email" => $email,
        "name_business" => $company,
        "note" => $note,
        "datetime" => $datetime,
        "service" => $service_package,
        "method" => $consult_method,
    ];

    $result = $app->update("appointments", $update, ["id" => $id]);
    
    $jatbi->logs('appointments', 'appointments-edit', $update);

    if ($result === false) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Không thể cập nhật dữ liệu.")]);
        return;
    }

    echo json_encode(["status" => "success", "content" => $jatbi->lang("Cập nhật thành công")]);
})->setPermissions(['consultation']);


//Thêm library
$app->router("/admin/consultation-add", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title1'] = $jatbi->lang("Thêm lịch tư vấn");
    $vars['services'] = $app->select("services", ["id", "title", "type"]);
    echo $app->render('templates/backend/consultation/consultation-post.html', $vars, 'global');
})->setPermissions(['consultation']);

$app->router("/admin/consultation-add", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $name = $app->xss($_POST['name'] ?? '');
    $phone = $app->xss($_POST['phone'] ?? '');
    $email = $app->xss($_POST['email'] ?? '');
    $company = $app->xss($_POST['name_business'] ?? '');
    $note = $app->xss($_POST['note'] ?? '');
    $date = $app->xss($_POST['date'] ?? '');
    $time = $app->xss($_POST['time'] ?? '');
    $service_package = (int)$app->xss($_POST['service'] ?? 0);
    $consult_method = $app->xss($_POST['method'] ?? '');

    // Kết hợp date + time thành datetime chuẩn MySQL (ví dụ: '2025-06-10 14:30:00')
    if ($date && $time) {
        $datetime = date('Y-m-d H:i:s', strtotime("$date $time"));
    } else {
        $datetime = '';
    }

    // Validate dữ liệu bắt buộc
    if (empty($name) || empty($phone) || empty($email) || empty($datetime) || $service_package === 0 || empty($consult_method)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng nhập đầy đủ thông tin bắt buộc")]);
        return;
    }

    $insert = [
        "name" => $name,
        "phone" => $phone,
        "email" => $email,
        "name_business" => $company,
        "note" => $note,
        "datetime" => $datetime,
        "service" => $service_package,
        "method" => $consult_method,
    ];

    $result = $app->insert("appointments", $insert);

    $jatbi->logs('appointments', 'appointments-add', $insert);


    if (!$result) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Không thể thêm lịch tư vấn")]);
        return;
    }

    echo json_encode(["status" => "success", "content" => $jatbi->lang("Thêm lịch tư vấn thành công")]);
})->setPermissions(['consultation']);
