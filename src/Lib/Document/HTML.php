<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Lib\Document;

/**
 * 用PHP来描述HTML
 *
 * @author wonli <wonli@live.com>
 * Class HTML
 * @package Cross\Lib\Document
 *
 * <pre>
 * example:
 *  echo HTML::div('im a div');
 *  echo HTML::a(['@content'=>'crossphp', 'href'=>'http://www.crossphp.com']);
 *  echo HTML::div(['@content' => 'im a div', 'style'=>'border:1px solid #dddddd;padding:20px;'],
 *          HTML::a(['@content'=>'crossphp', 'href'=>'http://www.crossphp.com'])
 *       );
 *  echo HTML::form(['method'=>'get'],
 *          HTML::div(
 *              HTML::label('User Name:', HTML::input(['type'=>'text'])),
 *              HTML::label('Password :', HTML::input(['type'=>'password'])),
 *              HTML::label('          ', HTML::input(['type'=>'submit', 'value'=>'submit']))
 *          )
 *       );
 *
 * 支持标签列表:
 * https://developer.mozilla.org/en-US/docs/Web/HTML/Element
 * </pre>
 *
 * @method static base($attributes, ...$ctx = null)
 * @method static head($attributes, ...$ctx = null)
 * @method static link($attributes, ...$ctx = null)
 * @method static meta($attributes, ...$ctx = null)
 * @method static style($attributes, ...$ctx = null)
 * @method static title($attributes, ...$ctx = null)
 * @method static body($attributes, ...$ctx = null)
 * @method static address($attributes, ...$ctx = null)
 * @method static article($attributes, ...$ctx = null)
 * @method static aside($attributes, ...$ctx = null)
 * @method static footer($attributes, ...$ctx = null)
 * @method static header($attributes, ...$ctx = null)
 * @method static h1($attributes, ...$ctx = null)
 * @method static h2($attributes, ...$ctx = null)
 * @method static h3($attributes, ...$ctx = null)
 * @method static h4($attributes, ...$ctx = null)
 * @method static h5($attributes, ...$ctx = null)
 * @method static h6($attributes, ...$ctx = null)
 * @method static hgroup($attributes, ...$ctx = null)
 * @method static main($attributes, ...$ctx = null)
 * @method static nav($attributes, ...$ctx = null)
 * @method static section($attributes, ...$ctx = null)
 * @method static blockquote($attributes, ...$ctx = null)
 * @method static dd($attributes, ...$ctx = null)
 * @method static div($attributes, ...$ctx = null)
 * @method static dl($attributes, ...$ctx = null)
 * @method static dt($attributes, ...$ctx = null)
 * @method static figcaption($attributes, ...$ctx = null)
 * @method static figure($attributes, ...$ctx = null)
 * @method static hr($attributes, ...$ctx = null)
 * @method static li($attributes, ...$ctx = null)
 * @method static ol($attributes, ...$ctx = null)
 * @method static p($attributes, ...$ctx = null)
 * @method static pre($attributes, ...$ctx = null)
 * @method static ul($attributes, ...$ctx = null)
 * @method static a($attributes, ...$ctx = null)
 * @method static abbr($attributes, ...$ctx = null)
 * @method static b($attributes, ...$ctx = null)
 * @method static bdi($attributes, ...$ctx = null)
 * @method static bdo($attributes, ...$ctx = null)
 * @method static br($attributes, ...$ctx = null)
 * @method static cite($attributes, ...$ctx = null)
 * @method static code($attributes, ...$ctx = null)
 * @method static data($attributes, ...$ctx = null)
 * @method static dfn($attributes, ...$ctx = null)
 * @method static em($attributes, ...$ctx = null)
 * @method static i($attributes, ...$ctx = null)
 * @method static kbd($attributes, ...$ctx = null)
 * @method static mark($attributes, ...$ctx = null)
 * @method static q($attributes, ...$ctx = null)
 * @method static rb($attributes, ...$ctx = null)
 * @method static rp($attributes, ...$ctx = null)
 * @method static rt($attributes, ...$ctx = null)
 * @method static rtc($attributes, ...$ctx = null)
 * @method static ruby($attributes, ...$ctx = null)
 * @method static s($attributes, ...$ctx = null)
 * @method static samp($attributes, ...$ctx = null)
 * @method static small($attributes, ...$ctx = null)
 * @method static span($attributes, ...$ctx = null)
 * @method static strong($attributes, ...$ctx = null)
 * @method static sub($attributes, ...$ctx = null)
 * @method static sup($attributes, ...$ctx = null)
 * @method static time($attributes, ...$ctx = null)
 * @method static u($attributes, ...$ctx = null)
 * @method static var($attributes, ...$ctx = null)
 * @method static wbr($attributes, ...$ctx = null)
 * @method static area($attributes, ...$ctx = null)
 * @method static audio($attributes, ...$ctx = null)
 * @method static img($attributes, ...$ctx = null)
 * @method static map($attributes, ...$ctx = null)
 * @method static track($attributes, ...$ctx = null)
 * @method static video($attributes, ...$ctx = null)
 * @method static embed($attributes, ...$ctx = null)
 * @method static iframe($attributes, ...$ctx = null)
 * @method static object($attributes, ...$ctx = null)
 * @method static param($attributes, ...$ctx = null)
 * @method static picture($attributes, ...$ctx = null)
 * @method static source($attributes, ...$ctx = null)
 * @method static canvas($attributes, ...$ctx = null)
 * @method static noscript($attributes, ...$ctx = null)
 * @method static script($attributes, ...$ctx = null)
 * @method static del($attributes, ...$ctx = null)
 * @method static ins($attributes, ...$ctx = null)
 * @method static caption($attributes, ...$ctx = null)
 * @method static col($attributes, ...$ctx = null)
 * @method static colgroup($attributes, ...$ctx = null)
 * @method static table($attributes, ...$ctx = null)
 * @method static tbody($attributes, ...$ctx = null)
 * @method static td($attributes, ...$ctx = null)
 * @method static tfoot($attributes, ...$ctx = null)
 * @method static th($attributes, ...$ctx = null)
 * @method static thead($attributes, ...$ctx = null)
 * @method static tr($attributes, ...$ctx = null)
 * @method static button($attributes, ...$ctx = null)
 * @method static datalist($attributes, ...$ctx = null)
 * @method static fieldset($attributes, ...$ctx = null)
 * @method static form($attributes, ...$ctx = null)
 * @method static input($attributes, ...$ctx = null)
 * @method static label($attributes, ...$ctx = null)
 * @method static legend($attributes, ...$ctx = null)
 * @method static meter($attributes, ...$ctx = null)
 * @method static optgroup($attributes, ...$ctx = null)
 * @method static option($attributes, ...$ctx = null)
 * @method static output($attributes, ...$ctx = null)
 * @method static progress($attributes, ...$ctx = null)
 * @method static select($attributes, ...$ctx = null)
 * @method static textarea($attributes, ...$ctx = null)
 * @method static details($attributes, ...$ctx = null)
 * @method static dialog($attributes, ...$ctx = null)
 * @method static menu($attributes, ...$ctx = null)
 * @method static summary($attributes, ...$ctx = null)
 * @method static slot($attributes, ...$ctx = null)
 * @method static template($attributes, ...$ctx = null)
 */
class HTML
{
    /**
     * HTML处理类入口
     *
     * @param string $name
     * @param mixed $arguments
     * @return CallTree
     */
    static function __callStatic(string $name, mixed $arguments): CallTree
    {
        $callTree = CallTree::getInstance();
        $callTree->saveNode($name, $arguments);
        return $callTree;
    }
}
