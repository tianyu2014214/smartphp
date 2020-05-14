<?php

namespace smart;

use smart\exception\HttpException;

class App
{
    // 应用调试模式
    public static $debug = false;

    // 应用命名空间
    public static $namespace = 'app';

    // 路由分发信息
    private static $dispatch = [];

    // 额外文件加载列表
    private static $extraFileList = [];

    /**
     * 执行应用程序
     */
    public static function run()
    {
        // 初始化
        self::init();

        // 检查是否系统维护
        self::maintenance();

        // 分析PATH_INFO
        self::pathinfo();

        // 路由分发
        self::dispatch();
    }

    /**
     * 应用程序初始化
     */
    public static function init()
    {
        // 模块公共文件、配置文件加载
        $conf = self::loader();

        // 应用调试模式
        self::$debug = $conf['app_debug'];
        if (!self::$debug) {
            ini_set('display_errors', 'Off');
        }

        // 注册应用命名空间
        if (defined('APP_NAMESPACE')) {
            self::$namespace = APP_NAMESPACE;
        }
        Loader::addNamespace(self::$namespace, APP_PATH);
        // 注册其他根命名空间
        if (!empty($conf['root_namespace'])) {
            Loader::addNamespace($conf['root_namespace']);
        }

        // 设置系统时区
        date_default_timezone_set($conf['default_timezone']);

        // 加载模块配置信息
        $request = Request::instance();
        if ($module = $request->module()) {
            self::loader($module);
        }

        // 初始化模块/控制器/方法信息
        self::$dispatch = [
            'module'     => $module,
            'controller' => $request->controller(),
            'action'     => $request->action()
        ];
    }

    /**
     * 系统维护功能
     * @param  string $module     模块
     * @param  string $controller 控制器
     * @param  string $action     方法
     * @return void
     */
    public static function maintenance($module = '', $controller = '', $action = '')
    {
        // 获取模块、控制器和方法信息
        $module     = $module ?: self::$dispatch['module'];
        $controller = $controller ?: self::$dispatch['controller'];
        $action     = $action ?: self::$dispatch['action'];

        // 系统级别维护
        if (defined('SYSTEM_MAINTENANCE') && SYSTEM_MAINTENANCE || Config::get('app_status') == 2) {
            throw new HttpException(405, '系统维护！请稍后再试～');
        }

        // 部分功能模块维护
        $maintenanceList  = file_exists($path = DATA_PATH . 'maintenance.php') ? __include_file($path) : [];
        $module = $module ? $module . '\\' : '';
        $mca    = $module . $controller . '\\' . $action;
        if (isset($maintenanceList[$mca])) {
            throw new HttpException(405, $maintenanceList[$mca]);
        }
    }

    /**
     * 加载应用初始化文件
     * @param  string $module 模块名
     * @return array
     */
    private static function loader($module = '')
    {
        // 定位模块目录
        $module = $module ? $module . DS : '';

        // 加载模块配置、数据库配置
        is_file($confPath  = CONFIG_PATH.$module.'config'.EXT) && Config::load($confPath);
        is_file($pdoPath   = CONFIG_PATH.$module.'pdo'   .EXT) && Config::load($pdoPath, 'pdo', 'database');
        is_file($redisPath = CONFIG_PATH.$module.'redis' .EXT) && Config::load($redisPath, 'redis', 'database');

        // 加载扩展函数文件
        Loader::loadExtraFile();

        // 加载公共文件
        $path = APP_PATH . $module . 'common' . EXT;
        if (is_file($path)) {
            include $path;
        }

        // 返回配置信息
        return Config::get();
    }

    /**
     * 分析PATH_INFO
     */
    private static function pathinfo()
    {
        // 获取模块、控制器、方法
        $module     = self::$dispatch['module'];
        $controller = self::$dispatch['controller'];
        $action     = self::$dispatch['action'];

        // 定义BIND_CONTROLLER 和 BIND_ACTION
        $root       = App::$namespace . '\\';
        $module     = $module ? $module . '\\' : '';
        $namespace  = $root . $module . 'controller\\';
        $controller = $namespace . ucfirst($controller);
        defined('BIND_MODULE') or define('BIND_MODULE', rtrim($module, '\\'));
        define('BIND_CONTROLLER', $controller);
        define('BIND_ACTION', $action);
    }

    /**
     * 路由分发
     */
    private static function dispatch()
    {
        // 检查控制器是否存在
        if (!class_exists(BIND_CONTROLLER)) {
            throw new HttpException(404, '控制器不存在');
        }

        // 实例化控制器，并调用相应的方法
        $curCtrl = BIND_CONTROLLER;
        $action  = BIND_ACTION;
        $ctrlobj = new $curCtrl();
        
        if (method_exists($ctrlobj, $action)) {
            // 执行控制器方法
            call_user_func_array([$ctrlobj, $action], []);
        } else {
            // 控制器方法不存在
            throw new HttpException(404, '页面不存在');
        }
    }
}
