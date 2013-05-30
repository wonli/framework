<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-cn">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <title>crossphp error</title>
    <style type="text/css">
    body{background:#ffffff}
    h1{font-size:60px;}
    .t {margin:0 auto;background:#ffffee;border:1px solid #f1f1f1;padding:1px 150px 50px 150px;}
    </style>
</head>
<body>
    <table>
    	<tr>
    		<td height=100>&nbsp;</td>
    	</tr>
    </table>
    <table class="t">
        <tr>
        	<td>
        		<h1><?php echo $code == 200 ? '出错啦 !' : $code ?></h1>
        	</td>
        </tr>
        <tr>
            <td>
                <?php
                    echo $message ? '<font style="color:#333333;">'.$message.'</font>' : '你所请求的页面不存在,';
                    echo ' ,请检查点此<a href='.Cross::config()->getInit(true)->sys->site_url.'>返回首页</a><br>如有疑问请联系管理员!<br>';
                ?>
            </td>
        </tr>
    </table>
    <table style="margin:0 auto;font-size:12px;">
    	<tr>
    		<td height=10></td>
    	</tr>
    </table>
</body>
</html>