<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/admin/library", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Thư viện số");
    echo $app->render('templates/backend/library/library.html', $vars);
})->setPermissions(['contact']);

$app->router("/admin/library", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';

    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');

    // Danh sách cột theo bảng library để order
    $validColumns = ["checkbox", "title", "description", "file_url", "name", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "title";

    // Điều kiện tìm kiếm (chỉ điều kiện WHERE)
    $where = [
        "AND" => [
            "OR" => [
                "resources.title[~]" => $searchValue,
            ]
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Đếm tổng số bản ghi thỏa điều kiện tìm kiếm
    $count = $app->count("resources", ["AND" => $where["AND"]]);


    // Lấy dữ liệu có join bảng category, phân trang, sắp xếp
    $datas = $app->select("resources", [
        "[>]categories" => ["id_category" => "id"]
    ], [
        "resources.id",
        "resources.title",
        "resources.description",
        "resources.file_url",
        "resources.id_category",
        "resources.created_at",
        "categories.name",
    ],$where) ?? [];

    // Format dữ liệu trả về cho DataTables
    $formattedData = array_map(function($data) use ($app, $jatbi) {
        return [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "title" => $data['title'],
            "description" => $data['description'],
            "file_url" => $data['file_url'],
            "name" => $data['name'],
            "created_at" => $data['created_at'],
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['library'],
                        'action' => [
                            'data-url' => '/admin/library-edit?id=' . $data['id'],
                            'data-action' => 'modal'
                        ]
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['library'],
                        'action' => [
                            'data-url' => '/admin/library-delete?id=' . $data['id'],
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
})->setPermissions(['library']);


  //Thêm library
    $app->router("/admin/library-add", 'GET', function($vars) use ($app, $jatbi, $setting) {
        $vars['title'] = $jatbi->lang("Thêm thư viện số");
        $vars['categories'] = $app->select("categories", ['id', 'name']);
        echo $app->render('templates/backend/library/library-post.html', $vars, 'global');
    })->setPermissions(['library']);
    
    $app->router("/admin/library-add", 'POST', function($vars) use ($app, $jatbi) {
        $app->header(['Content-Type' => 'application/json']);

        // Lấy dữ liệu từ form (xử lý XSS)
        $title = $app->xss($_POST['title'] ?? '');
        $description = $app->xss($_POST['description']??'');
        $category = $app->xss($_POST['category']??'');
        $filename = isset($_FILES['file']) ? $_FILES['file'] : null;


        // Kiểm tra dữ liệu bắt buộc
        if (empty($title) || empty($description) || empty($category)) {
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
            return;
        }

        // Xử lý upload file (ví dụ lưu vào thư mục /uploads/)
        // $uploadDir = __DIR__ . '/../../uploads/library/';
        // $filename = time() . '_' . basename($file['name']);
        // $targetFile = $uploadDir . $filename;

        // if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        //     echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải file thất bại")]);
        //     return;
        // }

        // Chuẩn bị dữ liệu lưu
        $insert = [
            "title" => $title,
            "description" => $description,
            "file_url" => $filename, 
            "id_category" => $category,
            "created_at" => date("Y-m-d H:i:s"),
        ];

        try {

            // Lưu vào DB (bảng `library`)
            $app->insert("resources", $insert);

            echo json_encode(["status" => "success", "content" => $jatbi->lang("Thêm thành công")]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
        }
    })->setPermissions(['library']);



    //Xóa library

    $app->router("/admin/library-deleted", 'GET', function($vars) use ($app, $jatbi) {
        $vars['title'] = $jatbi->lang("Xóa Thư viện số");
        echo $app->render('templates/common/deleted.html', $vars, 'global');
    })->setPermissions(['library']);

    $app->router("/admin/library-deleted", 'POST', function($vars) use ($app, $jatbi) {
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

                $deleted = $app->delete("resources", ["id" => $id]);

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
    })->setPermissions(['library']);


$app->router("/admin/library-edit", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title1'] = $jatbi->lang("Sửa Thư Viện");

    $id = isset($_GET['id']) ? $app->xss($_GET['id']) : null;

    echo $id ; 
    if (!$id) {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
        return;
    }

    // Lấy dữ liệu thư viện từ DB
    $vars['data'] = $app->select("resources", "*", ["id" => $id]);

    // Lấy danh sách danh mục
    $vars['categories'] = $app->select("categories", ["id", "name"],);

    // $vars['data']['edit'] = true;
    var_dump($vars['data']);

    if ($vars['data']) {
        echo $app->render('templates/backend/library/library-post.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['library']);







