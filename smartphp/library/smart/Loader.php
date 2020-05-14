<?php

namespace smart;

class Loader
{
    // 类名映射
    protected static $classMap = [];

    // 命名空间别名
    protected static $namespaceAlias = [];

    // PSR-4 命名空间前缀长度映射
    private static $prefixLengthsPsr4 = [];

    // PSR-4 加载目录
    private static $prefixDirsPsr4 = [];

    // PSR-4 加载失败的回退目录
    private static $fallbackDirsPsr4 = [];

    // 自动加载文件
    private static $extraFileList = [];

    /**
     * 自动加载
     * @param  string $class 类名
     * @return bool
     */
    public static function autoload($class)
    {
        // 检测命名空间别名
        if (!empty(self::$namespaceAlias)) {
            $namespace = dirname($class);
            if (isset(self::$namespaceAlias[$namespace])) {
                $original = self::$namespaceAlias[$namespace] . '\\' . basename($class);
                if (class_exists($original)) {
                    return class_alias($original, $class, false);
                }
            }
        }

        // 查找并加载类文件
        if ($file = self::findFile($class)) {
            if (pathinfo($file, PATHINFO_FILENAME) == pathinfo(realpath($file), PATHINFO_FILENAME)) {
                __include_file($file);
                return true;
            }
        }

        return false;
    }

    /**
     * 注册自动加载机制
     * @param  callable $autoload 自动加载处理方法
     * @return void
     */
    public static function register($autoload = null)
    {
        // 注册系统自动加载
        spl_autoload_register($autoload ?: 'smart\Loader::autoload', true, true);

        // Composer 自动加载支持
        if (is_dir(VENDOR_PATH . 'composer')) {
            if (PHP_VERSION_ID >= 50600 && is_file(VENDOR_PATH . 'composer' . DS . 'autoload_static.php')) {
                require VENDOR_PATH . 'composer' . DS . 'autoload_static.php';
                $declareClass  = get_declared_classes();
                $composerClass = array_pop($declareClass);
                foreach (['prefixLengthsPsr4', 'prefixDirsPsr4', 'fallbackDirsPsr4', 'classMap', 'files'] as $attr) {
                    if (property_exists($composerClass, $attr)) {
                        self::${$attr} = $composerClass::${$attr};
                    }
                }
            }
        } else {
            self::registerComposerLoader();
        }

        // 注册命名空间
        self::addNamespace([
            'smart' => CORE_PATH,
        ]);

        // 加载类库映射文件
        if (is_file(RUNTIME_PATH . 'classmap' . EXT)) {
            self::addClassMap(__include_file(RUNTIME_PATH . 'classmap' . EXT));
        }

        // 自动加载 extend 目录
        self::$fallbackDirsPsr4[] = rtrim(EXTEND_PATH, DS);
    }

    /**
     * 注册命名空间
     * @param  string|array $namespace 命名空间
     * @param  string       $path      路径
     * @return void
     */
    public static function addNamespace($namespace, $path = '')
    {
        if (is_array($namespace)) {
            foreach ($namespace as $prefix => $path) {
                self::addPsr4($prefix . '\\', rtrim($path, DS), true);
            }
        } else {
            self::addPsr4($namespace . '\\', rtrim($path, DS), true);
        }
    }

    /**
     * 注册命名空间别名
     * @param  array|string $namespace 命名空间
     * @param  string       $original  源文件
     * @return void
     */
    public static function addNamespaceAlias($namespace, $original = '')
    {
        if (is_array($namespace)) {
            self::$namespaceAlias = array_merge(self::$namespaceAlias, $namespace);
        } else {
            self::$namespaceAlias[$namespace] = $original;
        }
    }

    /**
     * 注册 classmap
     * @param  string|array $class 类名
     * @param  string       $map   映射
     * @return void
     */
    public static function addClassMap($class, $map = '')
    {
        if (is_array($class)) {
            self::$classMap = array_merge(self::$classMap, $class);
        } else {
            self::$classMap[$class] = $map;
        }
    }

    /**
     * 加载额外文件
     * @return void
     */
    public static function loadExtraFile()
    {
        // 加载扩展函数文件
        $extraFileList = Config::get('extra_file_list');
        foreach ($extraFileList as $filepath) {
            // 检查文件是否存在
            if (!in_array($filepath, self::$extraFileList) && file_exists($filepath)) {
                self::$extraFileList[] = $filepath;
                __include_file($filepath);
            }
        }
    }

    /**
     * 注册 composer 自动加载
     * @return void
     */
    private static function registerComposerLoader()
    {
        if (is_file(VENDOR_PATH . 'composer/autoload_psr4.php')) {
            $map = require VENDOR_PATH . 'composer/autoload_psr4.php';
            foreach ($map as $namespace => $path) {
                self::addPsr4($namespace, $path);
            }
        }

        if (is_file(VENDOR_PATH . 'composer/autoload_classmap.php')) {
            $classMap = require VENDOR_PATH . 'composer/autoload_classmap.php';
            if ($classMap) {
                self::addClassMap($classMap);
            }
        }
    }

    /**
     * 添加 PSR-4 空间
     * @param  array|string $prefix  空间前缀
     * @param  string       $path    路径
     * @param  bool         $prepend 预先设置的优先级更高
     * @return void
     */
    private static function addPsr4($prefix, $path, $prepend = false)
    {
        if (!$prefix) {
            // 将路径注册到 extend 命名
            self::$fallbackDirsPsr4 = $prepend ? 
            // prepend namespace
            array_merge((array) $path, self::$fallbackDirsPsr4) :
            // append namespace 
            array_merge(self::$fallbackDirsPsr4, (array) $path);
        } elseif (!isset(self::$prefixDirsPsr4[$prefix])) {
            // 将路径注册到新的命名空间
            $length = strlen($prefix);
            if ($prefix[$length - 1] !== '\\') {
                throw new \InvalidArgumentException('A non-empty PSR-4 prefix must end with a namespace separator.');
            }
            self::$prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            self::$prefixDirsPsr4[$prefix] = (array) $path;
        } else {
            // 将路径注册到已有的命名空间
            self::$prefixLengthsPsr4[$prefix] = $prepend ? 
            // prepend namespace
            array_merge((array) $path, self::$prefixDirsPsr4[$prefix]) : 
            // append namespace
            array_merge(self::$prefixDirsPsr4[$prefix], (array) $path);
        }
    }

    /**
     * 查找文件
     * @param  string $class 类名
     * @return bool|string
     */
    private static function findFile($class)
    {
        // 类库映射
        if (!empty(self::$classMap[$class])) {
            return self::$classMap[$class];
        }

        // 查找PSR-4
        $logicalPathPsr4 = strtr($class, '\\', DS) . EXT;
        $first           = $class[0];

        if (isset(self::$prefixLengthsPsr4[$first])) {
            foreach (self::$prefixLengthsPsr4[$first] as $prefix => $length) {
                if (strpos($class, $prefix) === 0) {
                    foreach (self::$prefixDirsPsr4[$prefix] as $dir) {
                        if (is_file($file = $dir . DS . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }

        // 查找 PSR-4 fallback dirs
        foreach (self::$fallbackDirsPsr4 as $dir) {
            if (is_file($file = $dir . DS . $logicalPathPsr4)) {
                return $file;
            }
        }

        // 找不到文件则设置映射为 false 并返回
        return self::$classMap[$class] = false;
    }
}
