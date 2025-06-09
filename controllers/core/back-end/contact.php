<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/admin/contact", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Liên hệ");
    echo $app->render('templates/backend/contact/contact.html', $vars);
})->setPermissions(['contact']);

$app->router("/admin/contact", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $draw = $_POST['draw'] ?? 0;
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $searchValue = $_POST['search']['value'] ?? '';

    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');

    // Danh sách cột theo bảng contact
    $validColumns = ["checkbox", "name", "phone", "email", "province", "title", "note", "datetime", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "datetime";

    // Điều kiện lọc
    $where = [
        "AND" => [
            "OR" => [
                "name[~]" => $searchValue,
                "phone[~]" => $searchValue,
                "email[~]" => $searchValue,
            ]
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Đếm tổng số bản ghi
    $count = $app->count("contact", ["AND" => $where["AND"]]);

    // Lấy dữ liệu
    $datas = $app->select("contact", "*", $where) ?? [];

    // Format dữ liệu cho DataTables
    $formattedData = array_map(function($data) use ($app, $jatbi) {
        return [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "name" => $data['name'],
            "phone" => $data['phone'],
            "email" => $data['email'],
            "province" => $data['province'],
            "title" => $data['title'],
            "note" => $data['note'],
            "datetime" => date("Y/m/d H:i", strtotime($data['datetime'])),
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['contact'],
                        'action' => [
                            'data-url' => '/admin/contact-delete?id=' . $data['id'],
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
})->setPermissions(['contact']);

// Hàm thêm liên hệ mới
$app->router("/admin/contact-add", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title'] = $jatbi->lang("Thêm liên hệ");
    echo $app->render('templates/backend/contact/contact-post.html', $vars, 'global');
})->setPermissions(['contact']);

$app->router("/admin/contact-add", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy dữ liệu POST, lọc xss
    $name = $app->xss($_POST['name'] ?? '');
    $phone = $app->xss($_POST['phone'] ?? '');
    $email = $app->xss($_POST['email'] ?? '');
    $province = $app->xss($_POST['province'] ?? '');
    $title = $app->xss($_POST['title'] ?? '');
    $note = $app->xss($_POST['note'] ?? '');
    $datetime = date('Y-m-d H:i:s'); // Lấy thời gian hiện tại

    // Kiểm tra dữ liệu bắt buộc
    if (empty($name) || empty($phone) || empty($email)) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Vui lòng nhập đầy đủ thông tin bắt buộc")
        ]);
        return;
    }

    $insert = [
        "name" => $name,
        "phone" => $phone,
        "email" => $email,
        "province" => $province,
        "title" => $title,
        "note" => $note,
        "datetime" => $datetime
    ];

    $result = $app->insert("contact", $insert);

    if (!$result) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Không thể thêm liên hệ")
        ]);
        return;
    }

    echo json_encode([
        "status" => "success",
        "content" => $jatbi->lang("Thêm liên hệ thành công")
    ]);
})->setPermissions(['contact']);


// Hàm xóa liên hệ
$app->router("/admin/contact-delete", 'GET', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("ID không hợp lệ")
        ]);
        return;
    }

    $result = $app->delete("contact", ["id" => $id]);

    if (!$result) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Xóa liên hệ thất bại")
        ]);
        return;
    }

    echo json_encode([
        "status" => "success",
        "content" => $jatbi->lang("Xóa liên hệ thành công")
    ]);
})->setPermissions(['contact']);



