<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-cn">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="<?php echo $static_url ?>css/style.css" media="all" />
    <link rel="stylesheet" type="text/css" href="<?php echo STATIC_URL ?>editor/plugins/code/prettify.css" media="all" />
    <script type="text/javascript" src="<?php echo $static_url ?>js/jquery-1.7.1.min.js"></script>
    <script src="<?php echo STATIC_URL ?>editor/plugins/code/prettify.js" charset="utf-8"></script>    
    <title><?php echo isset($title) ? $title : 'CrossBlog' ?></title>
</head>
<body>
    <div class="wrap">
        <div class="panl">
            <ul>
            	<li>
                    <?php if(isset($_SESSION["u"])) : ?>
                    <a href="<?php echo $this->link("user") ?>">欢迎你: <?php echo $_SESSION["screen_name"] ?></a>
                    &nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php echo $this->link("user:logout") ?>">退出</a>
                    <?php else : ?>
                    <a href="<?php echo $this->link("user:login") ?>">登录</a>
                    <?php endif ?>
                </li>
            </ul>
        </div>
    </div>
    <div class="wrap clear">
        <div class="logo">
            <a href="<?php echo SITE_URL ?>">
                <h1 class="toplogo">ideaa</h1>
            </a>
        </div>
    </div>
    <div class="topnav">
        <div class="wrap nav">
            <a href="<?php echo $this->link() ?>">首页</a>
            <a href="<?php echo $this->link("article") ?>" <?php if(strtolower($this->controller) == 'article') : ?>class="current"<?php endif ?>>日志</a>
        </div>
    </div>
    <div class="wrap content">
        <?php echo $content ?>
    </div>
    <div class="wrap foot clear">
        <span>ideaa 2012<br>Powered by CrossPHP</span>
    </div>   
</body>
</html>