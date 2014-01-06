<!doctype html>
<html lang="zh-ch">
<head>
    <meta charset="UTF-8">
    <title>Install</title>
    <link rel="stylesheet" href="<?php echo $this->res("css/pure-min.css") ?>"/>
    <link rel="stylesheet" href="<?php echo $this->res("css/pure-mine-blue.css") ?>"/>
    <script src="<?php echo $this->res("js/jquery-1.8.3.min.js") ?>"></script>
    <script>var SITE_URL="<?php echo SITE_URL ?>";</script>
</head>
<body>
    <div class="pure-g-r pure-skin-mine" style="text-align: center">
        <div class="pure-u-1">
            <?= isset($content)?$content:'' ?>
        </div>
    </div>
</body>
</html>
