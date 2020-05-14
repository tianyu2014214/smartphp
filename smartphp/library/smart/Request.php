<?php

namespace smart;

class Request
{
    // 实例对象
    protected static $instance;

    // HOOK扩展方法
    protected static $hook = [];

    // 客户端IP
    protected $ip;

    // 包含协议的域名
    protected $domain;

    // 模块名
    protected $module;

    // 控制器
    protected $controller;

    // 方法名
    protected $action;

    // 路由
    protected $route;

    // 保存 php://input
    protected $input;

    /**
     * 构造函数
     * @param  array $options 参数
     */
    protected function __construct($options = [])
    {
        // 初始化类属性
        foreach ($options as $name => $item) {
            // 检测类属性是否存在
            if (property_exists($this, $name)) {
                $this->$name = $item;
            }
        }
        // 保存 php://input
        $this->input = file_get_contents('php://input');
    }

    /**
     * 魔术方法
     * @param  string $method 方法名
     * @param  array  $args   参数
     */
    public function __call($method, $args)
    {
        if (array_key_exists($method, self::$hook)) {
            array_unshift($args, $this);
            return call_user_func_array(self::$hook[$method], $args);
        } else {
            throw new \Exception('method not exists:' . __CLASS__ . '->' . $method);
        }
    }

    /**
     * 请求对象初始化
     * @param  array  $options 参数
     * @return Request
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * HOOK 方法注入
     * @param  string $method   方法名
     * @param  mixed  $callback callable
     * @return void
     */
    public static function hook($method, $callback = null)
    {
        if (is_array($method)) {
            self::$hook = array_merge(self::$hook, $method);
        } else {
            self::$hook[$method] = $callback;
        }
    }

    /**
     * 获取请求协议
     * @return string
     */
    public function scheme()
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     * 获取包含协议的域名
     * @return string
     */
    public function domain($domain = null)
    {
        if (!is_null($domain)) {
            $this->domain = $domain;
            return $this;
        } elseif (!$this->domain) {
            $this->domain = $this->scheme() . '://' . $this->host();
        }
        return $this->domain;
    }

    /**
     * 获取当前请求的host
     * @param  bool   $strict 是否仅仅获取HOST
     * @return string
     */
    public function host($strict = false)
    {
        if (isset($_SERVER['HTTP_X_REAL_HOST'])) {
            $host = $_SERVER['HTTP_X_REAL_HOST'];
        } else {
            $host = $_SERVER['HTTP_HOST'];
        }

        return $strict === true && strpos($host, ':') ? strstr($host, ':', true) : $host;
    }

    /**
     * 获取当前请求的port
     * @return integer
     */
    public function port()
    {
        return $_SERVER['SERVER_PORT'];
    }

    /**
     * 当前是否ssl
     * @return bool
     */
    public function isSsl()
    {
        return strtolower($_SERVER['REQUEST_SCHEME']) == 'https';
    }

