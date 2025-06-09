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

    // Nếu không có slug, hiển thị tất cả danh mục; nếu có slug, chỉ lấy danh mục tương ứng
    $categoryId = null;
    if (!empty($categorySlug)) {
        $category = $app->get("categories", ["id"], ["slug" => $categorySlug]);
        if (!$category) {
            http_response_code(404);
            echo "Danh mục không tồn tại.";
            return;
        }
        $categoryId = $category['id'];
    }
    $vars['category_slug'] = $categorySlug;

    // Truy vấn tất cả danh mục, sắp xếp theo tổng views giảm dần
    $all_categories = $app->select("categories", [
        "[>]news" => ["id" => "category_id"]
    ], [
        "categories.id",
        "categories.name",
        "categories.slug",
        "total_views" => $app->raw("SUM(news.views)")
    ], [
        "news.status" => 'A',
        "GROUP" => "categories.id",
        "ORDER" => ["total_views" => "DESC"]
    ]);

    // Kiểm tra nếu không có danh mục
    if (empty($all_categories)) {
        $vars['category_posts'] = [];
        $vars['total_pages'] = 0;
        echo $app->render('templates/dhv/news.html', $vars);
        return;
    }

    // Lọc danh mục nếu có categoryId
    if ($categoryId !== null) {
        $all_categories = array_filter($all_categories, function($category) use ($categoryId) {
            return $category['id'] == $categoryId;
        });
    }

    // Lấy tất cả bài viết theo từng danh mục, sắp xếp theo views
    $all_posts = [];
    foreach ($all_categories as $category) {
        $conditions = [
            "category_id" => $category['id'],
            "status" => 'A',
            "ORDER" => ["views" => "DESC"]
        ];

        // Nếu có từ khóa tìm kiếm, thêm điều kiện tìm kiếm vào truy vấn
        if (!empty($searchQuery)) {
            $conditions["OR"] = [
                "title[~]" => "%{$searchQuery}%",
                "content[~]" => "%{$searchQuery}%"
            ];
        }

        $posts = $app->select("news", [
            "id",
            "title",
            "slug",
            "content",
            "image_url",
            "views",
            "published_at",
            "category_id"
        ], $conditions);

        if (!empty($posts)) {
            $all_posts[] = [
                'category' => $category,
                'posts' => $posts,
                'total_posts' => count($posts)
            ];
        }
    }

    // Phân trang thủ công: tối đa 4 bài/trang, tối đa 2 danh mục/trang, ưu tiên hết bài của danh mục hiện tại
    $postsPerPage = [];
    $page = 1;
    $currentPostCount = 0;
    $currentCategoryCount = 0;
    $currentPagePosts = [];
    $postIndex = array_fill(0, count($all_posts), 0);
    $categoriesOnPage = [];
    $currentCatIndex = 0;
    $totalPosts = 0;

    while ($currentCatIndex < count($all_posts)) {
        $category_data = $all_posts[$currentCatIndex];
        $posts = $category_data['posts'];
        $category = $category_data['category'];

        $remainingPosts = count($posts) - $postIndex[$currentCatIndex];

        if ($remainingPosts <= 0) {
            $currentCatIndex++;
            continue;
        }

        if (!in_array($category['id'], $categoriesOnPage)) {
            if ($currentCategoryCount >= 2) {
                if ($currentPostCount > 0) {
                    $postsPerPage[$page] = $currentPagePosts;
                    $page++;
                    $currentPagePosts = [];
                    $currentPostCount = 0;
                    $currentCategoryCount = 0;
                    $categoriesOnPage = [];
                }
            }
            $categoriesOnPage[] = $category['id'];
            $currentCategoryCount++;
        }

        $postsNeeded = $perPage - $currentPostCount;

        if ($postsNeeded <= 0) {
            $postsPerPage[$page] = $currentPagePosts;
            $page++;
            $currentPagePosts = [];
            $currentPostCount = 0;
            $currentCategoryCount = 0;
            $categoriesOnPage = [];
            continue;
        }

        $postsToAdd = min($remainingPosts, $postsNeeded);

        for ($i = 0; $i < $postsToAdd; $i++) {
            $currentPagePosts[] = [
                'category' => $category,
                'post' => $posts[$postIndex[$currentCatIndex]]
            ];
            $postIndex[$currentCatIndex]++;
            $currentPostCount++;
        }

        if ($postIndex[$currentCatIndex] >= count($posts)) {
            $currentCatIndex++;
        }

        if ($currentPostCount >= $perPage) {
            $postsPerPage[$page] = $currentPagePosts;
            $page++;
            $currentPagePosts = [];
            $currentPostCount = 0;
            $currentCategoryCount = 0;
            $categoriesOnPage = [];
        }
    }

    if ($currentPostCount > 0) {
        $postsPerPage[$page] = $currentPagePosts;
    }

    // Tối ưu: Tính tổng số bài viết trực tiếp
    $totalPosts = $app->count("news", ["status" => 'A']);
    $totalPages = ceil($totalPosts / $perPage);

    $category_posts = isset($postsPerPage[$currentPage]) ? $postsPerPage[$currentPage] : [];

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
?>