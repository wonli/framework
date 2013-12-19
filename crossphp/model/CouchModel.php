<?php
/**
 * @Auth: wonli <wonli@live.com>
 * CouchModel.php
 */

class CouchModel
{
    /**
     * @param $link_params
     * @throws CoreException
     */
    function __construct($link_params)
    {
        $host  = ! is_array($link_params['host']) ? array($link_params['host']) : $link_params['host'];
        $bucket = isset($link_params['bucket']) ? $link_params['bucket'] : 'default';
        $persistent = isset($link_params['persistent']) ? $link_params['persistent'] : true;

        try
        {
            $this->link = new Couchbase($host, $link_params['user'], $link_params['pwd'], $bucket, $persistent );
        } catch ( Exception $e ) {
            throw new CoreException ( $e->getMessage() );
        }
    }

    /**
     * 调用Couch提供的方法
     *
     * @param $method
     * @param $argv
     * @return mixed|null
     */
    public function __call($method, $argv)
    {
        $result = null;
        if(method_exists($this->link, $method))
        {
            $result = ($argv == null)
                ? $this->link->$method()
                : call_user_func_array(array($this->link, $method), $argv);
        }
        return $result;
    }
}
