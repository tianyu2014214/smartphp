<?php

namespace smart\db;

use smart\Config;
use smart\exception\PDOException;

/**
 * 关系型数据库驱动
 */
class Driver extends Builder
{
    // 引入数据库连接插件
    use traits\PDOConnector;

    // 事务指令数目
    protected $transTimes = 0;

    // 是否查询加锁
    protected $locking = false;

    /**
     * 构造函数（读取数据库配置）
     * @param array $config 数据库配置
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 指定当前操作的数据表
     * @param  mixed  $table  表名
     * @param  bool   $strict 是否强制别名
     * @return object
     */
    public function table($table, $strict = false)
    {
        // 将表名转换为数组格式
        if (is_string($table)) {
            $table = explode(',', $table);
        }
        // 遍历格式化后的数据表
        $tablelist = [];
        foreach ($table as $key => $value) {
            // 检查是否存在别名
            if (is_numeric($key)) {
                $value = explode(' ', trim($value, ' '), 2);
                $name  = $value[0];
                if (isset($value[1])) {
                    $alias = $value[1];
                } else {
                    $alias = ($strict && strpos($name, '.') !== false) ? explode('.', $name)[1] : '';
                }
            } else {
                $name  = $key;
                $alias = $value;
            }
            // 分析表名
            $tablelist[] = $this->parseKey($name, $alias);
        }
        // 保存表信息
        $this->options['table'] = implode(', ', $tablelist);
        return $this;
    }

    /**
     * 查询SQL组装 join
     * @param  mixed  $join      关联表名
     * @param  mixed  $condition 关联条件
     * @param  string $type      关联类型
     * @return object
     */
    public function join($join, $condition = null, $type = 'inner')
    {
        // 检查关联类型的合法性
        if (!in_array(strtolower($type), ['inner', 'left', 'right', 'full'])) {
            throw new \Exception('[database][join]参数{type}仅支持inner/left/right/full');
        }

        // 设置关联查询相关信息
        if (empty($condition)) {
            // 批量设置
            if (!is_array($join)) {
                throw new \Exception('[database][join]参数{condition}为空时， 参数{join}须为二维数组');
            }
            // 递归调用join
            foreach ($join as $key => $value) {
                // 检查是否满足递归要求
                if (is_array($value) && count($value) >= 2) {
                    $this->join($value[0], $value[1], isset($value[2]) ? $value[2] : $type);
                }
            }
        } else {
            // 单个设置
            $table     = $this->parseJoinTable($join);
            $condition = $this->parseJoinCondition($condition);
            $joinstr   = strapend(strtoupper($type), ' ') . 'JOIN ' . $table . ' ON ' . $condition;
            $this->options['join'] = isset($this->options['join']) ? $this->options['join'].' '.$joinstr : $joinstr;
        }
        return $this;
    }

    /**
     * 查询SQL组装 field
     * @param  string $field 查询字段
     * @return object
     */
    public function field($field = '*')
    {
        // 将字段转换为数组格式
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        // 遍历格式化后的字段
        $fieldlist = [];
        foreach ($field as $key => $value) {
            // 检查字段值是否为*
            if ($value == '*') {
                $fieldlist[] = '*';
                continue;
            } elseif (preg_match('/^[a-zA-Z]+[a-zA-Z0-9_]*\(.*\)/', $value)) {
                $fieldlist[] = $value;
                continue;
            }
            // 分析查询字段
            @list($fieldname, $alias) = is_numeric($key) ? explode(' ', trim($value, ' '), 2) : [$key, $value];
            $fieldlist[] = preg_match('/^[a-z]+\([\w\.\*]+\)$/', $fieldname) ? $this->parseAggregate($fieldname, $alias) : $this->parseKey($fieldname, $alias);
        }
        // 保存字段信息
        $this->options['field'] = implode(',', $fieldlist);
        return $this;
    }

    /**
     * 查询数据去重
     * @return object
     */
    public function distinct()
    {
        $this->options['field'] = ' DISTINCT ' . $this->options['field'];

        return $this;
    }

