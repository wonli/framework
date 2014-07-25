<?php
/**
 * Usage:
 * $current = empty($_GET['pn'])? 1: $_GET['pn'];
 * $total = 2;
 * $url = "?pn=<:pn:>";
 * $pagelink = new page($current,$total,$url);
 * echo $pagelink;
 */
namespace cross\lib\pagination;

class Page
{
    private $pagelink;

    public function __construct($current, $total, $url, $half = 5)
    {
        $pagelink = "";
        $current = ($current <= 0) ? 1 : $current;
        $current = ($current > $total) ? $total : $current;
        if ($current == 1 && $total == $current) {
            $this->pagelink = $pagelink;

            return true;
        }

        ($current == 1) ?
            $pagelink .= "上一页&nbsp;" :
            $pagelink .= "<a href='" . str_replace("<:pn:>", $current - 1, $url) . "'>上一页</a>&nbsp;";

        for ($i = $current - $half, $i = ($i > 0) ? $i : 1, $j = $current + $half, $j = ($j > $total) ? $total : $j; $i <= $j; $i++) {
            ($i == $current) ?
                $pagelink .= "<b>" . $i . "</b>&nbsp;" :
                $pagelink .= "<a href='" . str_replace("<:pn:>", $i, $url) . "'>[" . $i . "]</a>&nbsp;";
        }

        ($current == $total) ?
            $pagelink .= "下一页" :
            $pagelink .= "<a href='" . str_replace("<:pn:>", $current + 1, $url) . "'>下一页</a>";

        $this->pagelink = $pagelink;

        return true;
    }

    public function __toString()
    {
        return $this->pagelink;
    }
}
