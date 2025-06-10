<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');
$common = $app->getValueData('common');
$permission = $app->getValueData('permission');

// Hàm slugify để tạo slug từ tiêu đề
// function slugify($string) {
//     $transliteration = [
//         'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
//         'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
//         'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
//         'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
//         'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
//         'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
//         'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
//         'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
//         'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
//         'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
//         'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
//         'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
//         'đ' => 'd',
//         'À' => 'A', 'Á' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
//         'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
//         'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
//         'È' => 'E', 'É' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
//         'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
//         'Ì' => 'I', 'Í' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
//         'Ò' => 'O', 'Ó' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
//         'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
//         'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
//         'Ù' => 'U', 'Ú' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
//         'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
//         'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',
//         'Đ' => 'D',
//     ];

//     $string = strtr($string, $transliteration);
//     $string = strtolower($string);
//     $string = preg_replace('/[^a-z0-9]+/', '-', $string);
//     $string = trim($string, '-');
//     return $string ?: 'default-slug';
// }

// Route danh sách dự án
$app->router("/admin/projects", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $app->setGlobalFile(__DIR__ . '/../../includes/global.php');
    $vars['title'] = $jatbi->lang("Quản lý dự án");
    $vars['add'] = '/admin/projects-add';
    $vars['deleted'] = '/admin/projects-deleted';
    echo $app->render('templates/backend/news-projects/projects.html', $vars);
})->setPermissions(['projects']);

// Route lấy dữ liệu danh sách dự án (AJAX)
$app->router("/admin/projects", 'POST', function($vars) use ($app, $jatbi) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);

    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'DESC');
    $status = $_POST['status'] ?? '';

    $validColumns = [
        0 => "id",
        1 => "title",
        2 => "client_name",
        3 => "start_date",
        4 => "status",
    ];
    $orderColumn = $validColumns[$orderColumnIndex] ?? "id";

    $where = [
        "AND" => ["projects.deleted" => 0],
        "LIMIT" => [$start, $length],
        "ORDER" => [$orderColumn => $orderDir]
    ];

    $andConditions = [];
    if (!empty($status)) {
        $andConditions["projects.status"] = $status;
    }
    if (!empty($searchValue)) {
        $andConditions["OR"] = [
            "projects.title[~]" => $searchValue,
            "projects.client_name[~]" => $searchValue
        ];
    }
    if (!empty($andConditions)) {
        $where["AND"] = array_merge($where["AND"], $andConditions);
    }

    $ids = $app->select("projects", "id", $where);
    $count = count($ids);

    $datas = [];
    $app->select("projects", [
        'projects.id',
        'projects.title',
        'projects.slug',
        'projects.client_name',
        'projects.image_url',
        'projects.start_date',
        'projects.status',
        'projects.created_at',
        'projects.updated_at',
        'projects.industry',
    ], $where, function ($data) use (&$datas, $app, $jatbi) {
        $datas[] = [
            "checkbox" => $app->component("box", ["data" => $data['id']]),
            "title" => $data['title'],
            "client_name" => $data['client_name'] ?? 'Chưa xác định',
            "image" => $data['image_url'] ? '<img src="/templates/uploads/projects/' . $data['image_url'] . '" alt="' . $data['title'] . '" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">' : 'Chưa có ảnh',
            "start_date" => $data['start_date'] ? date('d/m/Y', strtotime($data['start_date'])) : 'Chưa bắt đầu',
            "status" => $app->component("status", ["url" => "/admin/projects-status/" . $data['id'], "data" => $data['status'], "permission" => ['projects.edit']]),
            "action" => $app->component("action", [
                "button" => [
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Sửa"),
                        'permission' => ['projects.edit'],
                        'action' => ['data-url' => '/admin/projects-edit/' . $data['id'], 'data-action' => 'modal']
                    ],
                    [
                        'type' => 'button',
                        'name' => $jatbi->lang("Xóa"),
                        'permission' => ['projects.deleted'],
                        'action' => ['data-url' => '/admin/projects-deleted?box=' . $data['id'], 'data-action' => 'modal']
                    ],
                ]
            ]),
        ];
    });

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $datas
    ]);
})->setPermissions(['projects']);

