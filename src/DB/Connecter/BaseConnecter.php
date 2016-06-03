<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
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
     * 合并用户输入的options
     *
     * @param array $default_options
     * @param array $options
     * @return array
     */
    protected static function getOptions(array $default_options, array $options)
    {
        if (!empty($options)) {
            foreach ($options as $option_key => $option_val) {
                $default_options[$option_key] = $option_val;
            }
        }

        return $default_options;
    }
}
