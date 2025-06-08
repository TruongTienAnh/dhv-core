<?php
    if (!defined('ECLO')) die("Hacking attempt");
    $setting = $app->getValueData('setting');
    $requests = [
        "main"=>[
            "name"=>$jatbi->lang("Chính"),
            "item"=>[
                '/'=>[
                    "menu"=>$jatbi->lang("Trang chủ"),
                    "url"=>'/',
                    "icon"=>'<i class="ti ti-dashboard"></i>',
                    "controllers"=>"controllers/core/back-end/main.php",
                    "main"=>'true',
                    "permission" => "",
                ],
            ],
        ],
        "page"=>[
            "name"=>'Admin',
            "item"=>[
                'users'=>[
                    "menu"=>$jatbi->lang("Người dùng"),
                    "url"=>'/users',
                    "icon"=>'<i class="ti ti-user "></i>',
                    "sub"=>[
                        'accounts'      =>[
                            "name"  => $jatbi->lang("Tài khoản"),
                            "router"=> '/users/accounts',
                            "icon"  => '<i class="ti ti-user"></i>',
                        ],
                        'permission'    =>[
                            "name"  => $jatbi->lang("Nhóm quyền"),
                            "router"=> '/users/permission',
                            "icon"  => '<i class="fas fa-universal-access"></i>',
                        ],
                    ],
                    "controllers"=>"controllers/core/back-end/users.php",
                    "main"=>'false',
                    "permission"=>[
                        'accounts'=> $jatbi->lang("Tài khoản"),
                        'accounts.add' => $jatbi->lang("Thêm tài khoản"),
                        'accounts.edit' => $jatbi->lang("Sửa tài khoản"),
                        'accounts.deleted' => $jatbi->lang("Xóa tài khoản"),
                        'permission'=> $jatbi->lang("Nhóm quyền"),
                        'permission.add' => $jatbi->lang("Thêm Nhóm quyền"),
                        'permission.edit' => $jatbi->lang("Sửa Nhóm quyền"),
                        'permission.deleted' => $jatbi->lang("Xóa Nhóm quyền"),
                    ]
                ],
                'admin'=>[
                    "menu"=>$jatbi->lang("Quản trị"),
                    "url"=>'/admin',
                    "icon"=>'<i class="ti ti-settings "></i>',
                    "sub"=>[
                        'blockip'   => [
                            "name"  => $jatbi->lang("Chặn truy cập"),
                            "router"    => '/admin/blockip',
                            "icon"  => '<i class="fas fa-ban"></i>',
                        ],
                        'trash'  => [
                            "name"  => $jatbi->lang("Thùng rác"),
                            "router"    => '/admin/trash',
                            "icon"  => '<i class="fa fa-list-alt"></i>',
                        ],
                        'logs'  => [
                            "name"  => $jatbi->lang("Nhật ký"),
                            "router"    => '/admin/logs',
                            "icon"  => '<i class="fa fa-list-alt"></i>',
                        ],
                        'config'    => [
                            "name"  => $jatbi->lang("Cấu hình"),
                            "router"    => '/admin/config',
                            "icon"  => '<i class="fa fa-cog"></i>',
                            "req"   => 'modal-url',
                        ],
                    ],
                    "controllers"=>"controllers/core/back-end/admin.php",
                    "main"=>'false',
                    "permission"=>[
                        'blockip'       =>$jatbi->lang("Chặn truy cập"),
                        'blockip.add'   =>$jatbi->lang("Thêm Chặn truy cập"),
                        'blockip.edit'  =>$jatbi->lang("Sửa Chặn truy cập"),
                        'blockip.deleted'=>$jatbi->lang("Xóa Chặn truy cập"),
                        'config'        =>$jatbi->lang("Cấu hình"),
                        'logs'          =>$jatbi->lang("Nhật ký"),
                        'trash'          =>$jatbi->lang("Thùng rác"),
                    ]
                ],
                'news-project'=>[
                    "menu"=>$jatbi->lang("Tin tức - Dự án"),
                    "url"=>'/admin/news',
                    "icon"=>'<i class="ti ti-user "></i>',
                    "sub"=>[
                        'news'      =>[
                            "name"  => $jatbi->lang("Tin tức"),
                            "router"=> '/admin/news',
                            "icon"  => '<i class="ti ti-news"></i>',
                        ],
                        'projects'    =>[
                            "name"  => $jatbi->lang("Dự án"),
                            "router"=> '/admin/projects',
                            "icon"  => '<i class="ti ti-briefcase"></i>',
                        ],
                    ],
                    "controllers" => [
                        "controllers/core/back-end/news.php",
                        "controllers/core/back-end/projects.php",  
                    ],
                    "main"=>'false',
                    "permission"=>[
                        'news'=> $jatbi->lang("Tin tức"),
                        'news.add' => $jatbi->lang("Thêm tin tức"),
                        'news.edit' => $jatbi->lang("Sửa tin tức"),
                        'news.deleted' => $jatbi->lang("Xóa tin tức"),
                        'projects'=> $jatbi->lang("Dự án"),
                        'projects.add' => $jatbi->lang("Thêm Dự án"),
                        'projects.edit' => $jatbi->lang("Sửa Dự án"),   
                        'projects.deleted' => $jatbi->lang("Xóa Dự án"),
                    ]
                ],
                    'consultation'=>[
                    "menu"=>$jatbi->lang("Lịch tư vấn"),
                    "url"=>'/admin/consultation',
                    "icon"=>'<i class="ti ti-user "></i>',
                    "controllers"=>"controllers/core/back-end/consultation.php",
                    "main"=>'false',
                    "permission"=>[
                        'consultation'=> $jatbi->lang("Lịch tư vấn"),
                        // 'accounts.add' => $jatbi->lang("Thêm tài khoản"),
                        // 'accounts.edit' => $jatbi->lang("Sửa tài khoản"),
                        // 'accounts.deleted' => $jatbi->lang("Xóa tài khoản"),
                    ]
                ],
                    'categories'=>[
                    "menu"=>$jatbi->lang("Danh mục"),
                    "url"=>'/admin/categories',
                    "icon"=>'<i class="ti ti-category"></i>',
                    "controllers"=>"controllers/core/back-end/categories.php",
                    "main"=>'false',
                    "permission"=>[
                        'categories'=> $jatbi->lang("Danh mục"),
                        // 'accounts.add' => $jatbi->lang("Thêm tài khoản"),
                        // 'accounts.edit' => $jatbi->lang("Sửa tài khoản"),
                        // 'accounts.deleted' => $jatbi->lang("Xóa tài khoản"),
                    ]
                ],
                    'library'=>[
                    "menu"=>$jatbi->lang("Thư viện số"),
                    "url"=>'/admin/library',
                    "icon"=>'<i class="ti ti-library "></i>',
                    "controllers"=>"controllers/core/back-end/library.php",
                    "main"=>'false',
                    "permission"=>[
                        'library'=> $jatbi->lang("Thư viện số"),
                        // 'accounts.add' => $jatbi->lang("Thêm tài khoản"),
                        // 'accounts.edit' => $jatbi->lang("Sửa tài khoản"),
                        // 'accounts.deleted' => $jatbi->lang("Xóa tài khoản"),
                    ]
                ],
                    'contact'=>[
                    "menu"=>$jatbi->lang("Liên hệ"),
                    "url"=>'/admin/contact',
                    "icon"=>'<i class="ti ti-contact"></i>',
                    "controllers"=>"controllers/core/back-end/contact.php",
                    "main"=>'false',
                    "permission"=>[
                        'contact'=> $jatbi->lang("Liên hệ"),
                        // 'accounts.add' => $jatbi->lang("Thêm tài khoản"),
                        // 'accounts.edit' => $jatbi->lang("Sửa tài khoản"),
                        // 'accounts.deleted' => $jatbi->lang("Xóa tài khoản"),
                    ]
                ],
                'services'=>[
                    "menu"=>$jatbi->lang("Dịch vụ - Chi tiết"),
                    "url"=>'/admin/services',
                    "icon"=>'<i class="ti ti-user "></i>',
                    "sub"=>[
                        'services'      =>[
                            "name"  => $jatbi->lang("Dịch vụ"),
                            "router"=> '/admin/services',
                            "icon"  => '<i class="ti ti-news"></i>',
                        ],
                        'services-detail'    =>[
                            "name"  => $jatbi->lang("Chi tiết dịch vụ"),
                            "router"=> '/admin/services-detail',
                            "icon"  => '<i class="ti ti-briefcase"></i>',
                        ],
                    ],
                    "controllers" => [
                        "controllers/core/back-end/services.php",
                        "controllers/core/back-end/services-detail.php",  
                    ],
                    "main"=>'false',
                    "permission"=>[
                        'services'=> $jatbi->lang("Dịch vụ"),
                        'services.add' => $jatbi->lang("Thêm dịch vụ"),
                        'services.edit' => $jatbi->lang("Sửa dịch vụ"),
                        'services.deleted' => $jatbi->lang("Xóa dịch vụ"),
                        'services-detail'=> $jatbi->lang("Chi tiết dịch vụ"),
                        'services-detail.add' => $jatbi->lang("Thêm Chi tiết dịch vụ"),
                        'services-detail.edit' => $jatbi->lang("Sửa Chi tiết dịch vụ"),   
                        'services-detail.deleted' => $jatbi->lang("Xóa Chi tiết dịch vụ"),
                    ]
                ],
            ],
        ],
    ];
    // foreach($requests as $request){
    //     foreach($request['item'] as $key_item =>  $items){
    //         $setRequest[] = [
    //             "key" => $key_item,
    //             "controllers" =>  $items['controllers'],
    //         ];
    //         if($items['main']!='true'){
    //             $SelectPermission[$items['menu']] = $items['permission'];
    //         }
    //         if (isset($items['permission']) && is_array($items['permission'])) {
    //             foreach($items['permission'] as $key_per => $per) {
    //                 $userPermissions[] = $key_per; 
    //             }
    //         }
    //     }
    // }
    foreach($requests as $request){
        foreach($request['item'] as $key_item =>  $items){
            if (is_array($items['controllers'])) {
                foreach($items['controllers'] as $controller) {
                    $setRequest[] = [
                        "key" => $key_item,
                        "controllers" => $controller,
                    ];
                }
            } else {
                $setRequest[] = [
                    "key" => $key_item,
                    "controllers" => $items['controllers'],
                ];
            }
            // Thêm controllers từ sub
            if (isset($items['sub']) && is_array($items['sub'])) {
                foreach ($items['sub'] as $sub_key => $sub_item) {
                    if (isset($sub_item['controllers'])) {
                        $setRequest[] = [
                            "key" => $sub_key,
                            "controllers" => $sub_item['controllers'],
                        ];
                    }
                }
            }
            if($items['main']!='true'){
                $SelectPermission[$items['menu']] = $items['permission'];
            }
            if (isset($items['permission']) && is_array($items['permission'])) {
                foreach($items['permission'] as $key_per => $per) {
                    $userPermissions[] = $key_per; 
                }
            }
        }
    }
?>