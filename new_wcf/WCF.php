<?php
//清除核心缓存
define('NO_CACHE_RUNTIME' ,True);
//define('APP_PATH' ,  WEB_ROOT_DIR);
// 定义 ThinkPHP 框架路径 ( 相对于入口文件 )
define('WCF_PATH', dirname(__FILE__));
define('THINK_PATH', WCF_PATH . '/ThinkPHP/');

define('APP_PATH' , WEB_ROOT_DIR);
require(THINK_PATH."ThinkPHP.php");
