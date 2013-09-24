function IDSOO_FAVE(){};
IDSOO_FAVE.IDSOO_DOMAIN = "http://www.letutao.com";
IDSOO_FAVE.LOCAL_DOMAIN = window.location.host;
IDSOO_FAVE.FEATURES = "height=350,width=700,top=100,left=100,status=no,resizable=no,scrollbars=yes,personalbar=no,directories=no,location=no,toolbar=no,menubar=no";
IDSOO_FAVE.VISIABLE = false;
IDSOO_FAVE.FILTER_REG = /(letutao.com|baidu.com|u148.net|163.com|sohu.com|soso.com)/i;
	
IDSOO_FAVE.toggle = function(){
	if(!IDSOO_FAVE.filterImg()){
		alert("Sorry, this page is not the right picture, please change another.");
		return;
	}
	if(IDSOO_FAVE.VISIABLE){
		IDSOO_FAVE.hide();
	}else{
		IDSOO_FAVE.show();
	}
};

IDSOO_FAVE.filterImg = function(){
	var flag = false;
	$("img").each(function(){
		var imgWidth = $(this).css("width").replace("px", "");
		var imgHeight = $(this).css("height").replace("px", "");
		if(imgWidth > 200 && imgHeight > 250){
			flag = true;
		}
	});
	return flag;
};

IDSOO_FAVE.hide = function(){
	IDSOO_FAVE.visible = false;
	$("#idsoo_overlay").remove();
	$("#idsoo_box").remove();
};

IDSOO_FAVE.show = function(){
	IDSOO_FAVE.VISIABLE = true;
	$("body").append('<div id="idsoo_overlay"></div>');
	$("#idsoo_overlay").append('<link href="' + IDSOO_FAVE.IDSOO_DOMAIN + '/css/post.css" rel="stylesheet" type="text/css" />');
	var $box = $('<div id="idsoo_box"></div>');
	var $close = $('<div id="idsoo_close"></div>');
	$close.append('<img src="' + IDSOO_FAVE.IDSOO_DOMAIN + '/images/logo.gif" alt="IDSOO" /><a href="#" id="tpm_RemoveLink">Close</a>');
	$box.append($close);
	$("body").append($box);
	$("#idsoo_close").click(function(){
		IDSOO_FAVE.hide();
	});
	IDSOO_FAVE.dealImg();
};

IDSOO_FAVE.dealImg = function(){
	$("img").each(function(){
		var imgWidth = $(this).css("width").replace("px", "");
		var imgHeight = $(this).css("height").replace("px", "");
		if(imgWidth > 200 && imgHeight > 250){
			var reg1 = /^http.*$/g;
			var reg2 = /^\/\/.*$/g;
			var imgSrc = $(this).attr("src");
			if(!reg1.test(imgSrc)){
				if(reg2.test(imgSrc)){
					imgSrc = "http:" + imgSrc;
				}else{
					imgSrc = "http://" + IDSOO_FAVE.LOCAL_DOMAIN + "/" + imgSrc;
				}
			}
			var imgurl = "imgUrl=" + imgSrc;
			imgurl.replace(/&/g, "IDSOOAND");
			var pageurl = "pageUrl=" + $.trim(window.location.href).replace(/&/g, "letutao");
			var title = "title=";
			if(IDSOO_FAVE.LOCAL_DOMAIN.indexOf("taobao") >= 0){
				title = "title=" + $.trim($("title").text()).replace(/-淘宝网/g, "").replace(/淘宝网/g, "").replace(/&/g, "");
			}else{
				title = "title=" + $.trim($("title").text()).replace(/&/g, "");
			}
			var random = "random=" + Math.random();
			var productUrl = IDSOO_FAVE.IDSOO_DOMAIN + "/index.php?app=group&ac=Post_topic&groupid=1&" + encodeURI(imgurl + "&" + pageurl + "&" + title + "&" + random);
			
			//创建img_view
			var paddingTop = 0;
			if(imgHeight < imgWidth){
				paddingTop = (200 - imgHeight * 200 / imgWidth) / 2;
			}
			
			var $imgView = $('<div class="idsoo_view"></div>');
			$imgView.append('<span class="img_info">' + imgWidth + ' x ' + imgHeight + '</span>');
			$imgDiv = $('<div class="ids_img"></div>');
			$imgDiv.click(function(){
				window.open(productUrl, "", IDSOO_FAVE.FEATURES);
			});
			
			$('<a href="#"></a>').append('<img class="ids_this" src="' + $(this).attr("src") + '" style="padding-top: ' + paddingTop + 'px" />')
			.append('<img class="ids_btn" src="' + IDSOO_FAVE.IDSOO_DOMAIN + '/images/btn_select.png" alt="收藏这张" />')
			.appendTo($imgDiv);
			$imgView.append($imgDiv);
			$("#idsoo_box").append($imgView);
		}
	});
};

IDSOO_FAVE.init = function(){
	var urlHref = document.location.href;
	var filter = urlHref.match(IDSOO_FAVE.FILTER_REG);
	if(!filter){
		IDSOO_FAVE.toggle();
	}
	else{
		alert("抱歉，暂不支持收藏该站点图片");
	}
};

IDSOO_FAVE.init();
window.scroll(0,0);