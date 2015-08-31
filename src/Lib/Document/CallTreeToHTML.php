<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.4.0
 */

namespace Cross\Lib\Document;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * 把CallTree转换为HTML
 *
 * @Auth: wonli <wonli@live.com>
 * Class NodeToHTML
 * @package Cross\Lib\Document
 */
class CallTreeToHTML
{
    /**
     * @var DOMDocument
     */
    private $dom;

    /**
     * @var DOMNode
     */
    private $element;

    private function __construct()
    {
        $this->dom = new DOMDocument();
    }

    static function getInstance()
    {
        return new CallTreeToHTML();
    }

    /**
     * 返回DOM
     *
     * @param $node
     * @return DOMDocument
     */
    function getDom($node)
    {
        $this->makeNode($node);
        $this->dom->appendChild($this->element);
        return $this->dom;
    }

    /**
     * DOM转HTML
     *
     * @param $node
     * @return string
     */
    function getHTML($node)
    {
        $dom = $this->getDom($node);
        return $dom->saveHTML();
    }

    /**
     * 把node转换为dom
     *
     * @param $node
     * @param DOMNode $parentElement
     */
    function makeNode($node, DOMNode $parentElement = null)
    {
        $content = null;
        $attr_set = array();

        //构造根节点
        if (null === $parentElement) {
            $root_element_name = current(array_keys($node));

            $node = current($node);
            if (isset($node[0]) && !$node[0] instanceof CallTree) {
                if (is_array($node[0])) {
                    if (isset($node[0]['@content'])) {
                        $content = $node[0]['@content'];
                        unset($node[0]['@content']);
                    }
                    $attr_set = $node[0];
                } else {
                    $content = $node[0];
                    unset($node[0]);
                }
            }

            $this->element = $this->dom->createElement($root_element_name, $content);
            if (!empty($attr_set)) {
                foreach ($attr_set as $attr_set_name => $attr_set_value) {
                    $this->element->setAttribute($attr_set_name, $attr_set_value);
                }
            }
        }

        //为parentElement设置属性
        if ($parentElement && isset($node[0]) && !$node[0] instanceof CallTree) {
            if (!empty($node[0])) {
                foreach ($node[0] as $attr_set_name => $attr_set_value) {
                    if ($attr_set_value instanceof Closure) {
                        $attr_set_value = call_user_func($attr_set_value);
                    }
                    $parentElement->setAttribute($attr_set_name, $attr_set_value);
                }
            }
        }

        foreach ($node as $n) {
            if (!empty($n) && $n instanceof CallTree) {
                $node_detail = $n->getNode();
                foreach ($node_detail as $element_name => $child_node) {

                    //获取当前element中的文本内容
                    if (isset($child_node[0]) && !$child_node[0] instanceof CallTree) {
                        if (is_array($child_node[0])) {
                            if (isset($child_node[0]['@content'])) {
                                $content = $child_node[0]['@content'];
                                unset($child_node[0]['@content']);
                            }
                        } else {
                            $content = $child_node[0];
                            unset($child_node[0]);
                        }
                    }

                    $element = $this->dom->createElement($element_name, $content);
                    if ($parentElement instanceof DOMElement) {
                        $current_element = $parentElement->appendChild($element);
                    } else {
                        $current_element = $this->element->appendChild($element);
                    }

                    if (!empty($child_node)) {
                        $this->makeNode($child_node, $current_element);
                    }
                }
            }
        }
    }
}
