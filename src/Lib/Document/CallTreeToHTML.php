<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Lib\Document;

use Closure;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;

/**
 * 把CallTree转换为HTML
 *
 * @author wonli <wonli@live.com>
 * Class NodeToHTML
 * @package Cross\Lib\Document
 */
class CallTreeToHTML
{
    /**
     * @var DOMDocument
     */
    private DOMDocument $dom;

    /**
     * @var DOMNode
     */
    private DOMNode $element;

    private function __construct()
    {
        $this->dom = new DOMDocument();
    }

    static function getInstance(): self
    {
        return new self();
    }

    /**
     * 返回DOM
     *
     * @param $node
     * @return DOMDocument
     * @throws DOMException
     */
    function getDom($node): DOMDocument
    {
        $this->makeNode($node);
        $this->dom->appendChild($this->element);
        return $this->dom;
    }

    /**
     * DOM转HTML
     *
     * @param $node
     * @param bool $htmlDecode
     * @return string
     * @throws DOMException
     */
    function getHTML($node, bool $htmlDecode = true): string
    {
        $dom = $this->getDom($node);
        $dom->encoding = 'utf-8';
        $html = $dom->saveHTML($dom->firstChild);
        if ($htmlDecode) {
            $html = html_entity_decode($html);
        }

        return $html;
    }

    /**
     * 把node转换为dom
     * @param $node
     * @param DOMNode|null $parentElement
     * @return void
     * @throws DOMException
     */
    function makeNode($node, DOMNode $parentElement = null): void
    {
        $content = null;
        $attrSet = [];

        //构造根节点
        if (null === $parentElement) {
            $rootElementName = current(array_keys($node));

            $node = current($node);
            if (isset($node[0]) && !$node[0] instanceof CallTree) {
                if (is_array($node[0])) {
                    if (isset($node[0]['@content'])) {
                        $content = $node[0]['@content'];
                        unset($node[0]['@content']);
                    }
                    $attrSet = $node[0];
                } else {
                    $content = $node[0];
                    unset($node[0]);
                }
            }

            $this->element = $this->dom->createElement($rootElementName, htmlentities($content ?? ''));
            if (!empty($attrSet)) {
                foreach ($attrSet as $attrSetName => $attrSetValue) {
                    $this->element->setAttribute($attrSetName, $attrSetValue);
                }
            }
        }

        //为parentElement设置属性
        if ($parentElement && isset($node[0]) && !$node[0] instanceof CallTree) {
            if (!empty($node[0])) {
                foreach ($node[0] as $attrSetName => $attrSetValue) {
                    if ($attrSetValue instanceof Closure) {
                        $attrSetValue = call_user_func($attrSetValue);
                    }
                    $parentElement->setAttribute($attrSetName, $attrSetValue);
                }
            }
        }

        foreach ($node as $n) {
            if (!empty($n) && $n instanceof CallTree) {
                $nodeDetail = $n->getNode();
                foreach ($nodeDetail as $elementName => $childNode) {

                    //获取当前element中的文本内容
                    if (isset($childNode[0]) && !$childNode[0] instanceof CallTree) {
                        if (is_array($childNode[0])) {
                            if (isset($childNode[0]['@content'])) {
                                $content = $childNode[0]['@content'];
                                unset($childNode[0]['@content']);
                            }
                        } else {
                            $content = $childNode[0];
                            unset($childNode[0]);
                        }
                    }

                    $element = $this->dom->createElement($elementName, htmlentities($content ?? ''));
                    if ($parentElement instanceof DOMElement) {
                        $currentElement = $parentElement->appendChild($element);
                    } else {
                        $currentElement = $this->element->appendChild($element);
                    }

                    if (!empty($childNode)) {
                        $this->makeNode($childNode, $currentElement);
                    }
                }
            }
        }
    }
}
