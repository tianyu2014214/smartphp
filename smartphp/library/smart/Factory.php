<?php

namespace smart;

class Factory
{
    // 模型实例列表
    private static $modelList = [];

    /**
     * 实例化模型类（单例）
     * @param  string $modelName 模型名称
     * @param  string $modelType 模型类型
     * @return object
     */
    public static function M($modelName, $modelType = 'model', $module = '')
    {
        // 检查模型名
        if (!preg_match('/^\w+$/', $modelName)) {
            throw new \Exception('模型名仅支持字母、数组和下划线的组合');
        }
        // 获取模型完整类名
        $module = $module ?: Request::instance()->module();
        $modelType = $module ? $module . '\\' . $modelType : $modelType;
        $namespace = '\\' . App::$namespace . '\\' . str_replace('.', '\\', $modelType) . '\\';
        $modelClassName = $namespace . ucfirst($modelName);
        if (!class_exists($modelClassName)) {
            throw new \Exception('model not exists:' . $modelClassName);
        }
        // 实例化模型
        if (!isset(self::$modelList[$modelClassName])) {
            // 模型对象不存在，则实例化该模型
            self::$modelList[$modelClassName] = new $modelClassName();
        }
        return self::$modelList[$modelClassName];
    }
}