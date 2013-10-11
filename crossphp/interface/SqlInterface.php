<?php

interface SqlInterface
{
    /**
     * get one data
     *
     * @param $table database table
     * @param $fields fileds
     * @param $where conditions
     * @return mixed
     */
    function get($table, $fields, $where);

    /**
     * find data
     *
     * @param $table
     * @param $fields
     * @param $where
     * @param $order
     * @param $page array('p', 'page');
     * @return mixed
     */
    function find($table, $fields, $where, $order = 1, & $page = array('p', 'limit') );

    /**
     * add data
     *
     * @param $table
     * @param $data
     * @param $multi
     * @return mixed
     */
    function add($table, $data, $multi = false);

    /**
     * update
     *
     * @param $table
     * @param $data
     * @param $where
     * @return mixed
     */
    function update($table, $data, $where);

    /**
     * del
     *
     * @param $table
     * @param $where
     * @return mixed
     */
    function del($table, $where);

}
