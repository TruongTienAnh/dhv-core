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




