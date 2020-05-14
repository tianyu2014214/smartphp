<?php

namespace smart\db\driver;

use smart\Config;

class Redis
{
    // 引入redis连接插件
    use \smart\db\traits\RedisConnector;

    // redis命令
    private $command = [
        // 命令 => 是否为写命令
        'del' => true,
        'set' => true,
        'get' => false,
        'ttl' => false,
        'hget' => false,
        'incr' => true,
        'lpop' => true,
        'rpop' => true,
        'spop' => true,
        'decr' => true,
        'ping' => true,
        'info' => false,
        'type' => false,
        'hlen' => false,
        'sadd' => true,
        'keys' => false,
        'mset' => true,
        'exec' => true,
        'hdel' => true,
        'auth' => true,
        'srem' => true,
        'hset' => true,
        'zrem' => true,
        'scan' => false,
        'dump' => false,
        'zset' => true,
        'move' => true,
        'save' => true,
        'lrem' => true,
        'lset' => true,
        'lget' => false,
        'llen' => false,
        'sort' => false,
        'hmget' => false,
        'ltrim' => true,
        'hvals' => false,
        'hkeys' => false,
        'brpop' => true,
        'lpush' => true,
        'rpush' => true,
        'smove' => true,
        'setex' => true,
        'watch' => true,
        'multi' => true,
        'bitop' => true,
        'setnx' => true,
        'zadd'  => true,
        'zrank' => false,
        'sscan' => false,
        'hscan' => false,
        'scard' => false,
        'hmset' => true,
        'zsize' => false,
        'ssize' => false,
        'lsize' => false,
        'zscan' => false,
        'blpop' => true,
        'sdiff' => false,
        'zcard' => false,
        'exists' => false,
        'zrange' => false,
        'lindex' => false,
        'getbit' => false,
        'sunion' => false,
        'sinter' => false,
        'strlen' => false,
        'decrby' => true,
        'object' => false,
        'incrby' => true,
        'zinter' => true,
        'getset' => true,
        'lrange' => true,
        'append' => true,
        'lpushx' => true,
        'zscore' => false,
        'dbsize' => false,
        'zcount' => false,
        'zunion' => true,
        'expire' => true,
        'config' => true,
        'rename' => true,
        'setbit' => true,
        'delete' => true,
        'zincrby' => true,
        'lremove' => true,
        'sremove' => true,
        'linsert' => true,
        'hincrby' => true,
        'flushdb' => true,
        'migrate' => true,
        'hgetall' => false,
        'unwatch' => true,
        'hexists' => false,
        'zdelete' => false,
        'discard' => true,
        'getkeys' => false,
        'persist' => true,
        'setrange' => true,
        'renamenx' => true,
        'getrange' => false,
        'bitcount' => false,
        'smembers' => true,
        'expireat' => true,
        'lastsave' => true,
        'listtrim' => true,
        'flushall' => true,
        'zrevrank' => false,
        'sismember' => false,
        'zrevrange' => false,
        'randomkey' => false,
        'rpoplpush' => true,
        'scontains' => false,
        'lgetrange' => false,
        'renamekey' => true,
        'sdiffstore' => true,
        'settimeout' => true,
        'sgetmembers' => true,
        'sinterstore' => true,
        'srandmember' => false,
        'sunionstore' => true,
        'getmultiple' => false,
        'bgrewriteaof' => true,
        'zrangebyscore' => false,
        'zrevrangebyscore' => false,
        'zremrangebyscore' => true,
        'zdeleterangebyscore' => true,
    ];

    /**
     * 构造函数（读取数据库配置）
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 选择数据库
     * @param  integer $database 数据库
     * @return object
     */
    public function select($database = 0)
    {
        // 判断是否读写分离
        if ($this->config['deploy'] && $this->config['rw_separate']) {
            $this->initConnect(false)->select($database);
        }
        // 主服务器选择数据库
        $this->initConnect(true)->select($database);
        return $this;
    }

    /**
     * 执行写命令
     * @param  string $command 指令
     * @param  array  $params  参数
     * @return bool
     */
    public function execute($command, ...$params)
    {
        // 检查命令是否存在
        $command = strtolower($command);
        if (!isset($this->command[$command])) {
            throw new \Exception('no support redis command:' . $command);
        }
        // 初始化连接，并执行命令
        $this->initConnect($this->command[$command]);
        // 返回结果
        return call_user_func_array([$this->linkID, $command], $params);
    }
}
