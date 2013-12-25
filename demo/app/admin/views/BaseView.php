<?php
/**
* @Author: wonli <wonli@live.com>
*/
class BaseView extends CoreView
{
    private $nav_menu;

    private $menu_data;

    function get_menu(  )
    {
    	return $this->menu_data;
    }

    function get_nav_menu()
    {
    	return $this->nav_menu;
    }

    function set_nav_menu($nav_data)
    {
    	$this->nav_menu = $nav_data;
    }

    function set_menu( $data )
    {
    	$this->menu_data = $data;
    }

    function js_notes($notes)
    {
        ?>
        <script type="text/javascript">pop.alert('<?php echo $notes['status'] < 0 ? -1 : 1 ?>', '<?php echo $notes['message'] ?>')</script>
        <?php
    }

    function page($page, $tpl='page')
    {
        list($controller, $params) = $page['link'];

        $_dot = isset($page['dot']) ? $page["dot"] : $this->urlconfig['dot'];
        include $this->tpl("page/{$tpl}");
    }

}
