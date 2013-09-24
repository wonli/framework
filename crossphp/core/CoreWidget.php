<?php
/**
 * @Auth: wonli <wonli@live.com>
 * CoreWidget.php
 */

class CoreWidget extends Cross
{

    function __construct( $app_name )
    {
        $this->app_name = $app_name;
    }

    public static function init( $app_name )
    {
        return new CoreWidget( $app_name );
    }

    function get($controller, $args = null)
    {
        $config = Config::load( $this->app_name )->parse( null );
    }

}
