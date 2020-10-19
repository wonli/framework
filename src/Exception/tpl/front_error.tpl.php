<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CrossPHP Exception</title>
    <style type="text/css">
        body {font:14px "12px/1.7 -apple-system","微软雅黑",helvetica,arial,sans-serif;color:#111;background-color:#fff;padding:0;margin: 0}
        a {text-decoration:none}
        .padding {padding-left:15px !important;padding-right:15px !important}
        .header {padding-top:25px;padding-bottom:15px;color:#262626;font-weight:400;background:#f0f8ff;border-bottom:1px solid #f1f1f1}
        .container{height:100%;width:100%;margin:0;padding:0;left:0;top:0}
        .title {margin:0;color:#818181}
        .message {font-size:36px;padding:15px 0;word-wrap:break-word}
        .stack-container {height:100%;position:relative;border-top:6px solid #be0000}
        .trace-container {padding:0}
        .frame {padding:15px;background:#fff;cursor:pointer}
        .frame.active {background-color:#3F51B5;color:#fff}
        .frame:not(.active):hover {background:rgba(236, 236, 236, 0.66)}
        .code-container{min-height:325px}
        .code-segment {min-height:325px;background-color:#fdfdfd;border-bottom:1px solid #f1f1f1}
        .code-line span, .code-line code {display:inline-block}
        .code-line code {margin-left:-10px}
        .code-line.active {background-color:#ffd2d2}
        .data-table-container {padding:0}
        .table-content {width:100%}
        .data-table {width:100%; margin:10px 0}
        .data-table label {font-size:16px;font-weight:bold;color:#525252;margin:10px 0;padding:10px 0;display:block;border-bottom:1px dotted #b0b0b0}
        .data-table tbody {font:13px consolas, monospace}
        .data-table thead {display:none} .data-table tr {padding:5px 0}
        .data-table td:first-child {width:30%;min-width:130px;overflow:hidden;font-weight:bold;color:#463C54;padding-right:5px}
        .data-table td:last-child {width:70%;-ms-word-break:break-all;word-break:break-all;-webkit-hyphens:auto;-moz-hyphens:auto;hyphens:auto}
        .data-table .empty {color:rgba(0, 0, 0, .3);font-style:italic}
        .trace-info{display:none}
        .line{border-right:1px solid #d9d9d9;padding-right:10px;width:50px;text-align:right;line-height:25px}
        .footer{text-align:center;height:30px;background: #333;color:#808080;line-height:30px;box-sizing:border-box;border-top:2px solid rgba(0, 0, 0, 0.35)}
        .footer>a{color:#565656}@media (max-width: 768px) {h1{font-size:18px} .code-container {display:none} .title{font-size:14px} .message {font-size:24px}}
    </style>
    <!--[if lt IE 9]><style>.container {min-width:1024px}</style><![endif]-->
</head>

<body>
<div class="container">
    <div class="stack-container">
        <div class="header padding">
            <h3 class="title">
                <?php printf('File: %s Line: %s', $data['main']['show_file'] ?? '', $data['main']['line'] ?? ''); ?>
            </h3>
            <div class="message">
                <?= $data['main']['message'] ?? '' ?>
            </div>
        </div>
        <div class="code-container">
            <?php if (!empty($data['trace'])) : ?>
                <?php foreach ($data['trace'] as $k => $m) : ?>
                    <div id="trace_info_<?= $k ?>" class="trace-info">
                        <div class="code-segment">
                            <?php
                            foreach ($m['source'] as $s_line => $s_source) {
                                if ($m['line'] == $s_line) {
                                    printf('<div class="code-line active"><span class="line">%s</span>&nbsp;&nbsp;%s</div>', $s_line, $s_source);
                                } else {
                                    printf('<div class="code-line"><span class="line">%s</span>&nbsp;&nbsp;%s</div>', $s_line, $s_source);
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
            <?php if (!empty($data['previous_trace'])) : ?>
                <?php foreach ($data['previous_trace'] as $pk => $pm) : ?>
                    <div id="previous_trace_info_<?= $pk ?>" class="trace-info">
                        <div class="code-segment">
                            <?php
                            foreach ($pm['source'] as $s_line => $s_source) {
                                if ($pm['line'] == $s_line) {
                                    printf('<div class="code-line active"><span class="line">%s</span>&nbsp;&nbsp;%s</div>', $s_line, $s_source);
                                } else {
                                    printf('<div class="code-line"><span class="line">%s</span>&nbsp;&nbsp;%s</div>', $s_line, $s_source);
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach ?>
            <?php endif ?>

            <div id="main_info" style="display:block">
                <div class="code-segment">
                    <?php
                    foreach ($data['main']['source'] ?? [] as $s_line => $s_source) {
                        if (!empty($data['main']['line']) && ($data['main']['line'] == $s_line)) {
                            printf('<div class="code-line active"><span class="line">%s</span>&nbsp;&nbsp;%s</div>', $s_line, $s_source);
                        } else {
                            printf('<div class="code-line"><span class="line">%s</span>&nbsp;&nbsp;%s</div>', $s_line, $s_source);
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="trace-container">
            <h1 class="padding">Exception File</h1>
            <div class="frame active" id="frame_active"
                 onclick="cp_exception.main(null)">
                <?= $data["main"]["show_file"] ?? '' ?>
            </div>
            <?php if (!empty($data['trace'])) : ?>
                <h1 class="padding">Trace</h1>
                <?php foreach ($data['trace'] as $kf => $mf) : ?>
                    <div class="frame" onclick="cp_exception.track(<?= $kf ?>, this, '')">
                        <?php printf('%s (line: %s)', $mf['show_file'], $mf['line']) ?>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
            <?php if (!empty($data['previous_trace'])) : ?>
                <h1 class="padding">Previous Trace</h1>
                <?php foreach ($data['previous_trace'] as $pkf => $pmf) : ?>
                    <div class="frame" onclick="cp_exception.track(<?= $pkf ?>, this, 'previous')">
                        <?php printf('%s (line: %s)', $pmf['show_file'], $pmf['line']) ?>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </div>
        <div class="data-table-container" id="data-tables">
            <div class="data-table" id="sg-get-data">
                <label class="padding">GET</label>
                <?php if (!empty($_GET)) : ?>
                    <table class="table-content padding">
                        <thead>
                        <tr>
                            <td class="data-table-k">Key</td>
                            <td class="data-table-v">Value</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($_GET as $gKey => $gValue) : ?>
                            <tr>
                                <td><?= (new \Cross\Interactive\DataFilter($gKey))->val()  ?></td>
                                <td><?= print_r($gValue, true) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <span class="empty padding">-</span>
                <?php endif ?>
            </div>

            <div class="data-table" id="sg-post-data">
                <label class="padding">POST</label>
                <?php if (!empty($_POST)) : ?>
                    <table class="table-content padding">
                        <thead>
                        <tr>
                            <td class="data-table-k">Key</td>
                            <td class="data-table-v">Value</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($_POST as $pKey => $pValue) : ?>
                            <tr>
                                <td><?= (new \Cross\Interactive\DataFilter($pKey))->val() ?></td>
                                <td><?= print_r($pValue, true) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <span class="empty padding">-</span>
                <?php endif ?>
            </div>

            <div class="data-table" id="sg-files">
                <label class="padding">Files</label>
                <?php if (!empty($_FILES)) : ?>
                    <table class="table-content padding">
                        <thead>
                        <tr>
                            <td class="data-table-k">Key</td>
                            <td class="data-table-v">Value</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($_FILES as $fKey => $fValue) : ?>
                            <tr>
                                <td><?= (new \Cross\Interactive\DataFilter($fKey))->val() ?></td>
                                <td><?= print_r($fValue, true) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <span class="empty padding">-</span>
                <?php endif ?>
            </div>

            <div class="data-table" id="sg-cookies">
                <label class="padding">Cookies</label>
                <?php if (!empty($_COOKIE)) : ?>
                    <table class="table-content padding">
                        <thead>
                        <tr>
                            <td class="data-table-k">Key</td>
                            <td class="data-table-v">Value</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($_COOKIE as $cKey => $cValue) : ?>
                            <tr>
                                <td><?= (new \Cross\Interactive\DataFilter($cKey))->val() ?></td>
                                <td><?= print_r($cValue, true) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <span class="empty padding">-</span>
                <?php endif ?>
            </div>

            <div class="data-table" id="sg-session">
                <label class="padding">Session</label>
                <?php if (!empty($_SESSION)) : ?>
                    <table class="table-content padding">
                        <thead>
                        <tr>
                            <td class="data-table-k">Key</td>
                            <td class="data-table-v">Value</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($_SESSION as $sKey => $sValue) : ?>
                            <tr>
                                <td><?= (new \Cross\Interactive\DataFilter($sKey))->val() ?></td>
                                <td><?= print_r($sValue, true) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <span class="empty padding">-</span>
                <?php endif ?>
            </div>
        </div>
        <div class="footer">
            <?php printf('<a href="//www.crossphp.com" target="_blank">CrossPHP version %s</a>', Cross\Core\Delegate::getVersion()) ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    var cp_exception = {
        main: function (action) {
            var act = action === 'hide' ? 'none' : 'block';
            this.setAct();
            document.getElementById('main_info').style.display = act;
            document.getElementById('frame_active').className = 'frame active';
            if (act !== 'none') {
                this.hide_track();
            }
        },
        track: function (id, o, p) {
            var tid = p ? p + '_trace_info_' + id : 'trace_info_' + id;
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
            var t_list = document.querySelectorAll('.trace-info');
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
