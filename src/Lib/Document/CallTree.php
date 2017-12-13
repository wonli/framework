<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Lib\Document;

use DOMDocument;

/**
 * 保存调用关系
 *
 * @author wonli <wonli@live.com>
 * Class NodeTree
 * @package Cross\Lib\Document
 */
class CallTree
{

    private $node = array();

    private function __construct()
    {

    }

    public static function getInstance()
    {
        return new CallTree();
    }

    /**
     * 保存调用关系
     *
     * @param string $node_name
     * @param mixed $node_arguments
     */
    function saveNode($node_name, $node_arguments)
    {
        $this->node = array($node_name => $node_arguments);
    }

    /**
     * 输出HTML标签
     * @param bool $html_decode
     */
    function html($html_decode = true)
    {
        echo $this->nodeToHTML($html_decode);
    }

    /**
     * 输出DOM
     *
     * @return DOMDocument
     */
    function dom()
    {
        return CallTreeToHTML::getInstance()->getDom($this->getNode());
    }

    /**
     * 获取当前node内容
     *
     * @return array
     */
    function getNode()
    {
        return $this->node;
    }

    /**
     * @see nodeToHTML
     *
     * @return string
     */
    function __toString()
    {
        return $this->nodeToHTML();
    }

    /**
     * 把node转换为html
     *
     * @param bool $html_decode
     * @return string
     */
    private function nodeToHTML($html_decode = true)
    {
        return CallTreeToHTML::getInstance()->getHTML($this->getNode(), $html_decode);
    }
}
