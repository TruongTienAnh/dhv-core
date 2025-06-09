<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');
$common = $app->getValueData('common');
$permission = $app->getValueData('permission');

// Hàm slugify để tạo slug từ tiêu đề
function slugify($string) {
    $transliteration = [
        'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        'đ' => 'd',
        'À' => 'A', 'Á' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
        'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
        'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
        'È' => 'E', 'É' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
        'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
        'Ò' => 'O', 'Ó' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
        'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
        'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
        'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
        'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',
        'Đ' => 'D',
    ];

    $string = strtr($string, $transliteration);
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    $string = trim($string, '-');
    return $string ?: 'default-slug';
}

// Route danh sách tin tức
$app->router("/admin/news", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $app->setGlobalFile(__DIR__ . '/../../includes/global.php');
    $vars['title'] = $jatbi->lang("Quản lý tin tức");
    $vars['add'] = '/admin/news-add';
    $vars['deleted'] = '/admin/news-deleted';
    $vars['categories'] = $app->select("categories_news", "*", ["deleted" => 0, "status" => 'A']);
    echo $app->render('templates/backend/news-projects/news.html', $vars);
})->setPermissions(['news']);

// Route lấy dữ liệu danh sách tin tức (AJAX)
$app->router("/admin/news", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    // Lấy dữ liệu từ DataTable
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');
    $status = $_POST['status'] ?? '';
    $category_id = $_POST['category_id'] ?? '';

    // Danh sách cột hợp lệ
    $validColumns = [
        0 => "id",
        1 => "title",
        2 => "category_id",
        4 => "published_at",
        5 => "views",
        6 => "created_at",
        7 => "updated_at",
    ];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "id";

    // Điều kiện lọc cơ bản
    $where = [
        "AND" => [
            "news.deleted" => 0
        ],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    // Xây dựng điều kiện AND
    $andConditions = [];

    // Lọc theo trạng thái
    if (!empty($status)) {
        $andConditions["news.status"] = $status;
    }

    // Lọc theo danh mục
    if (!empty($category_id)) {
        $andConditions["news.category_id"] = $category_id;
    }

    // Tìm kiếm
    if (!empty($searchValue)) {
        $andConditions["OR"] = [
            "news.title[~]" => $searchValue,
            "categories_news.name[~]" => $searchValue
        ];
    }

    if (!empty($andConditions)) {
        $where["AND"] = array_merge($where["AND"], $andConditions);
    }

    // Lấy tổng số dòng phù hợp
    $ids = $app->select("news", [
        "[>]categories_news" => ["category_id" => "id"]
    ], "news.id", $where);
    $count = count($ids);

    // Lấy dữ liệu trang hiện tại
    $datas = [];

    $app->select("news", [
        "[>]categories_news" => ["category_id" => "id"]
    ], [
        'news.id',
        'news.title',
        'news.slug',
        'news.image_url',
        'news.published_at',
        'news.views',
        'news.status',
        'news.created_at',
        'news.updated_at',
        'news.content',
        'categories_news.name(category_name)',
    ], $where, function ($data) use (&$datas, $app, $jatbi) {
        $datas[] = [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "title" => $data['title'],
            "categories" => $data['category_name'] ?? 'Chưa xác định',
            "image" => $data['image_url'] ? '<img src="/templates/uploads/news/' . $data['image_url'] . '" alt="' . $data['title'] . '" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">' : 'Chưa có ảnh',
            "published_at" => $data['published_at'] ? date('d/m/Y H:i', strtotime($data['published_at'])) : 'Chưa xuất bản',
            "views" => $data['views'],
            "created_at" => date('d/m/Y H:i', strtotime($data['created_at'])),
            "updated_at" => $data['updated_at'] ? date('d/m/Y H:i', strtotime($data['updated_at'])) : '',
            "status" => $app->component("status", ["url" => "/admin/news-status/" . $data['id'], "data" => $data['status'], "permission" => ['news.edit']]),
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['news.edit'],
                        'action' => ['data-url' => '/admin/news-edit/' . $data['id'], 'data-action' => 'modal']
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['news.deleted'],
                        'action' => ['data-url' => '/admin/news-deleted?box=' . $data['id'], 'data-action' => 'modal']
                    ],
                ]
            ]),
        ];
    });

    // Trả kết quả về DataTable
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $datas
    ]);
})->setPermissions(['news']);

