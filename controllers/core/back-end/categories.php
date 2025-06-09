<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');



$app->router("/admin/categories", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Danh mục");
    echo $app->render('templates/backend/categories/categories.html', $vars);
})->setPermissions(['categories']);

$app->router("/admin/categories", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';

    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');

    // Danh sách cột theo bảng library để order
    $validColumns = ["checkbox", "name","action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "title";

    // Điều kiện tìm kiếm (chỉ điều kiện WHERE)
    $where = [
        "AND" => [
            "OR" => [
                "categories.name[~]" => $searchValue,
            ]
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];
    $count = $app->count("categories", ["AND" => $where["AND"]]);

    $datas = $app->select("categories","*",$where) ?? [];

    // Format dữ liệu trả về cho DataTables
    $formattedData = array_map(function($data) use ($app, $jatbi) {
        return [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "name" => $data['name'],
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['library'],
                        'action' => [
                            'data-url' => '/admin/categories-edit?id=' . $data['id'],
                            'data-action' => 'modal'
                        ]
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['library'],
                        'action' => [
                            'data-url' => '/admin/categories-delete?id=' . $data['id'],
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
})->setPermissions(['categories']);


//Thêm categories
    $app->router("/admin/categories-add", 'GET', function($vars) use ($app, $jatbi, $setting) {
        $vars['title'] = $jatbi->lang("Thêm danh mục");
        echo $app->render('templates/backend/categories/categories-post.html', $vars, 'global');
    })->setPermissions(['categories']);
    
    $app->router("/admin/categories-add", 'POST', function($vars) use ($app, $jatbi) {
        $app->header(['Content-Type' => 'application/json']);

        // Lấy dữ liệu từ form (xử lý XSS)
        $name = $app->xss($_POST['name'] ?? '');


        // Kiểm tra dữ liệu bắt buộc
        if (empty($name)) {
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
            return;
        }

        $slug = generateSlug($name);

        // Chuẩn bị dữ liệu lưu
        $insert = [
            "name" => $name,
            "slug" => $slug,
        ];

        try {

            // Lưu vào DB (bảng `library`)
            $app->insert("categories", $insert);

            echo json_encode(["status" => "success", "content" => $jatbi->lang("Thêm thành công")]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
        }
    })->setPermissions(['categories']);


//Xóa categories

    $app->router("/admin/categories-deleted", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Xóa danh mục");
        echo $app->render('templates/common/deleted.html', $vars, 'global');
    })->setPermissions(['categories']);

    $app->router("/admin/categories-deleted", 'POST', function($vars) use ($app, $jatbi) {
        $app->header(['Content-Type' => 'application/json']);

        $idList = [];

        if (!empty($_GET['id'])) {
            $idList[] = $app->xss($_GET['id']);
        } elseif (!empty($_GET['box'])) {
            $idList = array_map('trim', explode(',', $app->xss($_GET['box'])));
        }

        if (empty($idList)) {
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Thiếu ID thư viện để xóa")]);
            return;
        }

        try {
            $deletedCount = 0;
            $errors = [];

            foreach ($idList as $id) {
                if (empty($id)) continue;

                $deleted = $app->delete("categories", ["id" => $id]);

                if ($deleted) {
                    $deletedCount++;
                } else {
                    $errors[] = $id;
                }
            }

            if (!empty($errors)) {
                echo json_encode([
                    "status" => "error",
                    "content" => $jatbi->lang("Một số danh mục xóa thất bại"),
                    "errors" => $errors
                ]);
            } else {
                echo json_encode([
                    "status" => "success",
                    "content" => $jatbi->lang("Đã xóa thành công") . " $deletedCount " . $jatbi->lang("danh mục")
                ]);
            }

        } catch (Exception $e) {
            echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
        }
    })->setPermissions(['categories']);

// Cập nhật categories
$app->router("/admin/categories-edit", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title'] = $jatbi->lang("Sửa danh mục");

    $id = isset($_GET['id']) ? $app->xss($_GET['id']) : null;

    echo $id ; 
    if (!$id) {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
        return;
    }

    // Lấy dữ liệu thư viện từ DB
    $vars['data'] = $app->select("categories", "*", ["id" => $id])[0] ?? null;

    if ($vars['data']) {
        echo $app->render('templates/backend/categories/categories-post.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['categories']);

$app->router("/admin/categories-edit", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $id = isset($_POST['id']) ? $app->xss($_POST['id']) : null;
    if (!$id) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("ID không hợp lệ")]);
        return;
    }

    $data = $app->select("categories", "*", ["id" => $id]);
    if (!$data) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Không tìm thấy dữ liệu")]);
        return;
    }

    $name = $app->xss($_POST['name'] ?? '');
    if (empty($name)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng điền đầy đủ thông tin")]);
        return;
    }

    // Tạo slug từ name
    $slug = generateSlug($name);

    $update = [
        "name" => $name,
        "slug" => $slug,
    ];

    try {
        $app->update("categories", $update, ["id" => $id]);
        echo json_encode(["status" => "success", "content" => $jatbi->lang("Cập nhật dữ liệu thành công")]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['categories']);








