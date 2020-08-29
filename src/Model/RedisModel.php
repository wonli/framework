<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Model;

use cross\exception\CoreException;
use Cross\Cache\Driver\RedisDriver;
use Cross\Core\Loader;
use Redis;

/**
 * @author wonli <wonli@live.com>
 * Class SQLModel
 * @package Cross\Model
 *
 * @method static isConnected()
 * @method static getHost()
 * @method static getPort()
 * @method static getDbNum()
 * @method static getTimeout()
 * @method static getReadTimeout()
 * @method static getPersistentID()
 * @method static getAuth()
 * @method static swapdb(int $db1, int $db2)
 * @method static setOption($option, $value)
 * @method static getOption($option)
 * @method static ping($message)
 * @method static echo ($message)
 * @method static get($key)
 * @method static set($key, $value, $timeout = null)
 * @method static setex($key, $ttl, $value)
 * @method static psetex($key, $ttl, $value)
 * @method static setnx($key, $value)
 * @method static del($key1, ...$otherKeys)
 * @method static delete($key1, $key2 = null, $key3 = null)
 * @method static subscribe($channels, $callback)
 * @method static pubsub($keyword, $argument)
 * @method static unsubscribe($channels = null)
 * @method static exists($key)
 * @method static incr($key)
 * @method static incrByFloat($key, $increment)
 * @method static incrBy($key, $value)
 * @method static decr($key)
 * @method static decrBy($key, $value)
 * @method static lPush($key, ...$value1)
 * @method static rPush($key, ...$value1)
 * @method static lPushx($key, $value)
 * @method static lPop($key)
 * @method static rPop($key)
 * @method static blPop($keys, $timeout)
 * @method static brPop($keys, $timeout)
 * @method static lLen($key)
 * @method static lSize($key)
 * @method static lIndex($key, $index)
 * @method static lGet($key, $index)
 * @method static lSet($key, $index, $value)
 * @method static lRange($key, $start, $end)
 * @method static lGetRange($key, $start, $end)
 * @method static lTrim($key, $start, $stop)
 * @method static listTrim($key, $start, $stop)
 * @method static lRem($key, $value, $count)
 * @method static lRemove($key, $value, $count)
 * @method static lInsert($key, $position, $pivot, $value)
 * @method static sAdd($key, ...$value1)
 * @method static sRem($key, ...$member1)
 * @method static sRemove($key, ...$member1)
 * @method static sMove($srcKey, $dstKey, $member)
 * @method static sIsMember($key, $value)
 * @method static sContains($key, $value)
 * @method static sCard($key)
 * @method static sPop($key, $count = 1)
 * @method static sRandMember($key, $count = 1)
 * @method static sInter($key1, ...$otherKeys)
 * @method static sInterStore($dstKey, $key1, ...$otherKeys)
 * @method static sUnion($key1, ...$otherKeys)
 * @method static sUnionStore($dstKey, $key1, ...$otherKeys)
 * @method static sDiff($key1, ...$otherKeys)
 * @method static sDiffStore($dstKey, $key1, ...$otherKeys)
 * @method static sMembers($key)
 * @method static sGetMembers($key)
 * @method static sScan($key, &$iterator, $pattern = null, $count = 0)
 * @method static getSet($key, $value)
 * @method static randomKey()
 * @method static select($dbIndex)
 * @method static move($key, $dbIndex)
 * @method static rename($srcKey, $dstKey)
 * @method static renameKey($srcKey, $dstKey)
 * @method static renameNx($srcKey, $dstKey)
 * @method static expire($key, $ttl)
 * @method static pExpire($key, $ttl)
 * @method static setTimeout($key, $ttl)
 * @method static expireAt($key, $timestamp)
 * @method static pExpireAt($key, $timestamp)
 * @method static keys($pattern)
 * @method static getKeys($pattern)
 * @method static dbSize()
 * @method static bgrewriteaof()
 * @method static slaveof($host = '127.0.0.1', $port = 6379)
 * @method static slowLog(string $operation, int $length = null)
 * @method static object($string = '', $key = '')
 * @method static save()
 * @method static bgsave()
 * @method static lastSave()
 * @method static wait($numSlaves, $timeout)
 * @method static type($key)
 * @method static append($key, $value)
 * @method static getRange($key, $start, $end)
 * @method static substr($key, $start, $end)
 * @method static setRange($key, $offset, $value)
 * @method static strlen($key)
 * @method static bitpos($key, $bit, $start = 0, $end = null)
 * @method static getBit($key, $offset)
 * @method static setBit($key, $offset, $value)
 * @method static bitCount($key)
 * @method static bitOp($operation, $retKey, $key1, ...$otherKeys)
 * @method static flushDB()
 * @method static flushAll()
 * @method static sort($key, $option = null)
 * @method static info($option = null)
 * @method static resetStat()
 * @method static ttl($key)
 * @method static pttl($key)
 * @method static persist($key)
 * @method static mset(array $array)
 * @method static getMultiple(array $keys)
 * @method static mget(array $array)
 * @method static msetnx(array $array)
 * @method static rpoplpush($srcKey, $dstKey)
 * @method static brpoplpush($srcKey, $dstKey, $timeout)
 * @method static zAdd($key, $options, $score1, $value1 = null, $score2 = null, $value2 = null, $scoreN = null, $valueN = null)
 * @method static zRange($key, $start, $end, $withscores = null)
 * @method static zRem($key, $member1, ...$otherMembers)
 * @method static zDelete($key, $member1, ...$otherMembers)
 * @method static zRevRange($key, $start, $end, $withscore = null)
 * @method static zRangeByScore($key, $start, $end, array $options = array())
 * @method static zRevRangeByScore($key, $start, $end, array $options = array())
 * @method static zRangeByLex($key, $min, $max, $offset = null, $limit = null)
 * @method static zRevRangeByLex($key, $min, $max, $offset = null, $limit = null)
 * @method static zCount($key, $start, $end)
 * @method static zRemRangeByScore($key, $start, $end)
 * @method static zDeleteRangeByScore($key, $start, $end)
 * @method static zRemRangeByRank($key, $start, $end)
 * @method static zDeleteRangeByRank($key, $start, $end)
 * @method static zCard($key)
 * @method static zSize($key)
 * @method static zScore($key, $member)
 * @method static zRank($key, $member)
 * @method static zRevRank($key, $member)
 * @method static zIncrBy($key, $value, $member)
 * @method static zUnionStore($output, $zSetKeys, array $weights = null, $aggregateFunction = 'SUM')
 * @method static zUnion($Output, $ZSetKeys, array $Weights = null, $aggregateFunction = 'SUM')
 * @method static zInterStore($output, $zSetKeys, array $weights = null, $aggregateFunction = 'SUM')
 * @method static zInter($Output, $ZSetKeys, array $Weights = null, $aggregateFunction = 'SUM')
 * @method static zScan($key, &$iterator, $pattern = null, $count = 0)
 * @method static bzPopMax($key1, $key2, $timeout)
 * @method static bzPopMin($key1, $key2, $timeout)
 * @method static zPopMax($key, $count = 1)
 * @method static zPopMin($key, $count = 1)
 * @method static hSet($key, $hashKey, $value)
 * @method static hSetNx($key, $hashKey, $value)
 * @method static hGet($key, $hashKey)
 * @method static hLen($key)
 * @method static hDel($key, $hashKey1, ...$otherHashKeys)
 * @method static hKeys($key)
 * @method static hVals($key)
 * @method static hGetAll($key)
 * @method static hExists($key, $hashKey)
 * @method static hIncrBy($key, $hashKey, $value)
 * @method static hIncrByFloat($key, $field, $increment)
 * @method static hMSet($key, $hashKeys)
 * @method static hMGet($key, $hashKeys)
 * @method static hScan($key, &$iterator, $pattern = null, $count = 0)
 * @method static hStrLen(string $key, string $field)
 * @method static geoadd($key, $longitude, $latitude, $member)
 * @method static geohash($key, ...$member)
 * @method static geopos(string $key, string $member)
 * @method static geodist($key, $member1, $member2, $unit = null)
 * @method static georadius($key, $longitude, $latitude, $radius, $unit, array $options = null)
 * @method static georadiusbymember($key, $member, $radius, $units, array $options = null)
 * @method static config($operation, $key, $value)
 * @method static eval($script, $args = array(), $numKeys = 0)
 * @method static evaluate($script, $args = array(), $numKeys = 0)
 * @method static evalSha($scriptSha, $args = array(), $numKeys = 0)
 * @method static evaluateSha($scriptSha, $args = array(), $numKeys = 0)
 * @method static script($command, $script)
 * @method static getLastError()
 * @method static clearLastError()
 * @method static client($command, $value = '')
 * @method static dump($key)
 * @method static restore($key, $ttl, $value)
 * @method static migrate($host, $port, $key, $db, $timeout, $copy = false, $replace = false)
 * @method static time()
 * @method static scan(&$iterator, $pattern = null, $count = 0)
 * @method static pfAdd($key, array $elements)
 * @method static pfCount($key)
 * @method static pfMerge($destKey, array $sourceKeys)
 * @method static rawCommand($command, $arguments)
 * @method static getMode()
 * @method static xAck($stream, $group, $messages)
 * @method static xAdd($key, $id, $messages, $maxLen = 0, $isApproximate = false)
 * @method static xClaim($key, $group, $consumer, $minIdleTime, $ids, $options = [])
 * @method static xDel($key, $ids)
 * @method static xGroup($operation, $key, $group, $msgId = '', $mkStream = false)
 * @method static xInfo($operation, $stream, $group)
 * @method static xLen($stream)
 * @method static xPending($stream, $group, $start = null, $end = null, $count = null, $consumer = null)
 * @method static xRange($stream, $start, $end, $count = null)
 * @method static xRead($streams, $count = null, $block = null)
 * @method static xReadGroup($group, $consumer, $streams, $count = null, $block = null)
 * @method static xRevRange($stream, $end, $start, $count = null)
 * @method static xTrim($stream, $maxLen, $isApproximate)
 * @method static sAddArray($key, array $values)
 * @method static _prefix($value)
 * @method static _unserialize($value)
 * @method static _serialize($value)
 */
