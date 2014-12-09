<?php
/**
 * cli模式下输出异常信息的模板
 *
 * 根据message中的trace_table 输出一个trace信息的文字表格
 * 内容为message['trace']
 */
if (! empty($message)) {

    $trace = $message['trace'];
    $table = $message['trace_table'];

    /**
     * 输出带标题的横线
     *
     * @param $txtTableInfo
     * @param string $text
     */
    if (! function_exists('line')) {
        function line($txtTableInfo, $text = "")
        {
            $line_length = array_sum($txtTableInfo) + count($txtTableInfo) + 1;

            $text_len = 0;
            if (!empty($text)) {
                $text_len = strlen($text);
            }
            $s = floor($line_length / 2 - $text_len / 2);
            for ($i = 0; $i < $line_length; $i++) {
                if ($i == $s) {
                    echo $text;
                    $i += $text_len;
                }
                echo '=';
            }
            echo "\n";
        }
    }

    /**
     * 输出表格边框
     *
     * @param $txtTableInfo
     */
    if (! function_exists('th')) {
        function th($txtTableInfo)
        {
            echo '+';
            foreach ($txtTableInfo as $t_name => $t_length) {
                for ($i = 0; $i < $t_length; $i++) {
                    echo '-';
                }
                echo '+';
            }
            echo "\n";
        }
    }

    /**
     * 输出txt表格头(标题居中)
     *
     * @param $txtTableInfo
     */
    if (! function_exists('tHead')) {
        function tHead($txtTableInfo)
        {
            echo '|';
            foreach ($txtTableInfo as $t_name => $t_length) {
                $name_len = strlen($t_name);
                $name_start = floor($t_length / 2 - $name_len / 2) + 1;

                $i = 0;
                while ($i++ < $t_length) {
                    if ($i == $name_start) {
                        echo ucfirst($t_name);
                        $i += $name_len - 1;
                    } else {
                        echo ' ';
                    }
                }
                echo '|';
            }
            echo "\n";
        }
    }

    /**
     * 输出表格的内容
     *
     * @param $data
     * @param $txtTableInfo
     */
    if (! function_exists('tBody')) {
        function tBody($data, $txtTableInfo)
        {
            echo '|';
            foreach ($data as $d_key => $d_val) {
                if (!isset($txtTableInfo[$d_key])) {
                    continue;
                }
                $tr_length = $txtTableInfo[$d_key];
                $val_length = strlen($d_val);

                $i = 0;
                while ($i++ < $tr_length) {
                    if ($i == 2) {
                        echo $d_val;
                        $i += $val_length - 1;
                    } else {
                        echo ' ';
                    }
                }
                echo '|';
            }
            echo "\n";
        }
    }

    echo "\n";
    line($table, '--  Exception Start  --');
    printf("\n Line: %s \n File: %s \n\n", $message['line'], $message['file']);

    th($table);
    thead($table);
    th($table);

    if (! empty($trace)) {
        foreach ($trace as $t) {
            tBody($t, $table);
            th($table);
        }
    }

    echo "\n";
    line($table, sprintf("--  Exception END  %s  --", date('Y-m-d H:i:s', time())));
}


