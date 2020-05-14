<?php

namespace smart;

use smart\exception\HttpException;
use smart\exception\RouteException;

class Route
{
    // 路由规则
    private static $routelist = [];

    // 路由缓存文件名
    private static $routeCacheFile = 'route';

    /**
     * 解析URL地址为 模块/控制器/操作
     * @param  string  $pathinfo
     * @param  boolean $store
     * @return array
     */
    public static function getModule($pathinfo, $store = false)
    {
        // 获取路由匹配信息
        $matchResult = self::matchRoute($pathinfo);
        if (!empty($matchResult)) {
            // 路由匹配成功
            $mca          = $matchResult['mca'];
            $mca['route'] = $matchResult['route'];
        } elseif (!Config::get('url_route_must')) {
            // 路由匹配失败
            $route         = $pathinfo;
            $pathinfo      = explode('/', trim($pathinfo, '/'));
            if (Config::get('app_multi_module')) {
                // 已开启多模块
                if (defined('BIND_MODULE')) {
                    // 存在BIND_MODULE
                    $mca = [
                        'module'     => BIND_MODULE,
                        'controller' => isset($pathinfo[0]) && !empty($pathinfo[0]) ? $pathinfo[0] : Config::get('default_controller'),
                        'action'     => isset($pathinfo[1]) && !empty($pathinfo[1]) ? $pathinfo[1] : Config::get('default_action'),
                        'route'      => count($pathinfo) > 2 ? "/{$pathinfo[0]}/{$pathinfo[1]}" : $route
                    ];
                } else {
                    // 不存在BIND_MODULE
                    $mca = [
                        'module'     => isset($pathinfo[0]) && !empty($pathinfo[0]) ? $pathinfo[0] : Config::get('default_module'),
                        'controller' => isset($pathinfo[1]) && !empty($pathinfo[1]) ? $pathinfo[1] : Config::get('default_controller'),
                        'action'     => isset($pathinfo[2]) && !empty($pathinfo[2]) ? $pathinfo[2] : Config::get('default_action'),
                        'route'      => count($pathinfo) > 3 ? "/{$pathinfo[0]}/{$pathinfo[1]}/{$pathinfo[2]}" : $route
                    ];
                }
                
            } else {
                // 未开启多模块
                $mca = [
                    'module'     => defined('BIND_MODULE') ? BIND_MODULE : '',
                    'controller' => isset($pathinfo[0]) && !empty($pathinfo[0]) ? $pathinfo[0] : Config::get('default_controller'),
                    'action'     => isset($pathinfo[1]) && !empty($pathinfo[1]) ? $pathinfo[1] : Config::get('default_action'),
                    'route'      => $route
                ];
            }
        } else {
            // 抛出路由不存在异常
            throw new RouteException('路由不存在');
        }
        // 判断是否将路由信息存入Request
        if ($store) {
            // 将路由信息存入Request
            $request = Request::instance();
            $request->route($mca['route']);
            $request->module($mca['module']);
            $request->controller($mca['controller']);
            $request->action($mca['action']);
        }
        return $mca;
    }

    /**
     * 路由匹配
     * @param  string $route 待匹配路由
     * @return array
     */
    public static function matchRoute($route, $full = true)
    {
        // 路由参数处理
        $route = ($route != '/') ? rtrim($route, '/') : '/';
        $count = ($route != '/') ? count(explode('/', $route)) - 1 : 0;

        // 获取并遍历路由规则
        $matchRoute    = null;
        $routeRuleList = self::getRouteRule();
        foreach ($routeRuleList as $routeRule => $routeData) {
            // PATH_INFO符合参数个数要求
            if (strpos($route, $routeRule) === 0 && $count >= $routeData['min_count'] && $count <= $routeData['max_count']) {
                $matchRoute = strlen($routeRule) > strlen($matchRoute) ? $routeRule : $matchRoute;
            }
        }

        // 判断是否匹配成功
        if (!is_null($matchRoute)) {
            // 匹配成功
            $routeParams = self::getRouteParams($route, $routeRuleList[$matchRoute]['params'], strlen($matchRoute));
            return [
                'route'  => $matchRoute,
                'params' => $routeParams,
                'mca'    => self::parseModule($routeRuleList[$matchRoute]['mca'])
            ];
        } else {
            // 匹配失败
            return [];
        }
    }

    /**
     * 获取路由规则
     * @return array
     */
    public static function getRouteRule()
    {
        // 检查是否开启路由
        if (!Config::get('url_route_on')) {
            // 未开启路由
            return [];
        }

        // 从缓存中获取路由
        $routeCache = self::getRouteCache();
        if (self::checkRouteCache($routeCache['time'])) {
            // 缓存路由有效
            return self::$routelist = $routeCache['data'];
        }

        // 重新加载分析路由规则
        return self::parseRoute();
    }

