<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class Article
 */
class Install extends CoreController {

    /**
     * @var InstallModule
     */
    protected $INSTALL;

    function __construct() {
        parent::__construct();
        $this->INSTALL = new InstallModule();

        $is_install = $this->INSTALL->isInstall();
        if( $is_install && $this->action != 'endnav' ) {
            die("please del cache/install/install.Lock ...");
        }
    }

    //安装引导页
    function index() {
        $data['agreement'] = $this->INSTALL->getAgreement();
        $this->display( $data );
    }

    //测试数据库配置
    function config() {

        if(! $this->is_post()) {
            $this->to("install");
        }

        $data ['db_config'] = $this->INSTALL->getDBConfig();
        $this->display( $data );
    }

    //保存配置文件
    function saveConfig() {

        if(! $this->is_post()) {
            $this->to("install");
        }

        if(! empty($_POST['dbConfig'])) {
            $this->INSTALL->updateDBConfig( $_POST['dbConfig'] );
        }

        $this->to("install:test");
    }

    //配置项测试
    function test() {
        $data['config'] = $this->INSTALL->getDBConfig( true );
        $this->display($data);
    }

    //导入数据
    function importData() {
        $IMPORT = new ImportModule;
        $sql = $this->INSTALL->getSQL();

        $ret = $IMPORT->execSQL( $sql );
        if($ret['status'] == 1) {
            $this->to("install:succeed");
        } else {
            $this->display( $ret['message'] );
        }
    }

    //导入数据成功
    function succeed() {
        $this->display();
    }

    //创建管理员账户
    function createAdmin() {

        if(! $this->is_post()) {
            $this->to("install");
        }

        $data ['name'] = $_POST['name'];
        $data ['password'] = $_POST['password'];
        $data ['rid'] = 0;
        $data ['t'] = 1;

        $ADMIN = new AdminModule();
        $ret = $ADMIN->add_admin( $data );

        if($ret) {
            $this->INSTALL->writeInstallFlag();
            $this->to("install:endnav");
        } else {
            $this->to("install:succeed");
        }
    }

    //安装结束
    function endNav() {
        $is_install = $this->INSTALL->isInstall();
        if($is_install) {
            $this->display();
        } else {
            $this->to("install");
        }
    }
}
