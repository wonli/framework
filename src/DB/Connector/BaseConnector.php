<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\Connector;

use Cross\I\PDOConnector;
use PDO;

/**
 * @author wonli <wonli@live.com>
 * Class BaseConnector
 * @package Cross\DB\Connector
 */
abstract class BaseConnector implements PDOConnector
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $sequence;

    /**
     * 合并用户输入的options
     *
     * @param array $defaultOptions
     * @param array $options
     * @return array
     */
    protected static function getOptions(array $defaultOptions, array $options): array
    {
        if (!empty($options)) {
            foreach ($options as $optionKey => $optionVal) {
                $defaultOptions[$optionKey] = $optionVal;
            }
        }

        return $defaultOptions;
    }

    /**
     * 设置序号
     *
     * @param string $sequence
     */
    public function setSequence(string $sequence): void
    {
        $this->sequence = $sequence;
    }
}
