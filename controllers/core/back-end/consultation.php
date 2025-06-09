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
                "name" => !empty($data['name']) ? $data['name'] : '–',
                "phone" => !empty($data['phone']) ? $data['phone'] : '–',
                "email" => !empty($data['email']) ? $data['email'] : '–',
                "name_business" => !empty($data['name_business']) ? $data['name_business'] : '–',
                "datetime" => !empty($data['datetime']) ? date("d/m/Y H:i", strtotime($data['datetime'])) : '–',
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
                    "content" => $jatbi->lang("Đã xóa thành công") . " $deletedCount " . $jatbi->lang("thư viện")
                ]);
            }

        } catch (Exception $e) {
            echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
        }
    })->setPermissions(['consultation']);

    
