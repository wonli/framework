<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\SQLAssembler;

use Cross\Exception\CoreException;

/**
 * @author wonli <wonli@live.com>
 * Class MySQLAssembler
 * @package Cross\DB\SQLAssembler
 */
class MySQLAssembler extends SQLAssembler
{
    /**
     * @param string $operator
     * @param string $field
     * @param mixed $field_config
     * @param bool $is_mixed_field
     * @param string $condition_connector
     * @param string $connector
     * @param array $params
     * @return array
     * @throws CoreException
     * @see SQLAssembler::parseCondition()
     *
     */
    function parseCondition(string $operator, string $field, $field_config, bool $is_mixed_field, string $condition_connector, string $connector, array &$params): array
    {
        $condition = [];
        switch ($connector) {
            case 'FIND_IN_SET':
                $condition[" {$condition_connector} "][] = sprintf('FIND_IN_SET(?, %s)', $field);
                $params[] = $field_config;
                break;

            case 'REGEXP':
                $condition[" {$condition_connector} "][] = sprintf('%s REGEXP(?)', $field);
                $params[] = $field_config;
                break;

            case 'INSTR':
                $condition[" {$condition_connector} "][] = sprintf('INSTR(%s, ?)', $field);
                $params[] = $field_config;
                break;

            default:
                $condition = parent::parseCondition($operator, $field, $field_config, $is_mixed_field, $condition_connector, $connector, $params);
        }

        return $condition;
    }
}