    /**
     * 获取缓存路由
     * @return array
     */
    private static function getRouteCache()
    {
        // 检查路由缓存文件是否存在
        $cachePath = RUNTIME_PATH . '~' . self::$routeCacheFile . '.json';
        if (file_exists($cachePath)) {
            // 缓存文件存在
            return [
                'time' => filemtime($cachePath),
                'data' => json_decode(file_get_contents($cachePath), true) ?: []
            ];
        }
        // 默认返回
        return ['time'=>0, 'data'=>[]];
    }

    /**
     * 检查缓存路由有效性
     * @param  integer $cacheTime 缓存时间
     * @return bool
     */
    private static function checkRouteCache($cacheTime)
    {
        // 遍历原始路由规则列表
        $filelist = Config::get('route_config_file');
        foreach ($filelist as $filename) {
            // 检查路由文件是否存在，若存在并判断是否发生修改
            $filepath = DATA_PATH . 'route' . DS . $filename . EXT;
            if (file_exists($filepath) && filemtime($filepath) > $cacheTime) {
                return false;
            }
        }
        return true;
    }

    /**
     * 分析并缓存路由
     * @return array
     */
    private static function parseRoute()
    {
        // 加载路由规则
        $routeRuleList = [];
        $filelist = Config::get('route_config_file');
        foreach ($filelist as $filename) {
            // 检查路由文件是否存在，若存在则进行加载
            $filepath = DATA_PATH . 'route' . DS . $filename . EXT;
            if (file_exists($filepath)) {
                $routeRuleList = array_merge($routeRuleList, __include_file($filepath));
            }
        }
        // 遍历并分析路由规则
        foreach ($routeRuleList as $rule => $mca) {
            $len      = 0;
            $params   = [];
            $rule     = explode(':', str_replace('[', '', $rule));
            $route    = ($rule[0] != '/') ? rtrim($rule[0], '/') : '/';
            $routeLen = ($rule[0] != '/') ? count(explode('/', $route)) - 1 : 0;
            for ($i=1; $i < count($rule); $i++) {
                $paramName    = rtrim(rtrim($rule[$i], '/'), ']');
                $paramRequire = strpos($rule[$i], ']') ? false : true;
                $params[$paramName] = $paramRequire;
                if ($paramRequire) {
                    $len++;
                }
            }
            // 保存路由规则
            self::$routelist[$route] = [
                'mca'       => $mca,
                'params'    => $params,
                'min_count' => $routeLen + $len,
                'max_count' => $routeLen + count($rule) - 1
            ];
        }
        // 缓存路由规则
        self::cacheRoute(self::$routelist);
        return self::$routelist;
    }

    /**
     * 缓存路由信息
     * @param  mixed $data 缓存内容
     * @return bool
     */
    private static function cacheRoute($data)
    {
        // 缓存内容格式转换
        $data = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        // 缓存文件路径
        $cachePath = RUNTIME_PATH . '~' . self::$routeCacheFile . '.json';
        // 存储缓存内容
        return file_put_contents($cachePath, $data);
    }

    /**
     * 获取路由参数
     * @param  string  $pathinfo  PATHINFO
     * @param  array   $paramlist 参数列表
     * @param  integer $routelen  路由长度
     * @return array
     */
    private static function getRouteParams(string $pathinfo, array $paramlist, int $routelen)
    {
        // 参数值下标及内容
        $index = 0;
        $params = substr($pathinfo, $routelen);
        $params = explode('/', trim($params , '/'));
        foreach ($paramlist as $paramName => $isRequire) {
            // 检查参数合法性
            if ($isRequire) {
                // 必传参数
                if (isset($params[$index]) && !empty($params[$index])) {
                    $routeParam[$paramName] = $params[$index];
                } else {
                    throw new HTTPException('404', '缺少路由参数：' . $paramName);
                }
            } else {
                // 非必传参数
                $routeParam[$paramName] = isset($params[$index]) ? $params[$index] : '';
            }
            $index++;
        }

        // 返回结果
        $params = isset($routeParam) ? $routeParam : [];
        $_GET   = array_merge($_GET, $params);
        return $params;
    }

    /**
     * 分析模块信息
     * @param  string $mca 模块/控制器/方法
     * @return array
     */
    private static function parseModule($mca)
    {
        $mca = explode('/', $mca);
        if (count($mca) > 2) {
            // 含有module
            return [
                'module'     => $mca[0],
                'controller' => $mca[1],
                'action'     => $mca[2]
            ];
        } else {
            // 未含module
            $multi  = Config::get('app_multi_module');
            $module = $multi ? (defined('BIND_MODULE') ? BIND_MODULE : Config::get('default_module')) : '';
            return [
                'module'     => $module,
                'controller' => $mca[0],
                'action'     => $mca[1]
            ];
        }
    }
}