    /**
     * 指定查询条件（默认AND）
     * @param  mixed  $field     查询字段
     * @param  string $op        运算符            
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function where($field, $op = null, $condition = null, $logic = 'AND')
    {
        $logic    = strapend(trim($logic, ' '), ' ');
        $wherestr = $this->parseWhere($field, $op, $condition);
        if ($this->where) {
            $this->where            = $this->where.$logic.$wherestr;
            $this->options['where'] = $this->options['where'].$logic.$wherestr;
        } else {
            $this->where            = $wherestr;
            $this->options['where'] = ' WHERE ' . $wherestr;
        }
        return $this;
    }

    /**
     * 指定查询条件 OR
     * @param  mixed  $field     查询字段
     * @param  string $op        运算符            
     * @param  mixed  $condition 查询条件
     * @return object
     */
    public function whereOr($field, $op = null, $condition = null)
    {
        $this->where($field, $op, $condition, 'OR');

        return $this;
    }

    /**
     * 指定查询条件 IS NULL
     * @param  string $field 查询字段
     * @param  string $logic 逻辑符
     * @return object
     */
    public function whereNull($field, $logic = 'AND')
    {
        $this->where($field, 'IS NULL', null, $logic);

        return $this;
    }

    /**
     * 指定查询条件 IS NOT NULL
     * @param  string $field 查询字段
     * @param  string $logic 逻辑符
     * @return object
     */
    public function whereNotNull($field, $logic = 'AND')
    {
        $this->where($field, 'IS NOT NULL', null, $logic);

        return $this;
    }

    /**
     * 指定查询条件 IN
     * @param  string $field     查询字段
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function whereIn($field, $condition, $logic = 'AND')
    {
        $this->where($field, 'IN', $condition, $logic);

        return $this;
    }

    /**
     * 指定查询条件 NOT IN
     * @param  string $field     查询字段
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function whereNotIn($field, $condition, $logic = 'AND')
    {
        $this->where($field, 'NOT IN', $condition, $logic);

        return $this;
    }

    /**
     * 指定查询条件 LIKE
     * @param  string $field     查询字段
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function whereLike($field, $condition, $logic = 'AND')
    {
        $this->where($field, 'LIKE', $condition, $logic);

        return $this;
    }

    /**
     * 指定查询条件 NOT LIKE
     * @param  string $field     查询字段
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function whereNotLike($field, $condition, $logic = 'AND')
    {
        $this->where($field, 'NOT LIKE', $condition, $logic);

        return $this;
    }

    /**
     * 指定查询条件 BETWEEN
     * @param  string $field     查询字段
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function whereBetween($field, $condition, $logic = 'AND')
    {
        $this->where($field, 'BETWEEN', $condition, $logic);

        return $this;
    }

    /**
     * 指定查询条件 NOT BETWEEN
     * @param  string $field     查询字段
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function whereNotBetween($field, $condition, $logic = 'AND')
    {
        $this->where($field, 'NOT BETWEEN', $condition, $logic);

        return $this;
    }

    /**
     * 指定查询条件 EXISTS
     * @param  closure $condition 条件
     * @param  string  $logic     逻辑符
     * @return object
     */
    public function whereExists($condition, $logic = 'AND')
    {
        $this->where($condition, 'EXISTS', null, $logic);

        return $this;
    }

    /**
     * 指定查询条件 NOT EXISTS
     * @param  closure $condition 条件
     * @param  string  $logic     逻辑符
     * @return object
     */
    public function whereNotExists($condition, $logic = 'AND')
    {
        $this->where($condition, 'NOT EXISTS', null, $logic);

        return $this;
    }

    /**
     * 指定原生查询条件
     * @param  string $where 查询条件
     * @param  string $logic 查询逻辑
     * @return object
     */
    public function whereRaw($where, $logic = 'AND')
    {
        $this->where($where, null, null, $logic);

        return $this;
    }

