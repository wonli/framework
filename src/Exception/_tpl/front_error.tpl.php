<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CrossPHP Exception</title>
    <style type="text/css">
    body {font:14px helvetica,arial,sans-serif;color:#333;background-color:#f5f5f5;padding:0;margin: 0;max-height:100%;}
    a {text-decoration:none;}
    .header {padding:20px 20px 10px 20px;color:white;background:#a94442;box-sizing:border-box;border-top:2px solid #c62d2d;}
    .container{height:100%;width:100%;margin:0;padding:0;left:0;top:0;}
    .exc-title {margin:0;color:#dadada;text-shadow:0 1px 2px rgba(0, 0, 0, .1);}
    .exc-message {font-size:30px;margin:5px 0;word-wrap:break-word;}
    .stack-container {height:100%;position:relative;border-top:3px solid #be0000}
    .details {padding:10px;}
    .frame {padding:14px;background:#efefef;cursor:pointer;}
    .frame.active {background-color:#666666;color:#f3f3f3;}
    .frame:not(.active):hover {background:#dadada;color:#5d5d5d;}
    .code-block {padding:0;margin:0;border-bottom:3px dashed #dadada;}
    .code-segment {padding:10px 6px 0 30px;min-height:250px;background-color:#fff;border-bottom:1px dashed #f1f1f1;border-top:1px solid #f1f1f1;}
    .data-table-container {padding:10px;}
    .data-table {width:100%; margin:10px 0;}
    .data-table label {font-size:16px;font-weight:bold;color:#525252;margin:10px 0;padding:10px 0;display:block;border-bottom:1px dotted #b0b0b0;}
    .data-table tbody {font:13px consolas, monospace;}
    .data-table thead {display:none;} .data-table tr {padding:5px 0;}
    .data-table td:first-child {width:20%;min-width:130px;overflow:hidden;font-weight:bold;color:#463C54;padding-right:5px;}
    .data-table td:last-child {width:80%;-ms-word-break:break-all;word-break:break-all;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto;}
    .data-table .empty {color:rgba(0, 0, 0, .3);font-style:italic;}
    .exception{font-family:"microsoft yahei",serif;}
    .line{border-right:1px solid #bbb;position:absolute;padding-right:5px;margin-right:20px;left:3px;width:30px;text-align:right}
    .footer{text-align:center;height:30px;background: #333;color:#808080;line-height:30px;box-sizing:border-box;border-top:2px solid rgba(0, 0, 0, 0.35)}
    .footer>a{color:#565656}@media (max-width: 768px) {.code-segment {display:none}}
    </style>
</head>

<body>
<div class="container">

<div class="stack-container">
<div class="header">
    <div class="exception">
        <h3 class="exc-title">
            <?php printf('File: %s Line: %s', $message['main']['show_file'], $message['main']['line'] ); ?>
        </h3>

        <p class="exc-message">
            <?php echo $message['main']['message'] ?>
        </p>
    </div>
</div>

<div class="frame-code-container">
    <?php if (!empty($message['trace'])) : ?>
        <?php foreach ($message['trace'] as $k => $m) : ?>
            <div id="trace_info_<?php echo $k ?>" class="trace_info_div" style="display:none">
                <div class="code-block"></div>
                <div class="code-segment">
                    <?php
                    foreach ($m['source'] as $s_line => $s_source) {
                        printf('<span class="line">%s</span>&nbsp;&nbsp;%s', $s_line, $s_source);
                    }
                    ?>
                </div>
            </div>
        <?php endforeach ?>
    <?php endif ?>
    <?php if (!empty($message['previous_trace'])) : ?>
        <?php foreach ($message['previous_trace'] as $pk => $pm) : ?>
            <div id="previous_trace_info_<?php echo $pk ?>" class="trace_info_div" style="display:none">
                <div class="code-block"></div>
                <div class="code-segment">
                    <?php
                    foreach ($pm['source'] as $s_line => $s_source) {
                        printf('<span class="line">%s</span>&nbsp;&nbsp;%s', $s_line, $s_source);
                    }
                    ?>
                </div>
            </div>
        <?php endforeach ?>
    <?php endif ?>

    <div id="main_info" style="display:block">
        <div class="code-block"></div>
        <div class="code-segment">
            <?php
            foreach ($message['main']['source'] as $s_line => $s_source) {
                printf('<span class="line">%s</span>&nbsp;&nbsp;%s', $s_line, $s_source);
            }
            ?>
        </div>
    </div>
</div>

<div class="details">
    <h1>Exception File</h1>
    <div class="frame active" id="frame_active"
         onclick="cp_exception.main(null)"><?php echo $message["main"]["show_file"] ?></div>
    <?php if (!empty($message['trace'])) : ?>
        <h1>Trace</h1>
        <?php foreach ($message['trace'] as $kf => $mf) : ?>
            <div class="frame" onclick="cp_exception.track(<?php echo $kf ?>, this, '')">
                <?php echo $mf['show_file'] ?> Line: <?php echo $mf['line'] ?>
            </div>
        <?php endforeach ?>
    <?php endif ?>
    <?php if (!empty($message['previous_trace'])) : ?>
        <h1>Previous Trace</h1>
        <?php foreach ($message['previous_trace'] as $pkf => $pmf) : ?>
            <div class="frame" onclick="cp_exception.track(<?php echo $pkf ?>, this, 'previous')">
                <?php echo $pmf['show_file'] ?> Line: <?php echo $pmf['line'] ?>
            </div>
        <?php endforeach ?>
    <?php endif ?>
</div>

<div class="data-table-container" id="data-tables">
    <div class="data-table" id="sg-get-data">
        <label>GET</label>
        <?php if (!empty($_GET)) : ?>
            <table class="data-table">
                <thead>
                <tr>
                    <td class="data-table-k">Key</td>
                    <td class="data-table-v">Value</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($_GET as $g_key => $g_value) : ?>
                    <tr>
                        <td><?php echo $g_key ?></td>
                        <td><?php echo var_export($g_value, true) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php else : ?>
            <span class="empty">empty</span>
        <?php endif ?>
    </div>

    <div class="data-table" id="sg-post-data">
        <label>POST</label>
        <?php if (!empty($_POST)) : ?>
            <table class="data-table">
                <thead>
                <tr>
                    <td class="data-table-k">Key</td>
                    <td class="data-table-v">Value</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($_POST as $p_key => $p_value) : ?>
                    <tr>
                        <td><?php echo $p_key ?></td>
                        <td><?php echo var_export($p_value, true) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php else : ?>
            <span class="empty">empty</span>
        <?php endif ?>
    </div>

    <div class="data-table" id="sg-files">
        <label>Files</label>
        <?php if (!empty($_FILES)) : ?>
            <table class="data-table">
                <thead>
                <tr>
                    <td class="data-table-k">Key</td>
                    <td class="data-table-v">Value</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($_FILES as $f_key => $f_value) : ?>
                    <tr>
                        <td><?php echo $f_key ?></td>
                        <td><?php echo var_export($f_value, true) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php else : ?>
            <span class="empty">empty</span>
        <?php endif ?>
    </div>

    <div class="data-table" id="sg-cookies">
        <label>Cookies</label>

        <?php if (!empty($_COOKIE)) : ?>
            <table class="data-table">
                <thead>
                <tr>
                    <td class="data-table-k">Key</td>
                    <td class="data-table-v">Value</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($_COOKIE as $c_key => $c_value) : ?>
                    <tr>
                        <td><?php echo $c_key ?></td>
                        <td><?php echo var_export($c_value, true) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php else : ?>
            <span class="empty">empty</span>
        <?php endif ?>
    </div>

    <div class="data-table" id="sg-session">
        <label>Session</label>
        <?php if (!empty($_SESSION)) : ?>
            <table class="data-table">
                <thead>
                <tr>
                    <td class="data-table-k">Key</td>
                    <td class="data-table-v">Value</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($_SESSION as $s_key => $s_value) : ?>
                    <tr>
                        <td><?php echo $s_key ?></td>
                        <td><?php echo var_export($s_value, true) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php else : ?>
            <span class="empty">empty</span>
        <?php endif ?>
    </div>
</div>
</div>
</div>
<div class="container footer">
    <?php printf('<a href="//www.crossphp.com" target="_blank">CrossPHP version %s</a>', Cross\Core\Delegate::getVersion()) ?>
</div>
<script type="text/javascript">
    cp_exception = {
        main: function (action) {
            var act = action == 'hide' ? 'none' : 'block';
            this.setAct();
            document.getElementById('main_info').style.display = act;
            document.getElementById('frame_active').className = 'frame active';
            if (act != 'none') {
                this.hide_track();
            }
        },
        track: function (id, o, p) {
            var tid = p?p+'_trace_info_'+id:'trace_info_'+id;
            this.display(tid);
            o.className = 'frame active';
        },
        setAct: function () {
            var a_list = document.querySelectorAll('.frame.active');
            for (var i = 0; i < a_list.length; i++) {
                a_list[i].className = 'frame';
            }
        },
        hide_track: function () {
            var t_list = document.querySelectorAll('.trace_info_div');
            for (var i = 0; i < t_list.length; i++) {
                t_list[i].style.display = 'none';
            }
        },
        display: function (tid) {
            this.main('hide');
            this.hide_track();

            document.getElementById(tid).style.display = 'block';
            this.setAct();
        }
    };
</script>
</body>
</html>