// Route thêm tin tức
$app->router("/admin/news-add", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Thêm tin tức");
    $vars['categories'] = $app->select("categories_news", "*", ["deleted" => 0, "status" => 'A']);
    $vars['data'] = [
        "status" => 'A',
        "category_id" => '',
    ];
    echo $app->render('templates/backend/news-projects/news-post.html', $vars, 'global');
})->setPermissions(['news.add']);

$app->router("/admin/news-add", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    // Kiểm tra dữ liệu đầu vào
    if (empty($_POST['title']) || empty($_POST['categories']) || empty($_POST['content'])) {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Vui lòng không để trống")]);
        return;
    }

    // Tạo slug từ tiêu đề
    $slug = slugify($_POST['title']);
    $slug_exists = $app->count("news", ["slug" => $slug, "deleted" => 0]);
    if ($slug_exists) {
        $slug .= '-' . time();
    }

    // Xử lý hình ảnh
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $handle = $app->upload($_FILES['image']);
        $path_upload = 'templates/uploads/news/';
        if (!is_dir($path_upload)) {
            mkdir($path_upload, 0755, true);
        }
        $new_image_name = $jatbi->active();
        if ($handle->uploaded) {
            $handle->allowed = ['image/*'];
            $handle->file_new_name_body = $new_image_name;
            $handle->Process($path_upload);
            if ($handle->processed) {
                $image_url = $new_image_name . '.' . $handle->file_dst_name_ext;
            }
        }
    }

    // Dữ liệu để chèn
    $insert = [
        "title" => $app->xss($_POST['title']),
        "slug" => $slug,
        "content" => $_POST['content'],
        "excerpt" => $app->xss($_POST['description']),
        "category_id" => $app->xss($_POST['categories']),
        "image_url" => $image_url,
        "published_at" => !empty($_POST['publish_at']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_at'])) : null,
        "status" => $app->xss($_POST['status'] ?? 'A'),
        "created_at" => date('Y-m-d H:i:s'),
        "updated_at" => date('Y-m-d H:i:s'),
        "deleted" => 0,
    ];

    $app->insert("news", $insert);
    $news_id = $app->id();

    // Ghi log
    $jatbi->logs('news', 'news-add', $insert);

    echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
})->setPermissions(['news.add']);