// Route thêm dự án
$app->router("/admin/projects-add", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Thêm dự án");
    $vars['data'] = [
        "status" => 'A',
    ];
    echo $app->render('templates/backend/news-projects/projects-post.html', $vars, 'global');
})->setPermissions(['projects.add']);

$app->router("/admin/projects-add", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    if (empty($_POST['title']) || empty($_POST['description'])) {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Vui lòng không để trống")]);
        return;
    }

    $slug = slugify($_POST['title']);
    $slug_exists = $app->count("projects", ["slug" => $slug, "deleted" => 0]);
    if ($slug_exists) {
        $slug .= '-' . time();
    }

    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $handle = $app->upload($_FILES['image']);
        $path_upload = 'templates/uploads/projects/';
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

    $insert = [
        "title" => $app->xss($_POST['title']),
        "slug" => $slug,
        "client_name" => $app->xss($_POST['client_name']),
        "excerpt" => $app->xss($_POST['excerpt']),
        "description" => $_POST['description'],
        "start_date" => !empty($_POST['start_date']) ? date('Y-m-d', strtotime($_POST['start_date'])) : null,
        "end_date" => !empty($_POST['end_date']) ? date('Y-m-d', strtotime($_POST['end_date'])) : null,
        "image_url" => $image_url,
        "status" => $app->xss($_POST['status'] ?? 'A'),
        "industry" => $app->xss($_POST['industry']),
        "created_at" => date('Y-m-d H:i:s'),
        "updated_at" => date('Y-m-d H:i:s'),
        "deleted" => 0,
    ];

    $app->insert("projects", $insert);
    $project_id = $app->id();

    if (!empty($_FILES['additional_images'])) {
        foreach ($_FILES['additional_images']['name'] as $key => $name) {
            if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                $handle = $app->upload(['tmp_name' => $_FILES['additional_images']['tmp_name'][$key], 'name' => $name]);
                $new_image_name = $jatbi->active();
                if ($handle->uploaded) {
                    $handle->allowed = ['image/*'];
                    $handle->file_new_name_body = $new_image_name;
                    $handle->Process($path_upload);
                    if ($handle->processed) {
                        $additional_image_url = $new_image_name . '.' . $handle->file_dst_name_ext;
                        $app->insert("project_images", [
                            "project_id" => $project_id,
                            "image_url" => $additional_image_url,
                            "caption" => $app->xss($_POST['captions'][$key] ?? ''),
                            "status" => 'A',
                            "deleted" => 0
                        ]);
                    }
                }
            }
        }
    }

    $jatbi->logs('projects', 'projects-add', $insert);
    echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
})->setPermissions(['projects.add']);