class RedisModel extends RedisDriver
{
    /**
     * redis实例
     *
     * @var Redis
     */
    static protected $redisConn;

    /**
     * 默认配置项
     *
     * @var string
     */
    static protected $defaultOptionName = 'cache';

    /**
     * 缓存链接项配置
     *
     * @var string
     */
    static protected $configFile = 'config/db.config.php';

    /**
     * 切换设置
     *
     * @param string $sessionOptionName
     * @return Redis
     * @throws CoreException
     */
    static function use(string $sessionOptionName): Redis
    {
        static $currentSession = null;
        if (null === $currentSession || $sessionOptionName != $currentSession) {
            $client = new RedisDriver(static::getConfigOptions($sessionOptionName));
            $client->selectCurrentDatabase();

            $currentSession = $sessionOptionName;
            static::$redisConn = $client->link;
        }

        return static::$redisConn;
    }

    /**
     * @param $method
     * @param $argv
     * @return mixed
     * @throws CoreException
     */
    static public function __callStatic($method, $argv)
    {
        static::use(static::$defaultOptionName);

        $result = null;
        if (method_exists(static::$redisConn, $method)) {
            $result = ($argv == null)
                ? static::$redisConn->$method()
                : call_user_func_array([static::$redisConn, $method], $argv);
        }

        return $result;
    }

    /**
     * 获取对应链接配置
     *
     * @param string $configName
     * @return array
     * @throws CoreException
     */
    static protected function getConfigOptions(string $configName): array
    {
        static $redisConfig = null;
        if (null === $redisConfig) {
            $configFile = Loader::read(static::getConfigFilePath(static::$configFile));
            $redisConfig = $configFile['redis'] ?? [];
            if (empty($redisConfig)) {
                throw new CoreException('请先配置redis');
            }
        }

        $dbOptions = $redisConfig[$configName] ?? [];
        if (empty($dbOptions) || !is_array($dbOptions)) {
            throw new CoreException('未定义的redis配置: ' . $configName);
        }

        return $dbOptions;
    }

    /**
     * 解析配置文件路径
     *
     * @param string $file
     * @return string
     */
    static protected function getConfigFilePath(string $file): string
    {
        if (defined('PROJECT_REAL_PATH')) {
            $configFile = PROJECT_REAL_PATH . $file;
        } else {
            $configFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . $file;
        }

        return $configFile;
    }

    /**
     * 设置配置文件
     *
     * @param string $file
     */
    static protected function setConfigFile(string $file)
    {
        static::$configFile = $file;
    }
}