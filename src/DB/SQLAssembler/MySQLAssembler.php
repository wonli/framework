<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\DB\SQLAssembler;

/**
 * @author wonli <wonli@live.com>
 * Class MySQLAssembler
 * @package Cross\DB\SQLAssembler
 */
class MySQLAssembler extends SQLAssembler
{
    /**
     * @see SQLAssembler::parseCondition()
     *
     * @param $operator
     * @param $field
     * @param $field_config
     * @param $is_mixed_field
     * @param $condition_connector
     * @param $connector
     * @param $params
     * @return array
     * @throws \Cross\Exception\CoreException
     */
    function parseCondition($operator, $field, $field_config, $is_mixed_field, $condition_connector, $connector, array &$params)
    {
        $condition = array();
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
