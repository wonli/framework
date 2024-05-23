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
     * @param array|string $fieldConfig
     * @param bool $isMixedField
     * @param string $conditionConnector
     * @param string $connector
     * @param array $params
     * @return array
     * @throws CoreException
     * @see SQLAssembler::parseCondition()
     *
     */
    function parseCondition(string $operator, string $field, array|string $fieldConfig, bool $isMixedField, string $conditionConnector, string $connector, array &$params): array
    {
        $condition = [];
        switch ($connector) {
            case 'FIND_IN_SET':
                $condition[" {$conditionConnector} "][] = sprintf('FIND_IN_SET(?, %s)', $field);
                $params[] = $fieldConfig;
                break;

            case 'REGEXP':
                $condition[" {$conditionConnector} "][] = sprintf('%s REGEXP(?)', $field);
                $params[] = $fieldConfig;
                break;

            case 'INSTR':
                $condition[" {$conditionConnector} "][] = sprintf('INSTR(%s, ?)', $field);
                $params[] = $fieldConfig;
                break;

            default:
                $condition = parent::parseCondition($operator, $field, $fieldConfig, $isMixedField, $conditionConnector, $connector, $params);
        }

        return $condition;
    }
}
