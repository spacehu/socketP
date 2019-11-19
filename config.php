<?php

/** 系统目录信息 */
define('SYSTEM_LOG', './log');
/** 网站信息 */
define('DOMAIN_NAME', 'localweb');
define('PORT', '80');
date_default_timezone_set('Asia/Shanghai');

return $config = [
    'env' => include_once("parem.env." . ENV . ".php"), // 配置设定 
    /** 常规系统设定 */
    'page_width' => 10, //分页
    'sysDelay' => 2, //系统延迟的秒数
    'cookie_pre' => '', //定义cookie的头部信息
    'cookie_life_time' => 1000 * 60 * 60 * 24/* */, //cookie存活的时间&session
    'language' => [
        'key' => 'zh_cn', //定义默认语言
    ],
    'log' => SYSTEM_LOG, //系统 日志
    'port' => PORT, //定义端口号
    'debug' => false, //调试器
];


