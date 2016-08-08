<?php
/**
 * cli模式下输出异常信息的模板
 *
 * 根据message中的trace_table 输出一个trace信息的文字表格
 * 内容为message['trace']
 */
if (!empty($message)) {

    $trace = &$message['trace'];
    $table = &$message['trace_table'];
    $previous_trace = &$message['previous_trace'];

    /**
     * 输出ASC logo
     */
    if (!function_exists('ascLogo')) {
        function ascLogo($txtTableInfo)
        {
            $line_width = array_sum($txtTableInfo) + count($txtTableInfo) + 1;
            $asc_logo_data = <<<ASC_LOGO
                                   __         v%s
  ______________  ______________  / /_  ____
 / ___/ ___/ __ \/ ___/ ___/ __ \/ __ \/ __ \
/ /__/ /  / /_/ (__  )__  ) /_/ / / / / /_/ /
\___/_/   \____/____/____/ .___/_/ /_/ .___/
                        /_/         /_/
ASC_LOGO;

            $logo_lines = explode("\n", sprintf($asc_logo_data, \Cross\Core\Delegate::getVersion()));
            $offset = 6;
            $max_width = 0;
            foreach ($logo_lines as $line) {
                $length = strlen($line);
                if ($length > $max_width) {
                    $max_width = $length;
                }
            }

            $half_width = floor($line_width / 2 - ($max_width - $offset) / 2);
            foreach ($logo_lines as $line) {
                for ($i = 0; $i <= $line_width; $i++) {
                    if ($i == $half_width) {
                        echo $line;
                    } elseif ($i < $half_width) {
                        echo ' ';
                    }
                }
                echo PHP_EOL;
            }
            echo PHP_EOL;
        }
    }

    /**
     * 输出带标题的横线
     *
     * @param $txtTableInfo
     * @param string $text
     * @param string $pad_string
     */
    if (!function_exists('line')) {
        function line($txtTableInfo, $text = '', $pad_string = '=')
        {
            $text_length = 0;
            if (!empty($text)) {
                $text_length = strlen($text);
            }

            $line_width = array_sum($txtTableInfo) + count($txtTableInfo) + 1;
            $s = floor($line_width / 2 - $text_length / 2);
            for ($i = 0; $i < $line_width; $i++) {
                if ($i == $s) {
                    echo $text;
                    $i += $text_length;
                }
                echo $pad_string;
            }
            echo PHP_EOL;
        }
    }

    /**
     * 输出表格边框
     *
     * @param $txtTableInfo
     */
    if (!function_exists('th')) {
        function th($txtTableInfo)
        {
            echo '+';
            foreach ($txtTableInfo as $type_name => $line_width) {
                for ($i = 0; $i < $line_width; $i++) {
                    echo '-';
                }
                echo '+';
            }
            echo PHP_EOL;
        }
    }

    /**
     * 输出txt表格头(标题居中)
     *
     * @param $txtTableInfo
     */
    if (!function_exists('tHead')) {
        function tHead($txtTableInfo)
        {
            echo '|';
            foreach ($txtTableInfo as $type_name => $line_width) {
                $name_width = strlen($type_name);
                $name_offset = floor($line_width / 2 - $name_width / 2) + 1;

                $i = 0;
                while ($i++ < $line_width) {
                    if ($i == $name_offset) {
                        echo ucfirst($type_name);
                        $i += $name_width - 1;
                    } else {
                        echo ' ';
                    }
                }
                echo '|';
            }
            echo PHP_EOL;
        }
    }

    /**
     * 输出表格的内容
     *
     * @param $data
     * @param $txtTableInfo
     */
    if (!function_exists('tBody')) {
        function tBody($data, $txtTableInfo)
        {
            echo '|';
            foreach ($txtTableInfo as $type => $line_width) {
                $content_length = strlen($data[$type]);
                $i = 0;
                while ($i++ < $line_width) {
                    if ($i == 2) {
                        echo $data[$type];
                        $i += $content_length - 1;
                    } else {
                        echo ' ';
                    }
                }
                echo '|';
            }
            echo PHP_EOL;
        }
    }

    echo PHP_EOL;
    ascLogo($table);
    line($table, '--  Exception Start  --');
    printf("\n Line: %s \n File: %s \n\n", $message['line'], $message['file']);

    th($table);
    thead($table);
    th($table);

    if (!empty($trace)) {
        foreach ($trace as $t) {
            tBody($t, $table);
        }
        th($table);
    }

    if (!empty($previous_trace)) {
        line($table, 'Previous Trace', ' ');
        th($table);
        foreach ($previous_trace as $t) {
            tBody($t, $table);
        }
        th($table);
    }

    echo PHP_EOL;
    line($table, sprintf("--  Exception END  %s  --", date('Y-m-d H:i:s', time())));
}