// Route sửa dự án
$app->router("/admin/projects-edit/{id}", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Sửa dự án");
    $vars['data'] = $app->get("projects", "*", ["id" => $vars['id'], "deleted" => 0]);
    if ($vars['data']) {
        echo $app->render('templates/backend/news-projects/projects-post.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['projects.edit']);

$app->router("/admin/projects-edit/{id}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $project = $app->get("projects", "*", ["id" => $vars['id'], "deleted" => 0]);
    if (!$project) {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Không tìm thấy dữ liệu")]);
        return;
    }

    if (empty($_POST['title']) || empty($_POST['description'])) {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Vui lòng không để trống")]);
        return;
    }

    $slug = slugify($_POST['title']);
    $slug_exists = $app->count("projects", ["slug" => $slug, "id[!]" => $vars['id'], "deleted" => 0]);
    if ($slug_exists) {
        $slug .= '-' . time();
    }

    $image_url = $project['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $handle = $app->upload($_FILES['image']);
        $path_upload = 'templates/uploads/projects/';
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

    $update = [
        "title" => $app->xss($_POST['title']),
        "slug" => $slug,
        "client_name" => $app->xss($_POST['client_name']),
        "excerpt" => $app->xss($_POST['excerpt']),
        "description" => $_POST['description'],
        "start_date" => !empty($_POST['start_date']) ? date('Y-m-d', strtotime($_POST['start_date'])) : null,
        "end_date" => !empty($_POST['end_date']) ? date('Y-m-d', strtotime($_POST['end_date'])) : null,
        "image_url" => $image_url,
        "status" => $app->xss($_POST['status'] ?? 'A'),
        "industry" => $app->xss($_POST['industry']),
        "updated_at" => date('Y-m-d H:i:s'),
    ];

    $app->update("projects", $update, ["id" => $vars['id']]);

    if (!empty($_FILES['additional_images'])) {
        foreach ($_FILES['additional_images']['name'] as $key => $name) {
            if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                $handle = $app->upload(['tmp_name' => $_FILES['additional_images']['tmp_name'][$key], 'name' => $name]);
                $new_image_name = $jatbi->active();
                if ($handle->uploaded) {
                    $handle->allowed = ['image/*'];
                    $handle->file_new_name_body = $new_image_name;
                    $handle->Process($path_upload);
                    if ($handle->processed) {
                        $additional_image_url = $new_image_name . '.' . $handle->file_dst_name_ext;
                        $app->insert("project_images", [
                            "project_id" => $vars['id'],
                            "image_url" => $additional_image_url,
                            "caption" => $app->xss($_POST['captions'][$key] ?? ''),
                            "status" => 'A',
                            "deleted" => 0
                        ]);
                    }
                }
            }
        }
    }

    $jatbi->logs('projects', 'projects-edit', $update);
    echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
})->setPermissions(['projects.edit']);

// Route thay đổi trạng thái dự án
$app->router("/admin/projects-status/{id}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $project = $app->get("projects", "*", ["id" => $vars['id'], "deleted" => 0]);
    if ($project) {
        $status = $project['status'] === 'A' ? 'D' : 'A';
        $app->update("projects", ["status" => $status], ["id" => $vars['id']]);
        $jatbi->logs('projects', 'projects-status', $project);
        echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
    } else {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Không tìm thấy dữ liệu")]);
    }
})->setPermissions(['projects.edit']);

// Route xóa dự án
$app->router("/admin/projects-deleted", 'GET', function($vars) use ($app, $jatbi) {
    $vars['title'] = $jatbi->lang("Xóa dự án");
    echo $app->render('templates/common/deleted.html', $vars, 'global');
})->setPermissions(['projects.deleted']);

$app->router("/admin/projects-deleted", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $box_ids = explode(',', $app->xss($_GET['box']));
    $projects = $app->select("projects", "*", ["id" => $box_ids, "deleted" => 0]);
    if (count($projects) > 0) {
        $titles = [];
        foreach ($projects as $project) {
            $app->update("projects", ["deleted" => 1], ["id" => $project['id']]);
            $titles[] = $project['title'];
        }
        $jatbi->logs('projects', 'projects-deleted', $projects);
        $jatbi->trash('/admin/projects-restore', "Dự án: " . implode(', ', $titles), ["database" => 'projects', "data" => $box_ids]);
        echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
    } else {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Có lỗi xảy ra")]);
    }
})->setPermissions(['projects.deleted']);

// Route khôi phục dự án
$app->router("/admin/projects-restore/{id}", 'GET', function($vars) use ($app, $jatbi) {
    $vars['data'] = $app->get("trashs", "*", ["active" => $vars['id'], "deleted" => 0]);
    if ($vars['data']) {
        echo $app->render('templates/common/restore.html', $vars, 'global');
    } else {
        echo $app->render('templates/common/error-modal.html', $vars, 'global');
    }
})->setPermissions(['projects.deleted']);

$app->router("/admin/projects-restore/{id}", 'POST', function($vars) use ($app, $jatbi) {
    $app->header(['Content-Type' => 'application/json']);

    $trash = $app->get("trashs", "*", ["active" => $vars['id'], "deleted" => 0]);
    if ($trash) {
        $data = json_decode($trash['data']);
        foreach ($data->data as $project_id) {
            $app->update("projects", ["deleted" => 0], ["id" => $project_id]);
        }
        $app->delete("trashs", ["id" => $trash['id']]);
        $jatbi->logs('projects', 'projects-restore', $data);
        echo json_encode(['status' => 'success', 'content' => $jatbi->lang("Cập nhật thành công")]);
    } else {
        echo json_encode(['status' => 'error', 'content' => $jatbi->lang("Có lỗi xảy ra")]);
    }
})->setPermissions(['projects.deleted']);
?>