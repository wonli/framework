<?php
/**
 * @Auth: wonli <wonli@live.com>
 * InstallModule.php
 */

class InstallModule {

    /**
     * 获取要导入的sql文件内容
     */
    function getSQL() {
        return Loader::read("::cache/install/blog.sql", false);
    }

    /**
     * 协议内容
     *
     * @return mixed
     */
    function getAgreement() {
        return Loader::read("::cache/install/agreement.html", false);
    }

    /**
     * 数据配置文件
     *
     * @param bool $return_data 读取文件/读取文件内容
     * @return mixed
     */
    function getDBConfig( $return_data = false ) {
        return Loader::read("::config/db.config.php", $return_data);
    }

    /**
     * 更新数据库配置文件内容
     *
     * @param $data
     */
    function updateDBConfig( $data ) {
        $config_file = Loader::getFilePath("::config/db.config.php");
        if(! file_exists($config_file)) {
            Helper::mkfile( $config_file );
        }

        file_put_contents($config_file, $data);
    }

    /**
     * 生成标识已安装的文件
     *
     * @return bool
     */
    function writeInstallFlag( ) {
        $file = Loader::getFilePath("::cache/install/install.LOCK");

        if(! file_exists($file)) {
            Helper::mkfile( $file );
        }

        return true;
    }

    /**
     * 判断是否已经安装过
     *
     * @return bool
     */
    function isInstall() {
        $file = Loader::getFilePath("::cache/install/install.LOCK");

        if( file_exists($file) ) {
            return true;
        }

        return false;
    }
}