    /**
     * 指定动态查询条件（默认AND）
     * @param  mixed  $field     查询字段
     * @param  string $op        运算符            
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function whereIf($field, $op = null, $condition = null, $logic = 'AND')
    {
        if (is_array($field)) {
            // 数组模式
            foreach ($field as $value) {
                // 检查子项是否为数组
                if (!is_array($value)) continue;
                // 判断条件是否符合要求
                if (count($value) > 2) {
                    $logic = isset($value[3]) ? $value[3] : 'AND';
                    if ($value[2] !== null && $value[2] !== '') {
                        $this->where($value[0], $value[1], $value[2], $logic);
                    }
                } elseif (count($value) >= 2) {
                    $logic = isset($value[3]) ? $value[3] : 'AND';
                    if ($value[1] == 'IS NULL' || $value[1] == 'IS NOT NULL') {
                        $this->where($value[0], $value[1], null, $logic);
                    }
                }
            }
        } elseif (is_string($field)) {
            // 字符串模式
            if ($condition !== null && $condition !== '') {
                $this->where($field, $op, $condition, $logic);
            }
        } else {
            // 非法数据类型
            throw new \Exception('[database][whereIf]参数{field}仅支持二维数组和字符串格式');
        }
        return $this;
    }

    /**
     * 查询SQL组装 group
     * @param  string $field 分组字段
     * @return object
     */
    public function group($field)
    {
        // 检查参数格式
        if (!is_string($field) || empty($field)) {
            throw new \Exception('[database][group]参数{field}仅支持非空字符串');
        }
        // 设置分组查询
        $this->options['group'] = ' GROUP BY ' . $this->parseKey($field);
        return $this;
    }

    /**
     * 设置查询分组条件
     * @param  mixed  $field     查询字段
     * @param  string $op        运算符            
     * @param  mixed  $condition 查询条件
     * @param  string $logic     逻辑符
     * @return object
     */
    public function having($field, $op = null, $condition = null, $logic = 'AND')
    {
        $havingstr = $this->parseWhere($field, $op, $condition);

        $this->options['having'] = isset($this->options['having']) ? $this->options['having'].strapend(trim($logic, ' '), ' ').$havingstr : ' HAVING ' . $havingstr;

        return $this;
    }

    /**
     * 设置查询排序方式
     * @param  array  $data 排序数据
     * @return object
     */
    public function order($data)
    {
        // 字符串模式
        if (is_string($data) && !empty($data)) {
            $this->options['order'] = ' ORDER BY ' . $data;
            return $this;
        }
        // 检查参数格式
        if (!is_array($data) || empty($data)) {
            throw new \Exception('[database][order]参数{data}仅支持非空数组和字符串');
        }
        // 遍历查询排序字段
        foreach ($data as $key => $value) {
            $key  = $this->parseKey($key);
            $value   = strtoupper($value);
            $order[] = "{$key} {$value}";
        }
        $this->options['order'] = ' ORDER BY ' . implode(',', $order);
        return $this;
    }

    /**
     * 指定查询数量
     * @param  mixed  $offset 起始位置
     * @param  mixed  $length 查询数量
     * @return object
     */
    public function limit($offset, $length = null)
    {
        if (is_null($length) && strpos($offset, ',')) {
            list($offset, $length) = explode(',', $offset);
        }
        $this->options['limit'] = ' LIMIT ' . intval($offset) . ($length ? ',' . intval($length) : '');
        return $this;
    }

    /**
     * 指定分页
     * @param  mixed  $page 页码
     * @param  mixed  $rows 每页数量
     * @return object
     */
    public function page($page, $rows)
    {
        $offset = intval(($page - 1) * $rows);

        $this->limit($offset, $rows);

        return $this;
    }

    /**
     * 新增数据
     * @param  array $data 待存数据
     * @return mixed
     */
    public function insert($data)
    {
        // 检查参数的合法性
        if (!is_array($data) || empty($data)) {
            throw new \Exception('[database][insert]参数{data}仅支持非空数组');
        }
        // 组装SQL语句
        if (count($data) == count($data, true)) {
            // 单次插入（一维数组）
            foreach ($data as $key => $value) {
                $fields[] = $this->parseKey($key);
                $values[] = $this->escapeString($value);
            }
            $fields = implode(',', $fields);
            $values = implode(',', $values);
            $sql    = "INSERT INTO {$this->options['table']}({$fields}) VALUES({$values});";
        } else {
            // 批量插入（二维数组）
            foreach ($data as $subdata) {
                $subdata  = array_values($subdata);
                $subdata  = implode(',', $this->escapeString($subdata));
                $values[] = "({$subdata})";
            }
            $fields = $this->parseFields(array_keys($data[0]));
            $values = implode(',', $values);
            $sql    = "INSERT INTO {$this->options['table']}({$fields}) VALUES{$values};";
        }
        // 执行SQL
        $sql = str_replace('\'\'', 'NULL', $sql);
        return $this->execute($sql);
    }

