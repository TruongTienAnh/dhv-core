<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

function generateSlug($str) {
    // Bước 1: Chuẩn hóa chuỗi
    $str = trim(mb_strtolower($str, 'UTF-8'));

    // Bước 2: Bỏ dấu tiếng Việt
    $vietCharMap = [
        // Chữ thường
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a',
        'â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
        'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e',
        'ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o',
        'ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u',
        'ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
        'đ'=>'d',
        // Chữ hoa
        'À'=>'a','Á'=>'a','Ạ'=>'a','Ả'=>'a','Ã'=>'a',
        'Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ậ'=>'a','Ẩ'=>'a','Ẫ'=>'a',
        'Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ặ'=>'a','Ẳ'=>'a','Ẵ'=>'a',
        'È'=>'e','É'=>'e','Ẹ'=>'e','Ẻ'=>'e','Ẽ'=>'e',
        'Ê'=>'e','Ề'=>'e','Ế'=>'e','Ệ'=>'e','Ể'=>'e','Ễ'=>'e',
        'Ì'=>'i','Í'=>'i','Ị'=>'i','Ỉ'=>'i','Ĩ'=>'i',
        'Ò'=>'o','Ó'=>'o','Ọ'=>'o','Ỏ'=>'o','Õ'=>'o',
        'Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ộ'=>'o','Ổ'=>'o','Ỗ'=>'o',
        'Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ợ'=>'o','Ở'=>'o','Ỡ'=>'o',
        'Ù'=>'u','Ú'=>'u','Ụ'=>'u','Ủ'=>'u','Ũ'=>'u',
        'Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ự'=>'u','Ử'=>'u','Ữ'=>'u',
        'Ỳ'=>'y','Ý'=>'y','Ỵ'=>'y','Ỷ'=>'y','Ỹ'=>'y',
        'Đ'=>'d'
    ];
    $str = strtr($str, $vietCharMap);

    // Bước 3: Bỏ ký tự không mong muốn, chỉ giữ a-z, 0-9 và dấu gạch ngang
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);

    // Bước 4: Bỏ dấu gạch thừa
    $str = preg_replace('/-+/', '-', $str);
    $str = trim($str, '-');

    return $str;
}

$app->router("/admin/library", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Thư viện số");

    $categories = $app->select("categories","*");
    $vars['categories'] = $categories;


    echo $app->render('templates/backend/library/library.html', $vars);
})->setPermissions(['library']);

