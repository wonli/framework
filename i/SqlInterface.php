<?php

/**
 * @Auth wonli <wonli@live.com>
 * Interface SqlInterface
 */
namespace cross\i;

interface SqlInterface
{
    /**
     * get one data
     *
     * @param string $table database table
     * @param string $fields fields
     * @param string $where conditions
     * @return mixed
     */
    function get($table, $fields, $where);

    /**
     * find data
     *
     * @param string $table
     * @param string $fields
     * @param string $where
     * @param string|int $order
     * @param array $page array('p', 'page');
     * @return mixed
     */
    function find($table, $fields, $where, $order = 1, & $page = array('p', 'limit'));

    /**
     * add data
     *
     * @param string $table
     * @param string $data
     * @param bool $multi
     * @return mixed
     */
    function add($table, $data, $multi = false);

    /**
     * update
     *
     * @param string $table
     * @param string $data
     * @param string $where
     * @return mixed
     */
    function update($table, $data, $where);

    /**
     * del
     *
     * @param string $table
     * @param string $where
     * @return mixed
     */
    function del($table, $where);

}
