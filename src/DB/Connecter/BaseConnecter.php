<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.4.0
 */
namespace Cross\DB\Connecter;

use Cross\I\PDOConnecter;
use PDO;

/**
 * @Auth: wonli <wonli@live.com>
 * Class BaseConnecter
 * @package Cross\DB\Connecter
 */
abstract class BaseConnecter implements PDOConnecter
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * 默认连接参数
     * <ul>
     *  <li>PDO::ATTR_PERSISTENT => false //禁用长连接</li>
     *  <li>PDO::ATTR_EMULATE_PREPARES => false //禁用模拟预处理</li>
     *  <li>PDO::ATTR_STRINGIFY_FETCHES => false //禁止数值转换成字符串</li>
     *  <li>PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true //使用缓冲查询</li>
     *  <li>PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION //发生错误时抛出异常 </li>
     *  <li>PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" </li>
     * </ul>
     *
     * @var array
     */
    protected $options = array(
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    );

    /**
     * 合并用户输入的options
     *
     * @param $options
     * @return array
     */
    protected function getOptions($options)
    {
        if (!empty($options)) {
            foreach ($options as $option_key => $option_val) {
                $this->options[$option_key] = $option_val;
            }
        }

        return $this->options;
    }
}
