<?php

//----------------------------------
// SmartPHP公共入口文件
//----------------------------------

// 版本信息
const SMART_VERSION = '1.0';

// 类文件后缀
const EXT = '.php';

// 路径分隔符
define('DS', DIRECTORY_SEPARATOR);

// 环境变量
define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);
defined('RUN_ENV') or define('RUN_ENV', 'production');

// 系统常量定义
defined('SMART_PATH')   or define('SMART_PATH',     __DIR__ . DS);
defined('APP_PATH')     or define('APP_PATH',       dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('ROOT_PATH')    or define('ROOT_PATH',      dirname(realpath(APP_PATH)) . DS);
defined('DATA_PATH')    or define('DATA_PATH',      ROOT_PATH . 'data' . DS);
defined('EXTEND_PATH')  or define('EXTEND_PATH',    ROOT_PATH . 'extend' . DS);
defined('PUBLIC_PATH')  or define('PUBLIC_PATH',    ROOT_PATH . 'public' . DS);
defined('RUNTIME_PATH') or define('RUNTIME_PATH',   ROOT_PATH . 'runtime' . DS);
defined('VENDOR_PATH')  or define('VENDOR_PATH',    ROOT_PATH . 'vendor' . DS);
defined('CONFIG_PATH')  or define('CONFIG_PATH',    DATA_PATH . 'conf' . DS);
defined('UPLOAD_PATH')  or define('UPLOAD_PATH',    PUBLIC_PATH . 'upload' . DS);
defined('LOGS_PATH')    or define('LOGS_PATH',      RUNTIME_PATH . 'logs' . DS);
defined('LIB_PATH')     or define('LIB_PATH',       SMART_PATH . 'library' . DS);
defined('CORE_PATH')    or define('CORE_PATH',      LIB_PATH . 'smart' . DS);

// 加载系统函数库
require SMART_PATH . 'common/system.php';

// 载入Loader类
require CORE_PATH . 'Loader.php';

// 注册自动加载
\smart\Loader::register();

// 注册错误和异常处理机制
\smart\Error::register();

// 加载惯例配置文件
\smart\Config::set(include SMART_PATH . 'convention' . EXT);
