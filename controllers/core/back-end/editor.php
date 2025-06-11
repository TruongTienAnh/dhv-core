<?php
if (!defined('ECLO')) die("Hacking attempt");

// Include file library.php để sử dụng hàm generateSlug()
require_once __DIR__ . '/library.php';

$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

// Thư mục lưu trữ trang và media
define('PAGE_STORAGE_PATH', __DIR__ . '/../../public/editor/my-pages/');
define('MEDIA_STORAGE_PATH', __DIR__ . '/../../public/editor/media/');
define('PAGE_PUBLIC_URL', '/editor/my-pages/');
define('MEDIA_PUBLIC_URL', '/editor/media/');

// Đảm bảo các thư mục tồn tại và có quyền ghi
if (!is_dir(PAGE_STORAGE_PATH)) {
    mkdir(PAGE_STORAGE_PATH, 0755, true);
}
if (!is_dir(MEDIA_STORAGE_PATH)) {
    mkdir(MEDIA_STORAGE_PATH, 0755, true);
}

// Route cho trình chỉnh sửa giao diện
$app->router("/admin/editor", 'GET', function($vars) use ($app, $jatbi, $setting) {
    $vars['title'] = $jatbi->lang("Trình chỉnh sửa giao diện");
    $vars['editor_css'] = '/editor/css/editor.css'; // CSS của VvvebJs
    $vars['editor_js'] = [
        '/editor/libs/builder/builder.js',
        '/editor/libs/builder/undo.js',
        '/editor/libs/builder/inputs.js',
        '/editor/libs/builder/components-bootstrap5.js',
        '/editor/libs/builder/components-widgets.js',
        '/editor/libs/builder/plugin-media.js',
        '/editor/libs/builder/plugin-codemirror.js',
    ];
    echo $app->render('templates/backend/editor/editor.html', $vars);
})->setPermissions(['editor']);

// Route để lấy danh sách trang
$app->router("/editor/get-pages", 'GET', function($vars) use ($app, $jatbi) {
    $pages = [];
    $files = glob(PAGE_STORAGE_PATH . '*.html');
    foreach ($files as $file) {
        $filename = basename($file);
        $pages[] = [
            'name' => $filename,
            'title' => ucfirst(str_replace('-', ' ', pathinfo($filename, PATHINFO_FILENAME))),
            'url' => PAGE_PUBLIC_URL . $filename,
            'file' => $filename,
        ];
    }
    header('Content-Type: application/json');
    echo json_encode(['pages' => $pages]);
})->setPermissions(['editor']);

// Route để lưu trang
$app->router("/editor/save-page", 'POST', function($vars) use ($app, $jatbi) {
    $data = json_decode(file_get_contents('php://input'), true);
    $html = $data['html'] ?? '';
    $file = $data['file'] ?? '';
    $folder = $data['folder'] ?? '';

    if (empty($file) || empty($html)) {
        http_response_code(400);
        echo json_encode(['error' => $jatbi->lang('Thiếu thông tin file hoặc nội dung HTML')]);
        return;
    }

    // Xử lý tên file để đảm bảo an toàn
    $file = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $file);
    $file = generateSlug($file); // Sử dụng hàm từ library.php
    if (!preg_match('/\.html$/', $file)) {
        $file .= '.html';
    }

    // Đường dẫn lưu file
    $savePath = PAGE_STORAGE_PATH . ($folder ? rtrim($folder, '/') . '/' : '') . $file;

    // Kiểm tra quyền ghi
    if (!is_writable(dirname($savePath))) {
        http_response_code(500);
        echo json_encode(['error' => $jatbi->lang('Không có quyền ghi vào thư mục')]);
        return;
    }

    // Lưu file
    if (file_put_contents($savePath, $html) !== false) {
        echo json_encode(['message' => $jatbi->lang('Lưu trang thành công'), 'file' => $file]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $jatbi->lang('Lỗi khi lưu trang')]);
    }
})->setPermissions(['editor']);

// Route để tải nội dung trang
$app->router("/editor/load-page", 'GET', function($vars) use ($app, $jatbi) {
    $file = $vars['file'] ?? '';
    if (empty($file)) {
        http_response_code(400);
        echo json_encode(['error' => $jatbi->lang('Thiếu tên file')]);
        return;
    }

    // Xử lý tên file để đảm bảo an toàn
    $file = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $file);
    $filePath = PAGE_STORAGE_PATH . $file;

    if (!file_exists($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        echo json_encode(['error' => $jatbi->lang('Trang không tồn tại')]);
        return;
    }

    $html = file_get_contents($filePath);
    header('Content-Type: application/json');
    echo json_encode(['html' => $html, 'file' => $file]);
})->setPermissions(['editor']);

// Route để upload ảnh
$app->router("/editor/upload-image", 'POST', function($vars) use ($app, $jatbi) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['content' => $jatbi->lang('Không có file ảnh hoặc lỗi upload')]);
        return;
    }

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['content' => $jatbi->lang('Định dạng file không được hỗ trợ')]);
        return;
    }

    // Tạo tên file duy nhất
    $filename = generateSlug(pathinfo($file['name'], PATHINFO_FILENAME)) . '-' . time() . '.' . $ext;
    $destination = MEDIA_STORAGE_PATH . $filename;

    if (!is_writable(MEDIA_STORAGE_PATH)) {
        http_response_code(500);
        echo json_encode(['content' => $jatbi->lang('Không có quyền ghi vào thư mục media')]);
        return;
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $url = MEDIA_PUBLIC_URL . $filename;
        echo json_encode(['content' => $url]);
    } else {
        http_response_code(500);
        echo json_encode(['content' => $jatbi->lang('Lỗi khi lưu ảnh')]);
    }
})->setPermissions(['editor']);