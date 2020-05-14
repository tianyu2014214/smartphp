<?php

namespace smart;

class Log
{
    // 实例对象
    private static $instance;

    // 日志存放路径
    private $logPath = LOGS_PATH;

    // 日志默认所属模块
    private $module = 'default';

    // 日志级别
    private $level = 'info';

    // seaslog 专属常量定义
    private $logConst = [
        'all'       => SEASLOG_ALL,
        'debug'     => SEASLOG_DEBUG,
        'info'      => SEASLOG_INFO,
        'notice'    => SEASLOG_NOTICE,
        'warning'   => SEASLOG_WARNING,
        'error'     => SEASLOG_ERROR,
        'critical'  => SEASLOG_CRITICAL,
        'alert'     => SEASLOG_ALERT,
        'emergency' => SEASLOG_EMERGENCY,
        'asc'       => SEASLOG_DETAIL_ORDER_ASC,
        'desc'      => SEASLOG_DETAIL_ORDER_DESC
    ];

    /**
     * 实例化日志类
     */
    public function __construct()
    {
        // 获取日志存放路径
        $this->logPath = \smart\Config::get('default_log_path');

        // 设置日志存放路径
        \Seaslog::setBasePath($this->logPath);

        // 设置日志所属模块
        \Seaslog::setLogger($this->module);
    }

    /**
     * 获取日志对象
     * @return object
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 写日志
     * @param  string $level   日志级别
     * @param  string $message 日志内容
     * @param  string $module  日志模块
     * @param  array  $content 日志内容参数
     * @return void
     */
    public function write($level, $message, $module = '')
    {
        // 检查 level 是否符合要求
        if (!in_array($level, ['info', 'debug', 'notice', 'warning', 'error', 'critical', 'alert'])) {
            throw new \Exception('日志级别错误');
        }
        // 设置存储 module
        $module = empty($module) ? $this->module : $module;
        // 写入日志
        \Seaslog::$level($message, [], $module);
    }

    /**
     * 读日志
     * @param string  $from    读取源
     * @param string  $keyword 查询关键字
     * @param string  $date    记录日期
     * @param integer $page    查询分页（默认第1页，每页20条）
     * @param string  $order   查询结果排序（默认ASC）
     * @return void
     */
    public function read($from = 'default.all', $keyword = '', $date = '*', $page = '1,20', $order = 'asc')
    {
        // 获取日志模块和日志级别
        $ml     = explode('.', $from);
        $module = isset($ml[0]) ? $ml[0] : null;
        $level  = isset($ml[1]) ? $ml[1] : 'all';

        // 获取查询起始地址和查询条数
        $sl    = explode(',', $page ?: '1,20');
        $start = isset($sl[0]) ? $sl[0] : null;
        $limit = isset($sl[1]) ? $sl[1] : 20;

        // 设置待查询的日志模块
        $this->setModule($module);

        // 判断 level 和 order 是否符合要求
        if (isset($this->logConst[$level]) && isset($this->logConst[$order])) {
            return \Seaslog::analyzerDetail(
                $this->logConst[$level],
                $date,
                $keyword,
                $start,
                $limit,
                $this->logConst[$order]
            );
        }
    }

    /**
     * 日志统计
     * @param  string $level   日志级别
     * @return array|integer
     */
    public function count($level = 'all')
    {
        if (isset($this->logConst[$level])) {
            return \Seaslog::analyzerCount($this->logConst[$level]);
        }
    }

    /**
     * 设置日志存储路径
     * @param  string $logPath 日志路径
     * @return void
     */
    public function setPath($logPath = null)
    {
        if (is_dir($logPath)) {
            $this->logPath = $logPath;
        }
        \Seaslog::setBasePath($this->logPath);
    }

    /**
     * 设置日志模块
     * @param  string $module 日志模块
     * @return void
     */
    public function setModule($module = null)
    {
        if (!empty($module)) {
            $this->module = $module;
        }
        \Seaslog::setLogger($this->module);
    }
}