    /**
     * 新增数据并获取自增ID值
     * @param  array $data 待存数据
     * @return mixed
     */
    public function insertGetId($data)
    {
        // 检查参数合法性
        if (!is_array($data) || count($data) != count($data, 1)) {
            throw new \Exception('[database][insertGetId]参数{data}仅支持一维数组');
        }
        // 存储数据，返回结果
        return $this->insert($data) ? $this->getLastInsertId() : false;
    }

    /**
     * 删除数据
     * @return bool
     */
    public function delete()
    {
        $where = $this->options['where'] ?: ' WHERE 1';

        $sql   = "DELETE FROM {$this->options['table']}{$where};";

        return $this->execute($sql);
    }

    /**
     * 清空数据表
     * @return bool
     */
    public function truncate()
    {
        $sql = "TRUNCATE {$this->options['table']}";

        return $this->execute($sql);
    }

    /**
     * 更新数据
     * @param  array $data 待更新数据
     * @return bool
     */
    public function update(array $data)
    {
        foreach ($data as $key => $value) {
            $keys   = $this->parseKey($key);
            $values = $this->escapeString($value);
            $values = ($values === '\'\'' || $values === null) ? 'NULL' : $values;
            $data[] = "{$keys}={$values}";
            unset($data[$key]);
        }
        $data  = implode(',', $data);
        $where = $this->options['where'] ?: ' WHERE 1';
        $sql   = "UPDATE {$this->options['table']} SET {$data}{$where};";
        return $this->execute($sql);
    }

    /**
     * 将某个字段值进行自增
     * @param  string  $field  字段
     * @param  integer $offset 自增值
     * @return bool
     */
    public function increment($field, $offset = 1)
    {
        // 检查参数的有效性
        if (!is_int($offset) || $offset < 1) {
            throw new \Exception('[database][increment]参数{offset}仅接受正整数');
        }
        // 生成SQL
        $field = $this->parseKey($field);
        $field = "{$field} = {$field} + {$offset}";
        $where = $this->options['where'] ?: ' WHERE 1';
        $sql   = "UPDATE {$this->options['table']} SET {$field}{$where};";
        $sql   = strpos($sql, 'WHERE') ? $sql : rtrim($sql, ';') . ' WHERE 1;';
        return $this->execute($sql);
    }

    /**
     * 将某个字段值进行自减
     * @param  string  $field  字段
     * @param  integer $offset 自减值
     * @return bool
     */
    public function decrement($field, $offset = 1)
    {
        // 检查参数的有效性
        if (!is_int($offset) || $offset < 1) {
            throw new \Exception('[database][decrement]参数{offset}仅接受正整数');
        }
        // 生成SQL
        $field = $this->parseKey($field);
        $field = "{$field} = {$field} - {$offset}";
        $where = $this->options['where'] ?: ' WHERE 1';
        $sql   = "UPDATE {$this->options['table']} SET {$field}{$where};";
        return $this->execute($sql);
    }

    /**
     * 查询数据集
     * @return array
     */
    public function select()
    {
        $sql = $this->buildQuerySql();

        return $this->query($sql);
    }

    /**
     * 查询一条数据
     * @return mixed
     */
    public function find()
    {
        $sql = $this->buildQuerySql();

        return $this->query($sql, [], 'row');
    }

    /**
     * 查询一条数据中的单个字段
     * @return mixed
     */
    public function column()
    {
        $sql = $this->buildQuerySql();

        return $this->query($sql, [], 'column');
    }

    /**
     * 获取指定字段最大值
     * @param  string  $field 字段名
     * @return mixed
     */
    public function max($field)
    {
        $this->field("max({$field})");

        return $this->column();
    }

