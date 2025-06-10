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
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';

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

    // Thêm điều kiện lọc theo ngày tháng
    if (!empty($dateFrom)) {
        $where["AND"]["datetime[>=]"] = $dateFrom . ' 00:00:00';
    }
    if (!empty($dateTo)) {
        $where["AND"]["datetime[<=]"] = $dateTo . ' 23:59:59';
    }

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
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['contact'],
                        'action' => [
                            'data-url' => '/admin/contact-edit?id=' . $data['id'], 
                            'data-action' => 'modal'  
                        ]
                    ],
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

    $jatbi->logs('contact', 'contact-add', $insert);


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
    $vars['title'] = $jatbi->lang("Xóa");
    echo $app->render('templates/common/deleted.html', $vars, 'global');
})->setPermissions(['contact']);

$app->router("/admin/contact-delete", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $idList = [];

    // Lấy id từ POST (nên dùng POST cho xóa)
    if (!empty($_POST['id'])) {
        $idList[] = $app->xss($_POST['id']);
    } elseif (!empty($_POST['box'])) {
        $idList = array_map('trim', explode(',', $app->xss($_POST['box'])));
    }

    if (empty($idList)) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Thiếu ID để xóa")
        ]);
        return;
    }

    try {
        $deletedCount = 0;
        $errors = [];

        foreach ($idList as $id) {
            if (empty($id)) continue;

            $deleted = $app->delete("contact", ["id" => $id]);

            $jatbi->logs('contact', 'contact-delete', $deleted);


            if ($deleted) {
                $deletedCount++;
            } else {
                $errors[] = $id;
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                "status" => "error",
                "content" => $jatbi->lang("Một số liên hệ xóa thất bại"),
                "errors" => $errors
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "content" => $jatbi->lang("Đã xóa thành công") . " $deletedCount " . $jatbi->lang("liên hệ")
            ]);
        }

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "content" => "Lỗi: " . $e->getMessage()
        ]);
    }
})->setPermissions(['contact']);


//Sửa contact
$app->router("/admin/contact-edit", 'GET', function($vars) use ($app, $jatbi) {
    $id = $app->xss($_GET['id'] ?? '');

    if (empty($id)) {
        $app->redirect('/admin/contact'); // Hoặc thông báo lỗi
        return;
    }

    // Lấy dữ liệu liên hệ theo id
    $contact = $app->select("contact", "*", ["id" => $id]);

    $vars['title'] = $jatbi->lang("Sửa liên hệ");
    $vars['data'] = $contact[0];  

    echo $app->render('templates/backend/contact/contact-post.html', $vars, 'global');
})->setPermissions(['contact']);


$app->router("/admin/contact-edit", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $id = $app->xss($_POST['id'] ?? '');
    $name = $app->xss($_POST['name'] ?? '');
    $phone = $app->xss($_POST['phone'] ?? '');
    $email = $app->xss($_POST['email'] ?? '');
    $province = $app->xss($_POST['province'] ?? '');
    $title = $app->xss($_POST['title'] ?? '');
    $note = $app->xss($_POST['note'] ?? '');

    if (empty($id)) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Thiếu ID liên hệ")
        ]);
        return;
    }

    if (empty($name) || empty($phone) || empty($email)) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Vui lòng nhập đầy đủ thông tin bắt buộc")
        ]);
        return;
    }

    $update = [
        "name" => $name,
        "phone" => $phone,
        "email" => $email,
        "province" => $province,
        "title" => $title,
        "note" => $note,
    ];

    $result = $app->update("contact", $update, ["id" => $id]);

    $jatbi->logs('contact', 'contact-edit', $update);


    if (!$result) {
        echo json_encode([
            "status" => "error",
            "content" => $jatbi->lang("Cập nhật liên hệ thất bại")
        ]);
        return;
    }

    echo json_encode([
        "status" => "success",
        "content" => $jatbi->lang("Cập nhật liên hệ thành công")
    ]);
})->setPermissions(['contact']);
