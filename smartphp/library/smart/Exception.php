<?php

namespace smart;

class Exception extends \Exception
{
    // 异常页面额外 Debug 数据
    protected $data = [];

    /**
     * 设置异常额外到 Debug 数据
     * @param  string $label 数据分类
     * @param  array  $data  需要显示的数据（关联数组）
     * @return void
     */
    final protected function setData($label, array $data)
    {
        $this->data[$label] = $data;
    }

    /**
     * 获取异常额外 Debug 数据
     * @return array
     */
    final public function getData()
    {
        return $this->data;
    }
}