    /**
     * 获取指定字段最小值
     * @param  string  $field 字段名
     * @return mixed
     */
    public function min($field)
    {
        $this->field("min({$field})");

        return $this->column();
    }

    /**
     * 获取指定字段平均值
     * @param  string  $field 字段名
     * @return mixed
     */
    public function avg($field)
    {
        $this->field("avg({$field})");

        return $this->column();
    }

    /**
     * 获取指定字段和
     * @param  string  $field 字段名
     * @return mixed
     */
    public function sum($field)
    {
        $this->field("sum({$field})");

        return $this->column();
    }

    /**
     * 统计符合条件数据总数
     * @param  string  $field 字段名
     * @return mixed
     */
    public function count($field = '*')
    {
        $this->field("count({$field})");

        return $this->column();
    }

    /**
     * 执行更新类SQL
     * @param  string $sql
     * @param  array  $bind
     * @return mixed
     */
    public function execute(string $sql, array $bind = [])
    {
        // 检查是否仅获取SQL
        if ($this->fetchSql) {
            $this->clear();
            return $sql;
        }
        // 执行SQL
        try {
            $this->initConnect(true);
            $this->sql      = $sql;
            $this->clear();
            return $this->linkID->exec($sql);
        } catch (\PDOException $e) {
            throw new PDOException($e, $this->config, $sql, $e->getCode());
        }
    }

    /**
     * 执行查询类SQL
     * @param  string $sql
     * @param  array  $bind
     * @param  string $type
     * @return mixed
     */
    public function query(string $sql, array $bind = [], string $type = 'all')
    {
        // 检查是否仅获取SQL
        if ($this->fetchSql) {
            $this->clear();
            return $sql;
        }
        // 执行SQL
        try {
            // 执行查询操作
            $this->clear();
            $this->initConnect(false);
            $this->sql = $sql;
            $query = $this->linkID->query($sql);
            // 获取结果集
            switch ($type) {
                // 获取行数据
                case 'row':
                    $result = $query->fetch(\PDO::FETCH_ASSOC);
                    break;
                // 获取列数据
                case 'column':
                    $result = $query->fetchColumn();
                    $result = !is_bool($result) ? $result : '';
                    break;
                // 获取全部数据
                default:
                    $result = $query->fetchAll(\PDO::FETCH_ASSOC);
                    break;
            }
            // 返回结果
            $query->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            throw new PDOException($e, $this->config, $sql, $e->getCode());
        }
    }

    /**
     * 开启事务
     * @return void
     */
    public function startTrans()
    {
        $this->initConnect(true);
        
        if ($this->transTimes == 0) {
            $this->transTimes = 1;
            $this->linkID->beginTransaction();
        }
        
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit()
    {
        $this->initConnect(true);

        if ($this->transTimes > 0) {
            $this->transTimes = 0;
            return $this->linkID->commit();
        }

        return false;
    }

    /**
     * 事务回滚
     * @return bool
     */
    public function rollback()
    {
        $this->initConnect(true);

        if ($this->transTimes == 1) {
            $this->transTimes = 0;
            return $this->linkID->rollback();
        }

        return false;
    }

    /**
     * 是否仅返回SQL
     * @param  boolean $fetch 是否返回sql
     * @return $this
     */
    public function fetchSql($fetch = true)
    {
        $this->fetchSql = $fetch;

        return $this;
    }

    /**
     * 获取前一次执行的SQL
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * 查询锁表设置
     * @param  bool   $withlock 是否加锁
     * @return object
     */
    public function lock($withlock = true)
    {
        $this->locking = $withlock;

        return $this;
    }

    /**
     * 获取自增ID
     * @return integer
     */
    private function getLastInsertId()
    {
        return $this->linkID->lastInsertId();
    }

    /**
     * 清除标记
     * @return void
     */
    private function clear()
    {
        $this->where    = null;
        $this->locking  = false;
        $this->fetchSql = false;
        $this->options  = [
            'table'  => null,
            'field'  => null,
            'where'  => null,
            'join'   => null,
            'group'  => null,
            'order'  => null,
            'having' => null,
            'limit'  => null,
        ];
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->linkID    = null;
        $this->linkRead  = null;
        $this->linkWrite = null;
        $this->links     = [];
    }
}
