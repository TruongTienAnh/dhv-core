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

    $draw = $_POST['draw'] ?? 0;
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $searchValue = $_POST['search']['value'] ?? '';

    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');

    // Cập nhật danh sách cột theo table bạn cung cấp
    $validColumns = ["checkbox", "name", "phone", "email", "name_business", "datetime", "method", "status", "note", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "datetime";

    // Điều kiện WHERE
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

    // Đếm bản ghi
    $count = $app->count("appointments", ["AND" => $where["AND"]]);

    // Truy vấn dữ liệu
    $datas = $app->select("appointments", "*", $where) ?? [];

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
            "name" => $data['name'],
            "phone" => $data['phone'],
            "email" => $data['email'],
            "name_business" => $data['name_business'],
            "datetime" => date("d/m/Y H:i", strtotime($data['datetime'])),
            "method" => $methodLabels[$data['method']] ?? $data['method'],
            "status" => $app->component("status", [
                "url" => "/admin/consultation-status/" . $data['id'],
                "data" => $data['status'],
                "permission" => ['consultation.edit']
            ]),
            "note" => $data['note'],
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
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    // Kiểm tra xem có 'id' hay 'box' trong request không
    $snList = [];

    if (!empty($_GET['id'])) {
        $snList[] = $app->xss($_GET['id']);
    } elseif (!empty($_GET['box'])) {
        $snList = array_map('trim', explode(',', $app->xss($_GET['box'])));
    }

    if (empty($snList)) {
        echo json_encode(["status" => "error", "content" => "Thiếu ID nhân viên để xóa"]);
        return;
    }

    try {
        $headers = [
            'Authorization: Bearer your_token',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $deletedCount = 0;
        $errors = [];

        foreach ($snList as $sn) {
            if (empty($sn)) continue; // Bỏ qua nếu có giá trị rỗng

            // Xóa khỏi database
            $app->delete("employee", ["sn" => $sn]);

            // Gửi yêu cầu xóa từ API
            $apiData = [
                'deviceKey' => '77ed8738f236e8df86',
                'secret' => '123456',
                'sn' => $sn,
            ];

            $response = $app->apiPost(
                'http://camera.ellm.io:8190/api/person/delete', 
                $apiData, 
                $headers
            );

            $apiResponse = json_decode($response, true);

            if (!empty($apiResponse['success']) && $apiResponse['success'] === true) {
                $deletedCount++;
            } else {
                $errorMessage = $apiResponse['msg'] ?? "Không rõ lỗi";
                $errors[] = "SN $sn: " . $errorMessage;
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                "status" => "error",
                "content" => "Một số nhân viên xóa thất bại",
                "errors" => $errors
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "content" => "Đã xóa thành công $deletedCount nhân viên"
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['consultation']);
