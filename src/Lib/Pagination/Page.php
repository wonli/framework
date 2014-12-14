<?php
/**
 * Usage:
 * $current = empty($_GET['pn'])? 1: $_GET['pn'];
 * $total = 2;
 * $url = "?pn=<:pn:>";
 * $page_link = new page($current,$total,$url);
 * echo $page_link;
 */
namespace Cross\Lib\Pagination;

class Page
{
    private $page_link;

    public function __construct($current, $total, $url, $half = 5)
    {
        $page_link = '';
        $current = ($current <= 0) ? 1 : $current;
        $current = ($current > $total) ? $total : $current;
        if ($current == 1 && $total == $current) {
            $this->page_link = $page_link;

            return true;
        }

        ($current == 1) ?
            $page_link .= '上一页&nbsp;' :
            $page_link .= "<a href='" . str_replace("<:pn:>", $current - 1, $url) . "'>上一页</a>&nbsp;";

        for ($i = $current - $half, $i = ($i > 0) ? $i : 1, $j = $current + $half, $j = ($j > $total) ? $total : $j; $i <= $j; $i++) {
            ($i == $current) ?
                $page_link .= "<b>" . $i . "</b>&nbsp;" :
                $page_link .= "<a href='" . str_replace("<:pn:>", $i, $url) . "'>[" . $i . "]</a>&nbsp;";
        }

        ($current == $total) ?
            $page_link .= '下一页' :
            $page_link .= "<a href='" . str_replace("<:pn:>", $current + 1, $url) . "'>下一页</a>";

        $this->page_link = $page_link;

        return true;
    }

    public function __toString()
    {
        return $this->page_link;
    }
}
