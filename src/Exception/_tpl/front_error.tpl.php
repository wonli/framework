<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
    <title>CrossPHP Framework Exception</title>
    <style type="text/css">
    body { font: 14px helvetica, arial, sans-serif; color: #2B2B2B; background-color: #D4D4D4; padding:0; margin: 0; max-height: 100%; }
    a { text-decoration: none; }
    header { padding: 30px 20px; color: white; background: #272727; box-sizing: border-box; border-top: 5px solid #CD3F3F; }
    .container{ height: 100%; width: 100%; margin: 0; padding: 0; left: 0; top: 0; }
    .exc-title { margin: 0; color: #616161; text-shadow: 0 1px 2px rgba(0, 0, 0, .1); }
    .exc-message { font-size: 32px; margin: 5px 0; word-wrap: break-word; }
    .stack-container { height: 100%; position: relative; }
    .details {padding: 10px;}
    .frame { padding: 14px; background: #F3F3F3; border-right: 1px solid rgba(0, 0, 0, .2); cursor: pointer; }
    .frame.active { background-color: #4288CE; color: #F3F3F3; box-shadow: inset -2px 0 0 rgba(255, 255, 255, .1); text-shadow: 0 1px 0 rgba(0, 0, 0, .2); }
    .frame:not(.active):hover { background: #BEE9EA; }
    .frame-file { font-family: consolas, monospace; word-wrap:break-word; }
    .frame-code .frame-file { background: #C6C6C6; color: #525252; text-shadow: 0 1px 0 #E7E7E7; padding: 10px 10px 5px 10px;  border-top-right-radius: 6px; border-top-left-radius:  6px;  border: 1px solid rgba(0, 0, 0, .1); border-bottom: none; box-shadow: inset 0 1px 0 #DADADA; }
    .code-block { padding: 10px; margin: 0; box-shadow: inset 0 0 6px rgba(0, 0, 0, .3); }
    .frame-comments { box-shadow: inset 0 0 6px rgba(0, 0, 0, .3); border: 1px solid rgba(0, 0, 0, .2); border-top: none;  padding: 10px; font-size: 12px; background: #404040; }
    .frame-comments.empty { padding: 8px 15px; }
    .frame-comments.empty:before { content: "No comments for this stack frame."; font-style: italic; color: #828282; }
    .frame-comment a { color: #BEE9EA; font-weight: bold; text-decoration: none; }
    .frame-comment a:hover { color: #4bb1b1; }
    .data-table-container {padding:10px;}
    .data-table-container label { font-size: 16px; font-weight: bold; color: #4288CE; margin: 10px 0; padding: 10px 0;  display: block; border-bottom: 1px dotted rgba(0, 0, 0, .2); }
    .data-table { width: 100%; margin: 10px 0; }
    .data-table tbody { font: 13px consolas, monospace; }
    .data-table thead { display: none; }  .data-table tr { padding: 5px 0; }
    .data-table td:first-child { width: 20%; min-width: 130px; overflow: hidden; font-weight: bold; color: #463C54; padding-right: 5px;  }
    .data-table td:last-child { width: 80%; -ms-word-break: break-all; word-break: break-all; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; }
    .data-table .empty { color: rgba(0, 0, 0, .3); font-style: italic; }
    .exception{ font-family: "microsoft yahei",serif; }
    .line{border-right:1px solid #bbb;position: absolute;padding-right:5px;margin-right: 20px;left: 3px;width: 30px;text-align: right}
    </style>
</head>

<body>
<div class="container">

<div class="stack-container">
<header>
    <div class="exception">
        <h3 class="exc-title">
            <?php printf('File: %s Line: %s', $message['main']['show_file'], $message['main']['line'] ); ?>
        </h3>

        <p class="exc-message">
            <?php echo $message['main']['message'] ?>
        </p>
    </div>
</header>

<div class="frame-code-container">
    <?php if (!empty($message['trace'])) : ?>
        <?php foreach ($message['trace'] as $k => $m) : ?>
            <div id="trace_info_<?php echo $k ?>" class="trace_info_div" style="display:none">
                <div style="background: #333;" class="code-block frame-file"></div>
                <div style="margin:10px;padding-left:30px;">
                    <?php
                    foreach ($m['source'] as $s_line => $s_source) {
                        printf('<span class="line">%s</span>&nbsp;&nbsp;%s', $s_line, $s_source);
                    }
                    ?>
                </div>
                <div class="frame-comments"></div>
            </div>
        <?php endforeach ?>
    <?php endif ?>

    <div id="main_info" style="display:block">
        <div class="frame-file"></div>
        <div class="code-block" style="background: #333;"></div>
        <div style="margin:10px;padding-left:30px;">
            <?php
            foreach ($message['main']['source'] as $s_line => $s_source) {
                printf('<span class="line">%s</span>&nbsp;&nbsp;%s', $s_line, $s_source);
            }
            ?>
        </div>
        <div class="frame-comments"></div>
    </div>
</div>

<div class="details">
    <label></label>

    <div>
        <h1>Exception File</h1>
        <div class="frame active" id="frame_active"
             onclick="cp_exception.main(null)"><?php echo $message["main"]["show_file"] ?></div>
        <?php if (!empty($message['trace'])) : ?>
            <h1>Trace</h1>
            <?php foreach ($message['trace'] as $k => $m) : ?>
                <div class="frame" onclick="cp_exception.track(<?php echo $k ?>, this)" style="display:block">
                    <?php echo $m['show_file'] ?> Line: <?php echo $m['line'] ?>
                </div>
            <?php endforeach ?>
        <?php endif ?>
    </div>
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
                <?php foreach ($_GET as $c_key => $c_value) : ?>
                    <tr>
                        <td><?php echo $c_key ?></td>
                        <td><?php echo $c_value ?></td>
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
                <?php foreach ($_POST as $c_key => $c_value) : ?>
                    <tr>
                        <td><?php echo $c_key ?></td>
                        <td><?php echo $c_value ?></td>
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
                <?php foreach ($_FILES as $c_key => $c_value) : ?>
                    <tr>
                        <td><?php echo $c_key ?></td>
                        <td><?php echo $c_value ?></td>
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
                        <td><?php echo $c_value ?></td>
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
                        <td><?php echo $s_value ?></td>
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

<script type="text/javascript">
    cp_exception = {
        main: function (action) {
            var act = action == 'hide' ? 'none' : 'block';
            this.setAct();
            document.getElementById("main_info").style.display = act;
            document.getElementById("frame_active").className = "frame active";
            if (act != 'none') {
                this.hide_track();
            }
        },
        track: function (id, o) {
            var tid = "trace_info_" + id;
            this.display(tid);
            o.className = "frame active";
        },
        setAct: function () {
            var a_list = document.getElementsByClassName("frame active");
            for (var i = 0; i < a_list.length; i++) {
                a_list[i].className = 'frame';
            }
        },
        hide_track: function () {
            var t_list = document.getElementsByClassName("trace_info_div");
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
