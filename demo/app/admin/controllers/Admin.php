<?php defined('DOCROOT')or die('Access Denied');
/**
* @Author: wonli <wonli@live.com>
*/
class Admin extends CoreController
{
    protected $SEC;

    function __construct()
    {
        parent::__construct();
        $this->SEC = $this->loadModule("Security");
    }

    function index()
    {
        $this->to("admin:login");
    }

    function login()
    {
        $data = '';

        if(! empty($_SESSION ['admin']))
        {
            $this->to('panel');
        }

        if( $this->is_post() )
        {
            $args = $this->getArgs();

            $data['notes'] = $this->loadModule("Admin")->check_admin($args['username'],
                $args['password'], $args["vlocation"], $args["vcode"] );

            if($data['notes']["status"] == 1) {
                $_SESSION['admin'] = $args['username'];
                return $this->to('panel');
            }
        }

        $data["location"] = $this->SEC->shuffle_location();
        $this->view->display($data, 'login');
    }

    function logout()
    {
        $_SESSION = array();
        $this->to("admin:login");
    }
}




