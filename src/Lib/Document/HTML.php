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
 *  echo HTML::a(array('@content'=>'crossphp', 'href'=>'http://www.crossphp.com'));
 *  echo HTML::div(array('@content' => 'im a div', 'style'=>'border:1px solid #dddddd;padding:20px;'),
 *          HTML::a(array('@content'=>'crossphp', 'href'=>'http://www.crossphp.com'))
 *       );
 *  echo HTML::form(array('method'=>'get'),
 *          HTML::div(
 *              HTML::label('User Name:', HTML::input(array('type'=>'text'))),
 *              HTML::label('Password :', HTML::input(array('type'=>'password'))),
 *              HTML::label('          ', HTML::input(array('type'=>'submit', 'value'=>'submit')))
 *          )
 *       );
 * </pre>
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
    static function __callStatic($name, $arguments)
    {
        $callTree = CallTree::getInstance();
        $callTree->saveNode($name, $arguments);
        return $callTree;
    }
}
