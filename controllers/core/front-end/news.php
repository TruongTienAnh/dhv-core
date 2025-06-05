<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

// Hàm chung để lấy bài viết, phân trang và xử lý tìm kiếm
$newsHandler = function($vars) use ($app, $jatbi, $setting) {
    // Tiêu đề trang
    $vars['title'] = $jatbi->lang('Tin tức');

    // Số bài viết tối đa mỗi trang
    $perPage = 4;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    // Lấy từ khóa tìm kiếm
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $vars['search_query'] = $searchQuery;

    // Lấy slug danh mục từ URL (nếu có)
    $categorySlug = $vars['slug'] ?? '';
    $vars['category_slug'] = $categorySlug;

    // Nếu không có slug, hiển thị tất cả danh mục; nếu có slug, chỉ lấy danh mục tương ứng
    $categoryId = null;
    if (!empty($categorySlug)) {
        $category = $app->get("categories_news", ["id"], ["slug" => $categorySlug, "deleted" => 0, "status" => 'A']);
        if (!$category) {
            http_response_code(404);
            echo "Danh mục không tồn tại.";
            return;
        }
        $categoryId = $category['id'];
    }

    // Truy vấn tất cả danh mục, chỉ lấy danh mục có deleted = 0 và status = 'A', sắp xếp theo tổng views giảm dần
    $all_categories = $app->select("categories_news", [
        "[>]news" => ["id" => "category_id"]
    ], [
        "categories_news.id",
        "categories_news.name",
        "categories_news.slug",
        "total_views" => $app->raw("SUM(news.views)"),
        "count" => $app->raw("COUNT(news.id)")
    ], [
        "categories_news.deleted" => 0,
        "categories_news.status" => 'A',
        "news.status" => 'A',
        "news.deleted" => 0,
        "GROUP" => "categories_news.id",
        "ORDER" => ["total_views" => "DESC"]
    ]);

    // Chuẩn hóa dữ liệu danh mục
    foreach ($all_categories as &$category) {
        $category['name'] = mb_strtoupper($category['name'], 'UTF-8');
        $category['count'] = (int)$category['count'];
    }
    unset($category); // Hủy tham chiếu
    $vars['all_categories'] = $all_categories;

    // Kiểm tra nếu không có danh mục
    if (empty($all_categories)) {
        $vars['category_posts'] = [];
        $vars['total_pages'] = 0;
        echo $app->render('templates/dhv/news.html', $vars);
        return;
    }

    // Xây dựng điều kiện truy vấn
    $conditions = [
        "news.status" => 'A',
        "news.deleted" => 0,
        "category_id" => $app->select("categories_news", "id", [
            "deleted" => 0,
            "status" => 'A'
        ])
    ];
    if ($categoryId !== null) {
        $conditions["category_id"] = $categoryId;
    }
    if (!empty($searchQuery)) {
        $conditions["OR"] = [
            "news.title[~]" => "%{$searchQuery}%",
            "news.content[~]" => "%{$searchQuery}%"
        ];
    }

    // Tính tổng số bài viết
    $totalPosts = $app->count("news", "id", [
        "AND" => $conditions
    ]);
    $totalPages = ceil($totalPosts / $perPage);

    // Lấy bài viết cho trang hiện tại
    $posts = $app->select("news", [
        "[>]categories_news" => ["category_id" => "id"]
    ], [
        "news.id",
        "news.title",
        "news.slug",
        "news.excerpt",
        "news.content",
        "news.image_url",
        "news.views",
        "news.published_at",
        "news.category_id",
        "categories_news.name(category_name)",
        "categories_news.slug(category_slug)"
    ], [
        "AND" => $conditions,
        "LIMIT" => [($currentPage - 1) * $perPage, $perPage],
        "ORDER" => ["news.views" => "DESC"]
    ]);

    // Chuẩn hóa dữ liệu bài viết
    $category_posts = [];
    foreach ($posts as $post) {
        $category_posts[] = [
            'category' => [
                'id' => $post['category_id'],
                'name' => mb_strtoupper($post['category_name'], 'UTF-8'),
                'slug' => $post['category_slug']
            ],
            'post' => [
                'id' => $post['id'],
                'title' => mb_strtoupper($post['title'], 'UTF-8'),
                'slug' => $post['slug'],
                'excerpt' => isset($post['excerpt']) && $post['excerpt'] 
                    ? $post['excerpt'] 
                    : (isset($post['excerpt']) 
                        ? substr(strip_tags((string)$post['excerpt']), 0, 150) . '...' 
                        : ''),
                'content' => substr(strip_tags($post['content']), 0, 150) . '...',
                'image_url' => $post['image_url'] ?: 'blog-grid-1.jpg',
                'views' => (int)($post['views'] ?? 0),
                'published_at' => $post['published_at'] ? date('d/m/Y', strtotime($post['published_at'])) : ''
            ]
        ];
    }

    // Truyền dữ liệu vào template
    $vars['category_posts'] = $category_posts;
    $vars['current_page'] = $currentPage;
    $vars['total_pages'] = $totalPages;
    $vars['setting'] = $setting;
    $vars['app'] = $app;

    echo $app->render('templates/dhv/news.html', $vars);
};