// Route sửa tin tức
$app->router("/admin/news-edit/{id}", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Sửa tin tức");
    $vars['categories'] = $app->select("categories_news", "*", ["deleted" => 0, "status" => 'A']);
    $vars['data'] = $app->get("news", "*", ["id" => $vars['id'], "deleted" => 0]);
    if ($vars['data']) {
        echo $app->render('templates/backend/news-projects/news-post.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['news.edit']);

$app->router("/admin/news-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    $news = $app->get("news", "*", ["id" => $vars['id'], "deleted" => 0]);
    if (!$news) {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Không tìm thấy dữ liệu")]);
        return;
    }

    // Kiểm tra dữ liệu đầu vào
    if (empty($_POST['title']) || empty($_POST['categories']) || empty($_POST['content'])) {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Vui lòng không để trống")]);
        return;
    }

    // Tạo slug từ tiêu đề
    $slug = slugify($_POST['title']);
    $slug_exists = $app->count("news", ["slug" => $slug, "id[!]" => $vars['id'], "deleted" => 0]);
    if ($slug_exists) {
        $slug .= '-' . time();
    }

    // Xử lý hình ảnh
    $image_url = $news['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $handle = $app->upload($_FILES['image']);
        $path_upload = 'templates/uploads/news/';
        if (!is_dir($path_upload)) {
            mkdir($path_upload, 0755, true);
        }
        $new_image_name = $jatbi->active();
        if ($handle->uploaded) {
            $handle->allowed = ['image/*'];
            $handle->file_new_name_body = $new_image_name;
            $handle->Process($path_upload);
            if ($handle->processed) {
                $image_url = $new_image_name . '.' . $handle->file_dst_name_ext;
            }
        }
    }

    // Dữ liệu để cập nhật
    $update = [
        "title" => $app->xss($_POST['title']),
        "slug" => $slug,
        "content" => $_POST['content'],
        "excerpt" => $app->xss($_POST['description']),
        "category_id" => $app->xss($_POST['categories']),
        "image_url" => $image_url,
        "published_at" => !empty($_POST['publish_at']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_at'])) : null,
        "status" => $app->xss($_POST['status'] ?? 'A'),
        "updated_at" => date('Y-m-d H:i:s'),
    ];

    $app->update("news", $update, ["id" => $vars['id']]);

    // Ghi log
    $jatbi->logs('news', 'news-edit', $update);

    echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
})->setPermissions(['news.edit']);

// Route thay đổi trạng thái tin tức
$app->router("/admin/news-status/{id}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    $news = $app->get("news", "*", ["id" => $vars['id'], "deleted" => 0]);
    if ($news) {
        $status = $news['status'] === 'A' ? 'D' : 'A';
        $app->update("news", ["status" => $status], ["id" => $vars['id']]);
        $jatbi->logs('news', 'news-status', $news);
        echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
    } else {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Không tìm thấy dữ liệu")]);
    }
})->setPermissions(['news.edit']);

// Route xóa tin tức
$app->router("/admin/news-deleted", 'GET', function($vars) use ($app, $jatbi) {
    $app->setGlobalFile(__DIR__ . '/../../includes/global.php');
    $vars['title'] = $jatbi->lang("Xóa tin tức");
    echo $app->render('templates/common/deleted.html', $vars, 'global');
})->setPermissions(['news.deleted']);

$app->router("/admin/news-deleted", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    $box_ids = explode(',', $app->xss($_GET['box']));
    $news_items = $app->select("news", "*", ["id" => $box_ids, "deleted" => 0]);
    if (count($news_items) > 0) {
        $titles = [];
        foreach ($news_items as $news) {
            $app->update("news", ["deleted" => 1], ["id" => $news['id']]);
            $titles[] = $news['title'];
        }
        $jatbi->logs('news', 'news-deleted', $news_items);
        $jatbi->trash('/admin/news-restore', "Tin tức: " . implode(', ', $titles), ["database" => 'news', "data" => $box_ids]);
        echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
    } else {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Có lỗi xảy ra")]);
    }
})->setPermissions(['news.deleted']);

// Route khôi phục tin tức
$app->router("/admin/news-restore/{id}", 'GET', function($vars) use ($app, $jatbi) {
    $app->setGlobalFile(__DIR__ . '/../../includes/global.php');
    $vars['data'] = $app->get("trashs", "*", ["active" => $vars['id'], "deleted" => 0]);
    if ($vars['data']) {
        echo $app->render('templates/common/restore.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['news.deleted']);

$app->router("/admin/news-restore/{id}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    $trash = $app->get("trashs", "*", ["active" => $vars['id'], "deleted" => 0]);
    if ($trash) {
        $data = json_decode($trash['data']);
        foreach ($data->data as $news_id) {
            $app->update("news", ["deleted" => 0], ["id" => $news_id]);
        }
        $app->delete("trashs", ["id" => $trash['id']]);
        $jatbi->logs('news', 'news-restore', $data);
        echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
    } else {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Có lỗi xảy ra")]);
    }
})->setPermissions(['news.deleted']);
?>