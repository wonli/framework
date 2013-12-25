<?php
/**
* @Author: wonli <wonli@live.com>
*/
class Security extends Base
{
    private $SEC;

    function __construct()
    {
        parent::__construct();
        $this->SEC = $this->loadModule("Security");
    }

    function index()
    {
        $this->to("security:output");
    }
    
    function output()
    {
        $data = $this->SEC->secrityData($this->u);        
        $this->view->display($data);
    }
    
    function download()
    {
        $data = $this->SEC->output_img($this->u);        
        $this->view->display($data);        
    }
    
    function bind()
    {
        $data = $this->SEC->bindcard($this->u);
        $this->view->display($data);
    }
    
    function refresh()
    {
        $data = $this->SEC->updateCard($this->u);
        $this->view->display($data);
    }    
    
    function kill()
    {
        $data = $this->SEC->killbind($this->u);
        $this->view->display($data);
    }     

    function create()
    {
        $data = $this->SEC->create_table();
        $this->view->display($data);
    }
    
    function changepassword()
    {
        $data = array();
        if($this->is_post())
        {
            $args = $this->getArgs();

            if($args ['np1'] != $args ['np2'])
            {
                $data ['notes'] = $this->result(-1, "两次输入的密码必须一致");
            }
            else
            {
                $isright = $this->SEC->checkPassword( $args['op'] );
                if($isright)
                {
                    $data ['notes'] = $this->SEC->update_password( $args['np1'] );
                } else {
                    $data ['notes'] = $this->result(-2, "原密码错误");
                }
            }
        }

        $this->view->display($data);
    }
}