// Đăng ký 2 route riêng biệt, dùng chung handler
$app->router("/news", 'GET', $newsHandler);
$app->router("/news/{slug}", 'GET', $newsHandler);

// Hàm xử lý chi tiết bài viết
$newsDetailHandler = function($vars) use ($app, $jatbi, $setting) {
    // Lấy slug bài viết từ URL
    $postSlug = $vars['slug'] ?? '';
    if (empty($postSlug)) {
        http_response_code(404);
        echo "Bài viết không tồn tại.";
        return;
    }

    // Lấy chi tiết bài viết
    $post = $app->get("news", [
        "[>]categories_news" => ["category_id" => "id"]
    ], [
        "news.id",
        "news.title",
        "news.slug",
        "news.excerpt",
        "news.content",
        "news.image_url",
        "news.views",
        "news.published_at",
        "news.category_id",
        "categories_news.name(category_name)",
        "categories_news.slug(category_slug)"
    ], [
        "news.slug" => $postSlug,
        "news.status" => 'A',
        "news.deleted" => 0,
        "categories_news.status" => 'A',
        "categories_news.deleted" => 0
    ]);

    if (!$post) {
        http_response_code(404);
        echo "Bài viết không tồn tại.";
        return;
    }

    // Chuẩn hóa dữ liệu bài viết
    $post_data = [
        'id' => $post['id'],
        'title' => mb_strtoupper($post['title'], 'UTF-8'),
        'slug' => $post['slug'],
        'content' => $post['content'], // Giữ nguyên HTML
        'excerpt' => isset($post['excerpt']) && $post['excerpt'] 
            ? $post['excerpt'] 
            : (isset($post['excerpt']) 
                ? substr(strip_tags((string)$post['excerpt']), 0, 150) . '...' 
                : ''),
        'image_url' => $post['image_url'] ?: 'blog-grid-1.jpg',
        'views' => (int)($post['views'] ?? 0),
        'published_at' => $post['published_at'] ? date('d/m/Y', strtotime($post['published_at'])) : '',
        'category' => [
            'id' => $post['category_id'],
            'name' => mb_strtoupper($post['category_name'], 'UTF-8'),
            'slug' => $post['category_slug']
        ]
    ];

    // Lấy tất cả danh mục cho sidebar
    $all_categories = $app->select("categories_news", [
        "[>]news" => ["id" => "category_id"]
    ], [
        "categories_news.id",
        "categories_news.name",
        "categories_news.slug",
        "total_views" => $app->raw("SUM(news.views)"),
        "count" => $app->raw("COUNT(news.id)")
    ], [
        "categories_news.deleted" => 0,
        "categories_news.status" => 'A',
        "news.status" => 'A',
        "news.deleted" => 0,
        "GROUP" => "categories_news.id",
        "ORDER" => ["total_views" => "DESC"]
    ]);

    // Chuẩn hóa dữ liệu danh mục
    foreach ($all_categories as &$category) {
        $category['name'] = mb_strtoupper($category['name'], 'UTF-8');
        $category['count'] = (int)$category['count'];
    }
    unset($category);

    // Truyền dữ liệu vào template
    $vars['title'] = $post_data['title'];
    $vars['post'] = $post_data;
    $vars['all_categories'] = $all_categories;
    $vars['category_slug'] = $post_data['category']['slug'];
    $vars['search_query'] = '';
    $vars['setting'] = $setting;
    $vars['app'] = $app;

    echo $app->render('templates/dhv/news-detail.html', $vars);
};  

$app->router("/news-detail/{slug}", 'GET', $newsDetailHandler);

?>