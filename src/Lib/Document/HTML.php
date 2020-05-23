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
    static function __callStatic(string $name, $arguments): CallTree
    {
        $callTree = CallTree::getInstance();
        $callTree->saveNode($name, $arguments);
        return $callTree;
    }
}
