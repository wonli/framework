<?php
/**
 * Cross - a micro PHP framework
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

    private $node = [];

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        return new static();
    }

    /**
     * 保存调用关系
     *
     * @param string $node_name
     * @param mixed $node_arguments
     */
    function saveNode(string $node_name, $node_arguments): void
    {
        $this->node = [$node_name => $node_arguments];
    }

    /**
     * 输出HTML标签
     * @param bool $html_decode
     */
    function html(bool $html_decode = true): void
    {
        echo $this->nodeToHTML($html_decode);
    }

    /**
     * 输出DOM
     *
     * @return DOMDocument
     */
    function dom(): DOMDocument
    {
        return CallTreeToHTML::getInstance()->getDom($this->getNode());
    }

    /**
     * 获取当前node内容
     *
     * @return array
     */
    function getNode(): array
    {
        return $this->node;
    }

    /**
     * @return string
     * @see nodeToHTML
     *
     */
    function __toString(): string
    {
        return $this->nodeToHTML();
    }

    /**
     * 把node转换为html
     *
     * @param bool $html_decode
     * @return string
     */
    private function nodeToHTML(bool $html_decode = true): string
    {
        return CallTreeToHTML::getInstance()->getHTML($this->getNode(), $html_decode);
    }
}
