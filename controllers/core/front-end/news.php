<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/news", 'GET', function($vars) use ($app, $jatbi, $setting) {
    // Tiêu đề trang
    $vars['title'] = $jatbi->lang('Tin tức');

    // Số bài viết tối đa mỗi trang
    $perPage = 4;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    // Lấy từ khóa tìm kiếm
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $vars['search_query'] = $searchQuery; // Truyền từ khóa tìm kiếm vào template

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
    $currentCatIndex = 0; // Theo dõi danh mục hiện tại đang xử lý
    $totalPosts = 0;

    while ($currentCatIndex < count($all_posts)) {
        $category_data = $all_posts[$currentCatIndex];
        $posts = $category_data['posts'];
        $category = $category_data['category'];

        // Số bài còn lại trong danh mục này
        $remainingPosts = count($posts) - $postIndex[$currentCatIndex];

        // Nếu danh mục này đã hết bài, chuyển sang danh mục tiếp theo
        if ($remainingPosts <= 0) {
            $currentCatIndex++;
            continue;
        }

        // Kiểm tra số danh mục đã có trên trang hiện tại
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

        // Số bài cần thêm để lấp đầy trang hiện tại
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

        // Số bài có thể thêm từ danh mục này
        $postsToAdd = min($remainingPosts, $postsNeeded);

        // Thêm bài viết vào trang hiện tại
        for ($i = 0; $i < $postsToAdd; $i++) {
            $currentPagePosts[] = [
                'category' => $category,
                'post' => $posts[$postIndex[$currentCatIndex]]
            ];
            $postIndex[$currentCatIndex]++;
            $currentPostCount++;
        }

        // Nếu danh mục hiện tại đã hết bài, chuyển sang danh mục tiếp theo
        if ($postIndex[$currentCatIndex] >= count($posts)) {
            $currentCatIndex++;
        }

        // Nếu đã đủ 4 bài, chuyển sang trang mới
        if ($currentPostCount >= $perPage) {
            $postsPerPage[$page] = $currentPagePosts;
            $page++;
            $currentPagePosts = [];
            $currentPostCount = 0;
            $currentCategoryCount = 0;
            $categoriesOnPage = [];
        }
    }

    // Nếu còn bài viết trong trang cuối, thêm vào
    if ($currentPostCount > 0) {
        $postsPerPage[$page] = $currentPagePosts;
    }

    // Tính tổng số bài viết
    foreach ($all_posts as $category_data) {
        $totalPosts += $category_data['total_posts'];
    }

    // Tính tổng số trang
    $totalPages = ceil($totalPosts / $perPage);

    // Lấy bài viết cho trang hiện tại
    $category_posts = isset($postsPerPage[$currentPage]) ? $postsPerPage[$currentPage] : [];

    // Truyền dữ liệu vào template
    $vars['category_posts'] = $category_posts;
    $vars['current_page'] = $currentPage;
    $vars['total_pages'] = $totalPages;
    $vars['setting'] = $setting;
    $vars['app'] = $app;

    echo $app->render('templates/dhv/news.html', $vars);
});
?>