    /**
     * 获取请求方式
     * @return string
     */
    public function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) ?: 'GET';
    }

    /**
     * 获取当前请求的时间
     * @param  bool   $float 是否使用浮点类型
     * @return string
     */
    public function time($float = false)
    {
        return $float ? $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];
    }

    /**
     * 获取请求的pathinfo
     * @return string
     */
    public function pathinfo()
    {
        return !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';
    }

    /**
     * 获取访问的路由
     * @param  string $route 路由
     * @return string
     */
    public function route($route = null)
    {
        if (is_null($route)) {
            // 获取路由
            if (is_null($this->route)) {
                Route::getModule($this->pathinfo(), true);
            }
            return $this->route;
        } else {
            // 设置路由
            $this->route = $route;
        }
    }

    /**
     * 获取访问的模块名称
     * @param  string $module 模块名
     * @return string
     */
    public function module($module = null)
    {
        if (is_null($module)) {
            // 获取模块
            if (is_null($this->module)) {
                Route::getModule($this->pathinfo(), true);
            }
            return $this->module;
        } else {
            // 设置模块
            $this->module = $module;
        }
    }

    /**
     * 获取访问的控制器名称
     * @param  string $controller 控制器
     * @return string
     */
    public function controller($controller = null)
    {
        if (is_null($controller)) {
            // 获取控制器
            if (is_null($this->controller)) {
                Route::getModule($this->pathinfo(), true);
            }
            return $this->controller;
        } else {
            // 设置控制器
            $this->controller = $controller;
        }
    }

    /**
     * 获取访问的方法名称
     * @param  string $action 方法
     * @return string
     */
    public function action($action = null)
    {
        if (is_null($action)) {
            // 获取方法
            if (is_null($this->action)) {
                Route::getModule($this->pathinfo(), true);
            }
            return $this->action;
        } else {
            // 设置方法
            $this->action = $action;
        }
    }

    /**
     * 获取客户端IP
     * @return string
     */
    public function ip()
    {
        // 直接返回IP
        if (!is_null($this->ip)) {
            return $this->ip;
        }
        // 获取客户端IP
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $this->ip = $ip;
                        break;
                    }
                }
            } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $this->ip = $_SERVER['HTTP_X_REAL_IP'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $this->ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $this->ip = $_SERVER['REMOTE_ADDR'];
            } else {
                $this->ip = '0.0.0.0';
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $this->ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_X_REAL_IP')) {
                $this->ip = getenv('HTTP_X_REAL_IP');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $this->ip = getenv('HTTP_CLIENT_IP');
            } else {
                $this->ip = getenv('REMOTE_ADDR');
            }
        }
        return $this->ip;
    }

    /**
     * 获取客户端浏览器类型
     * @return string
     */
    public function browser()
    {
        // 获取浏览器头，并提取浏览器信息
        $httpUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        if (strpos($httpUserAgent, 'MicroMessenger') !== false) {
            // 微信浏览器
            return 'MicroMessenger';
        } elseif (strpos($httpUserAgent, 'AlipayClient') !== false) {
            // 支付宝浏览器
            return 'AlipayClient';
        }
        return 'Unknow';
    }

    /**
     * 获取HTTP_PREFER
     * @return string
     */
    public function getHttpReferer($onlyRoute = true)
    {
        $scheme      = $this->scheme();
        $httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['HTTP_HOST'];
        if ($onlyRoute) {
            // 仅返回路由
            return parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        } else {
            // 返回整个URL
            return $httpReferer;
        }
    }

    /**
     * 获取上传文件信息
     * @param  string $name 文件名
     * @return array
     */
    public function file($name = '')
    {
        if (isset($_FILES[$name])) {
            // 获取文件信息
            $fileinfo = $_FILES[$name];
            if (is_array($fileinfo['name'])) {
                $filelist = [];
                foreach ($fileinfo as $key => $value) {
                    for ($i=0; $i < count($value); $i++) { 
                        $filelist[$i][$key] = $value[$i];
                    }
                }
                $fileinfo = $filelist;
            }
            // 返回文档操作对象
            if (isset($fileinfo[0]['name'])) {
                // 多文件上传
                foreach ($fileinfo as $file) {
                    $item[] = File::upload($file);
                }
            } else {
                $item = File::upload($fileinfo);
            }
            return $item;
        }
        return false;
    }

    /**
     * 获取指定的请求数据
     * @param  string $name    字段名
     * @param  string $default 默认值
     * @param  string $filter  筛选条件
     * @return mixed
     */
    public function input($name, $default = null, $filter = null)
    {
        // 获取数据
        if (strpos($name, '.') !== false) {
            // 指定请求方式
            list($method, $name) = explode('.', $name);
            if (strtolower($method) == 'get') {
                // GET方式
                $content = isset($_GET[$name]) ? $_GET[$name] : '';
            } else {
                // POST方式
                $content = isset($_POST[$name]) ? $_POST[$name] : '';
            }
        } else {
            $content = isset($_REQUEST[$name]) ? $_REQUEST[$name] : '';
        }
        // 返回结果
        $content = ($content === '' || $content === null) ? $default : $content;
        return empty($filter) || $this->validate($filter, $content) ? $content : $default;
    }

    /**
     * 获取所有请求数据
     * @return array
     */
    public function all()
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * 获取部分数据（限参数）
     * @param  array  $name 字段名组合
     * @return array
     */
    public function only(...$name)
    {
        $data = [];
        foreach ($name as $value) {
            $data[$value] = $this->input($value);
        }
        return $data;
    }

    /**
     * 获取部分数据（除参数）
     * @param  array  $name 字段名组合
     * @return array
     */
    public function except(...$name)
    {
        $data = $this->all();
        foreach ($name as $value) {
            if(isset($data[$value]))
                unset($data[$value]);
        }
        return $data;
    }

    /**
     * 保存本次请求的所有数据
     * @return void
     */
    public function flash()
    {
        Session::set('old_request_data', $this->all());
    }

    /**
     * 保存本次请求的部分数据（限参数）
     * @param  array $name 字段名
     * @return array
     */
    public function flashOnly(...$name)
    {
        $data = $this->all();
        if (Session::has('old_request_data')) {
            Session::delete('old_request_data');
        }
        foreach ($name as $value) {
            Session::push('old_request_data', isset($data[$value]) ? $data[$value] : null);
        }
    }

    /**
     * 保存本次请求的部分数据（除参数）
     * @param  array $name 字段名
     * @return array
     */
    public function flashExcept(...$name)
    {
        $data = $this->all();
        if (Session::has('old_request_data')) {
            Session::delete('old_request_data');
        }
        foreach ($name as $value) {
            if (isset($data[$value])) {
                unset($data[$value]);
            }
        }
        Session::push('old_request_data', $data);
    }

    /**
     * 获取指定的旧数据
     * @param  string $name  字段名
     * @param  string $reset 初始化值
     * @return mixed
     */
    public function old($name, $reset = null)
    {
        $key = 'old_request_data.' . $name;
        return Session::has($key) ? (Session::get($key)) : $reset;
    }

    /**
     * 获取php://input
     * @return mixed
     */
    public function getContent()
    {
        return $this->input;
    }

    /**
     * 判断某请求字段是否存在
     * @param  string  $name 字段名
     * @return boolean
     */
    public function has($name)
    {
        return isset($_POST[$name]) || isset($_GET[$name]);
    }

    /**
     * 判断该字段是否属于post请求
     * @param  string  $name 字段名
     * @return boolean
     */
    public function isPost($name)
    {
        return isset($_POST[$name]);
    }

    /**
     * 判断该字段是否属于get请求
     * @param  string  $name 字段名
     * @return boolean
     */
    public function isGet($name)
    {
        return isset($_GET[$name]);
    }

    /**
     * 校验请求数据是否符合规则
     * @param  string  $rule 校验规则
     * @param  mixed   $data 待校验数据
     * @return bool
     */
    private function validate($rule, $data)
    {
        $rule = ['validate' => $rule];
        $data = ['validate' => $data];
        return Validate::make($rule)->check($data);
    }
}