$app->router("/admin/library", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';

    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');

    $validColumns = ["checkbox", "title", "description", "file_url", "img_url", "name", "created_at", "action"];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "title";

    // Điều kiện WHERE
    $where = [
        "AND" => [
            "OR" => [
                "resources.title[~]" => $searchValue,
                "resources.description[~]" => $searchValue,
            ]
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Thêm điều kiện lọc theo ngày tháng
    if (!empty($dateFrom)) {
        $where["AND"]["resources.created_at[>=]"] = $dateFrom . ' 00:00:00';
    }
    if (!empty($dateTo)) {
        $where["AND"]["resources.created_at[<=]"] = $dateTo . ' 23:59:59';
    }

    // Đếm tổng số bản ghi
    $count = $app->count("resources", ["AND" => $where["AND"]]);

    // Lấy dữ liệu
    $datas = $app->select("resources", [
        "[>]categories" => ["id_category" => "id"]
    ], [
        "resources.id",
        "resources.title",
        "resources.description",
        "resources.file_url",
        "resources.img_url",
        "resources.id_category",
        "resources.created_at",
        "categories.name",
    ], $where) ?? [];

    // Format dữ liệu cho DataTables
    $formattedData = array_map(function($data) use ($app, $jatbi) {
        return [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "title" => $data['title'],
            "description" => $data['description'],
            "file_url" => $data['file_url'],
            "img_url" => $data['img_url'],
            "name" => $data['name'],
            "created_at" => date("Y/m/d H:i", strtotime($data['created_at'])),
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
    $vars['title1'] = $jatbi->lang("Thêm thư viện số");
    $vars['categories'] = $app->select("categories", ['id', 'name']);
    echo $app->render('templates/backend/library/library-post.html', $vars, 'global');
})->setPermissions(['library']);

$app->router("/admin/library-add", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $title = $app->xss($_POST['title'] ?? '');
    $description = $app->xss($_POST['description'] ?? '');
    $category = $app->xss($_POST['category'] ?? '');
    $pdfFile = $_FILES['file'] ?? null;
    $imgFile = $_FILES['image'] ?? null;

    if (empty($title) || empty($category) || !$pdfFile || !$imgFile) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng không để trống các trường bắt buộc")]);
        return;
    }

    $slug = generateSlug($title);
    $uploadDir = __DIR__ . '/../../uploads/library/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Lưu file PDF
    $pdfFilename = time() . '_' . basename($pdfFile['name']);
    $pdfPath = $uploadDir . $pdfFilename;
    if (!move_uploaded_file($pdfFile['tmp_name'], $pdfPath)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải file PDF thất bại")]);
        return;
    }

    // Lưu ảnh minh họa
    $imgExt = pathinfo($imgFile['name'], PATHINFO_EXTENSION);
    $imgFilename = time() . '_cover.' . $imgExt;
    $imgPath = $uploadDir . $imgFilename;
    if (!move_uploaded_file($imgFile['tmp_name'], $imgPath)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải ảnh minh họa thất bại")]);
        return;
    }

    // Lưu dữ liệu vào DB
    $insert = [
        "title" => $title,
        "description" => $description,
        "file_url" => 'uploads/library/' . $pdfFilename,
        "img_url" => 'uploads/library/' . $imgFilename,
        "id_category" => $category,
        "created_at" => date("Y-m-d H:i:s"),
        "slug" => $slug,
    ];

    try {
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
    $vars['data'] = $app->select("resources", "*", ["id" => $id])[0] ?? null;

    // Lấy danh sách danh mục
    $vars['categories'] = $app->select("categories", ["id", "name"],);

    if ($vars['data']) {
        echo $app->render('templates/backend/library/library-post.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['library']);

$app->router("/admin/library-edit", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    // Lấy ID thư viện từ request
    $id = isset($_POST['id']) ? $app->xss($_POST['id']) : null;
    if (!$id) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("ID không hợp lệ")]);
        return;
    }

    // Lấy dữ liệu cũ từ DB
    $data = $app->select("resources", "*", ["id" => $id]);
    if (!$data) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Không tìm thấy dữ liệu")]);
        return;
    }

    // Lấy dữ liệu từ form
    $title = isset($_POST['title']) ? $app->xss($_POST['title']) : '';
    $description = isset($_POST['description']) ? $app->xss($_POST['description']) : '';
    $category = isset($_POST['category']) ? $app->xss($_POST['category']) : '';
    $create_at = isset($_POST['create_at']) ? $app->xss($_POST['create_at']) : '';



    // Kiểm tra dữ liệu bắt buộc
    if (empty($title) || empty($description) || empty($category) || empty($create_at)) {
        echo json_encode(["status" => "error", "content" => $jatbi->lang("Vui lòng điền đầy đủ thông tin")]);
        return;
    }

    // Xử lý upload file nếu có
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/library/';
        $fileName = basename($_FILES['file']['name']);
        $targetFile = $uploadDir . time() . "_" . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $filePath = $targetFile;
        } else {
            echo json_encode(["status" => "error", "content" => $jatbi->lang("Tải file thất bại")]);
            return;
        }
    } else {
        $filePath = $data['file'] ?? null; // Giữ nguyên file cũ nếu không upload
    }

    // Dữ liệu cập nhật
    $update = [
        "title" => $title,
        "description" => $description,
        "file_url" => $filePath,
        "id_category" => $category,
        "created_at" => $create_at,
    ];

    try {
        $app->update("resources", $update, ["id" => $id]);

        echo json_encode(["status" => "success", "content" => $jatbi->lang("Cập nhật dữ liệu thành công")]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "content" => "Lỗi: " . $e->getMessage()]);
    }
})->setPermissions(['library']);








