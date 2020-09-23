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
     * @param string $nodeName
     * @param mixed $nodeArguments
     */
    function saveNode(string $nodeName, $nodeArguments): void
    {
        $this->node = [$nodeName => $nodeArguments];
    }

    /**
     * 输出HTML标签
     * @param bool $htmlDecode
     */
    function html(bool $htmlDecode = true): void
    {
        echo $this->nodeToHTML($htmlDecode);
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
     * @param bool $htmlDecode
     * @return string
     */
    private function nodeToHTML(bool $htmlDecode = true): string
    {
        return CallTreeToHTML::getInstance()->getHTML($this->getNode(), $htmlDecode);
    }
}
