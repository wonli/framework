var emailNull = "请输入您的 email地址";
var emailFormatError = "邮箱格式输入不正确";
var pwdNull = "请输入密码";
var pwdLess6 = "密码不能少于六位";
var pwdNotSame = "密码不一致，请重新输入";
var nicknameNull = "给自己起个个性的名字吧";
var nicknameLess3 = "昵称不能少于三位字符";
var addressOver = "地址请不要超过50个汉字或100个字符";
//验证邮箱正确性
function emailCheck(){
	var email = $.trim($("#email").val());
	if(email.length == 0){
		$("#error_email").empty().text(emailNull);
		$("#email").focus();
		return false;
	}
	var pattern = /^([a-zA-Z0-9._-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/;
	if(!pattern.test(email)){
		$("#error_email").empty().text(emailFormatError);
		$("#email").focus();
		return false;
	}
	$("#error_email").empty();
	return true;
}
//验证原密码是否为空
function oldPasswordCheck(){
	var pwd = $.trim($("#oldPassword").val());
	alert(pwd);
	if(pwd.length == 0){
		$("#error_oldPassword").empty().text(pwdNull);
		$("#oldPassword").focus();
		return false;
	}
	if(pwd.length < 6){
		$("#error_oldPassword").empty().text(pwdLess6);
		$("#oldPassword").focus();
		return false;
	}
	$("#error_oldPassword").empty();
	return true;
}
//验证密码是否为空
function passwordCheck(){
	var pwd = $.trim($("#password").val());
	if(pwd.length == 0){
		$("#error_password").empty().text(pwdNull);
		$("#password").focus();
		return false;
	}
	if(pwd.length < 6){
		$("#error_password").empty().text(pwdLess6);
		$("#password").focus();
		return false;
	}
	$("#error_password").empty();
	return true;
}
//验证确认密码是否为空
function surePasswordCheck(){
	var pwd = $.trim($("#surePassword").val());
	if(pwd.length == 0){
		$("#error_surePassword").empty().text(pwdNull);
		$("#surePassword").focus();
		return false;
	}
	$("#error_surePassword").empty();
	return true;
}
//验证密码是否一致
function passwordSameCheck(){
	var pwd = $.trim($("#password").val());
	var surePwd = $.trim($("#surePassword").val());
	
	if(pwd != surePwd){
		$("#error_password").empty().text(pwdNotSame);
		$("#password").focus();
		return false;
	}
	
	return true;
}
//昵称验证
function nicknameCheck(){
	var nickname = $.trim($("#nickname").val());
	if(nickname.length == 0){
		$("#error_nickname").empty().text(nicknameNull);
		$("#nickname").focus();
		return false;
	}
	if(nickname.length < 3){
		$("#error_nickname").empty().text(nicknameLess3);
		$("#nickname").focus();
		return false;
	}
	$("#error_nickname").empty();
	return true;
}
function addressCheck(){
	var address = $.trim($("#address").val());
	if(address.length > 100){
		$("#error_address").empty().text(addressOver);
		$("#address").focus();
		return false;
	}
	$("#error_address").empty();
	return true;
}
function siteCheck(){
	var site = $.trim($("#site").val());
	if(site.length > 100){
		$("#error_site").empty().text(addressOver);
		$("#site").focus();
		return false;
	}
	$("#error_site").empty();
	return true;
}
function signatreCheck(){
	var signature = $.trim($("#signature").val());
	if(signature.length > 100){
		$("#error_signature").empty().text(addressOver);
		$("#signature").focus();
		return false;
	}
	$("#error_signature").empty();
	return true;
}


function search(){
	if($("#searchtext").val().trim()!=""){
		if(!document.getElementById("searchid")){
			var _form=document.createElement("form");
			_form.action="search";
			_form.method="post";
			_form.id="searchid";
			var _input=document.createElement("input");
			_input.name="keyword";
			_input.id="keyword";
			_form.appendChild(_input);
			document.body.appendChild(_form);
		}
		$("#keyword").val($("#searchtext").val().trim());
		$("#searchid").submit();
	}
}
(function($){
	/**
	 * add function namespace
	 * @param {Object} namespaces
	 * @param {Object} objects
	 */
	$.namespace = function(namespaces, objects){
		var o, nss, snss;
		nss = namespaces.split('.');
		snss = "window";
		$.each(nss, function(i, n){
			snss += "['"+n+"']";
			o = window[nss[0]] = window[nss[0]] || {};
			$.each(nss.slice(1), function(j, m){
				o = o[m] = o[m] || {};
			});
		});
		if(typeof objects == "function"){
			objects();
		}else {
			eval(snss +"=objects");
		}
	},
	$.initBrowser = function(){
		var Browser = {},
		
		ua = navigator.userAgent.toLowerCase(),
	    check = function(r){
	        return r.test(ua);
	    },
		uaMatch = function() {
			var matchs = /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
				/(opera)(?:.*version)?[ \/]([\w.]+)/.exec( ua ) ||
				/(msie) ([\w.]+)/.exec( ua ) ||
				!/compatible/.test( ua ) && /(mozilla)(?:.*? rv:([\w.]+))?/.exec( ua ) ||
			  	[];
			var name = (ua.match(/\b(chrome|opera|safari|msie|firefox)\b/) || ['','mozilla'])[1];
			var r = '(?:' + name + '|version)[\\/: ]([\\d.]+)';
			var version = (ua.match(new RegExp(r)) ||[])[1];
			
			return { Browser : matchs[1] || "",
					 KernelVersion : matchs[2] || "0",
					 Name : name,
					 Version : version
					};
		},
	    DOC = document,
	    isStrict = DOC.compatMode == "CSS1Compat",
	    isOpera = check(/opera/), //opera
	    isChrome = check(/\bchrome\b/), //chrome
	    isWebKit = check(/webkit/),  //webkit
	    isSafari = !isChrome && check(/safari/),  //safari
	    isSafari2 = isSafari && check(/applewebkit\/4/), // unique to Safari 2
	    isSafari3 = isSafari && check(/version\/3/),
	    isSafari4 = isSafari && check(/version\/4/),
	    isIE = !isOpera && check(/msie/),  //IE
	    isIE7 = isIE && check(/msie 7/),   
	    isIE8 = isIE && check(/msie 8/),
	    isIE6 = isIE && !isIE7 && !isIE8,
	    isGecko = !isWebKit && check(/gecko/), //firefox
	    isGecko2 = isGecko && check(/rv:1\.8/),
	    isGecko3 = isGecko && check(/rv:1\.9/),
	    isBorderBox = isIE && !isStrict,
	    isWindows = check(/windows|win32/),
	    isMac = check(/macintosh|mac os x/),
	    isAir = check(/adobeair/),       //adobe air
	    isLinux = check(/linux/),
	    isSecure = /^https/i.test(window.location.protocol);
		
	    // remove css image flicker
	    if(isIE6){
	        try{
	            DOC.execCommand("BackgroundImageCache", false, true);
	        }catch(e){}
	    }
		
		var browserMatch = uaMatch();
		if ( browserMatch.Browser ) {
			Browser[ browserMatch.Browser ] = true;
			Browser.KernelVersion = browserMatch.KernelVersion;
			Browser.Name = browserMatch.Name;
			Browser.Version = browserMatch.Version;
			Browser[browserMatch.Name] = true;
		}
		
		// Deprecated, use jQuery.browser.webkit instead
		if ( Browser.webkit ) {
			Browser.safari = true;
		}
		return Browser;
	};

})(jQuery);
/**
* 时间对象的格式化;
*/
Date.prototype.format = function(format){
 /*
  * eg:format="YYYY-MM-dd hh:mm:ss";
  */
 var o = {
  "M+" :  this.getMonth()+1,  //month
  "d+" :  this.getDate(),     //day
  "h+" :  this.getHours(),    //hour
      "m+" :  this.getMinutes(),  //minute
      "s+" :  this.getSeconds(), //second
      "q+" :  Math.floor((this.getMonth()+3)/3),  //quarter
      "S"  :  this.getMilliseconds() //millisecond
   }
 
   if(/(y+)/.test(format)) {
    format = format.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
   }
 
   for(var k in o) {
    if(new RegExp("("+ k +")").test(format)) {
      format = format.replace(RegExp.$1, RegExp.$1.length==1 ? o[k] : ("00"+ o[k]).substr((""+ o[k]).length));
    }
   }
 return format;
}
var utils={
		formatdate:function(date){
			var currentDate=new Date();
			var currentstr=currentDate.format("yyyy-MM-dd");
			if(date.indexOf(currentstr)!=-1){
				date=date.replace(currentstr+" ","");
				var datetimeseconds=parseInt(date.substring(0,2),10)*3600+parseInt(date.substring(3,5),10)*60+parseInt(date.substring(6,8),10);
				var currentHourStr=currentDate.format("hh:mm:ss");
				var currenttimeseconds=parseInt(currentHourStr.substring(0,2),10)*3600+parseInt(currentHourStr.substring(3,5),10)*60+parseInt(currentHourStr.substring(6,8),10);
				var betweensecond=currenttimeseconds-datetimeseconds;
				var str="";
				if(betweensecond<60){
					str=betweensecond+"秒前";
				}else if(betweensecond>=60&&betweensecond<3600){
					str=parseInt(betweensecond/60,10)+"分钟前";
				}else if(betweensecond>=3600){
					str=parseInt(betweensecond/3600,10)+"小时前";
				}
				return str;
			}else{
				return date;
			}
		},
	toplink:function(){
		$('#t_l').topLink({
			min: 400,
			fadeSpeed: 500
		});
		//smoothscroll
		$('#t_l').click(function(e) {
			e.preventDefault();
			$.scrollTo(0,300);
		});
	},
	transform:function(data){
		return data.replace(new RegExp("&_quot;","gm"),"\"").replace(new RegExp("&acute;","gm"),"\'");
	},
	fDomAutoLink:function(oEle, oDocument)
	{
	 if(!oDocument){
		 oDocument = document; 
	 }
	 if(!sTarget){
		 var sTarget = "_blank"; 
	 }
	 var oNode, sStr = '', oSpan; 
	 for(var i = 0, j = oEle.childNodes.length; i < j; i++ )
	 {
	  oNode = oEle.childNodes[i];  
	  if(oNode.nodeType == 3)
	  {
	   if(oNode.parentNode.nodeName == 'A')return false;   
	   if(oNode.data.indexOf('http') < 0 && oNode.data.indexOf('ftp') < 0 &&
	   oNode.data.indexOf('@') < 0)continue;
	   oSpan = oDocument.createElement('span');
	   sStr = oNode.data.replace(/\&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	   sStr = sStr.replace
	    (/(ht|f)tp(s|)\:\/\/[\-\w.:]+(\/[^ \n\r\'\"\[\]]+|)/gi,
	     function(match)
	     {
	      return ['<a rel="nofollow" href="'+match+'" target="' + sTarget + '">',match,'</a>'].join('');
	     } 
	    );   
	   oSpan.innerHTML = sStr;
	   oEle.replaceChild(oSpan, oNode);
	  }  
	  if(oNode.nodeType == 1)arguments.callee(oEle.childNodes[i], oDocument);
	 }
	 oNode = oSpan = null;
	}
};
jQuery.fn.topLink = function(settings) {
	settings = jQuery.extend({
		min: 1,
		fadeSpeed: 200,
		ieOffset: 50
	}, settings);
	return this.each(function() {
		//listen for scroll
		var el = $(this);
		el.css('display','none'); //in case the user forgot
		$(window).scroll(function() {
			if(!jQuery.support.hrefNormalized) {
				el.css({
					'position': 'absolute',
					'top': $(window).scrollTop() + $(window).height() - settings.ieOffset
				});
			}
			if($(window).scrollTop() >= settings.min)
			{
				el.fadeIn(settings.fadeSpeed);
			}
			else
			{
				el.fadeOut(settings.fadeSpeed);
			}
		});
	});
};