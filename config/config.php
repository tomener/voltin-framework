<?php
return [
    'system' => [
        'common_dir' => 'common'
    ],
    'url' => [
        'c' => 'Index', //默认控制器
        'a' => 'index', //默认操作
        'mode' => 1, // 0 普通模式; 1PATHINFO模式; 2REWRITE 模式; 3兼容模式 默认为PATHINFO模式
        'r' => 'r', //兼容模式标识符
        'join' => '/',
        'ext' => '/',
    ],
    'cache' => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path' => ROOT_PATH . 'cache' . DS,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],
    'config' => [
        'path' => ROOT_PATH . 'config' . DS
    ],
    'log' => [
        'record' => true,
        'path' => ROOT_PATH . 'logs' . DS,
    ],
    'session' => [
        'name' => 'TMP',
        'id' => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'timo',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],
    'default_app' => 'web',
    'var_jsonp_callback' => '__callback',
    'default_jsonp_handler' => 'jsonp_handler',
    'default_return_type' => 'html',
    // 默认跳转页面对应的模板文件
    'jump_success_tpl'  => FRAME_PATH . 'tpl' . DS . 'jump.tpl.php',
    'jump_error_tpl'    => FRAME_PATH . 'tpl' . DS . 'jump.tpl.php',
];
