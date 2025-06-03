<?php
if (!defined('ECLO')) die("Hacking attempt");
$jatbi = new Jatbi($app);
$setting = $app->getValueData('setting');

$app->router("/news", 'GET', function($vars) use ($app, $jatbi, $setting) {
    // Tiêu đề trang
    $vars['title'] = $jatbi->lang('Tin tức');

    // Truy vấn 2 danh mục có tổng views cao nhất
    $top_categories = $app->select("categories", [
        "[>]news" => ["id" => "category_id"]
    ], [
        "categories.id",
        "categories.name",
        "categories.slug",
        "total_views" => $app->raw("SUM(news.views)")
    ], [
        "news.status" => 'A',
        "GROUP" => "categories.id",
        "ORDER" => ["total_views" => "DESC"],
        "LIMIT" => 2
    ]);

    // Với mỗi danh mục, lấy 2 bài viết có views cao nhất
    $category_posts = [];
    foreach ($top_categories as $category) {    
        $posts = $app->select("news", [
            "id",
            "title",
            "slug",
            "content",
            "image_url",
            "views",
            "published_at"
        ], [
            "category_id" => $category['id'],
            "status" => 'A',
            "ORDER" => ["views" => "DESC"],
            "LIMIT" => 2
        ]);
        
        $category_posts[] = [
            'category' => $category,
            'posts' => $posts
        ];
    }

    // Truyền dữ liệu vào template
    $vars['category_posts'] = $category_posts;

    echo $app->render('templates/dhv/news.html', $vars);
});
?>