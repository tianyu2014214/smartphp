<?php

namespace smart;

class Controller
{
    // Request 实例
    protected $request;

    // 前置操作方法列表
    protected $beforeActionList = [];

    /**
     * 构造函数
     * @param Request $request
     */
    public function __construct(Request $request = null)
    {
        // 初始化Request
        $this->request = is_null($request) ? (Request::instance()) : $request;
        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ? $this->beforeAction($options) : $this->beforeAction($method, $options);
            }
        }
    }

    /**
     * 前置操作
     * @param  string $method  前置操作方法名
     * @param  array  $options 调用参数 ['only'=>[...]] 或者 ['except'=>[...]]
     * @return void
     */
    protected function beforeAction($method, $options)
    {
        if (isset($options['only'])) {
            $options['only'] = is_string($options['only']) ? explode(',', $options['only']) : $options['only'];
            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            $options['except'] = is_string($options['except']) ? explode(',', $options['except']) : $options['except'];
            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }
        call_user_func([$this, $method]);
    }
}
