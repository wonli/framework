<?php
/**
 * @Auth: wonli <wonli@live.com>
 * ImportModule.php
 */

//设置超时
set_time_limit(0);

class ImportModule extends CoreModule {

    /**
     * 执行SQL语句
     *
     * @param $data
     * @return array|string
     */
    function execSQL( $data ) {

        try{

            $this->link->execute( $data );
            return $this->result(1, 'ok');

        } catch (Exception $e) {

            return $this->result(-1, $e->getMessage());
        }
    }

}
