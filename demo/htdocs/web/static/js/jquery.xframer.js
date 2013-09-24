//xframer
(function($)
{var X=null;$xframer=function()
{OnMessage=function(obj){if(!obj)return;$(this).trigger("onMessage",obj);};GetResult=function(code){return code;};DoMessage=function(code){if(code==null)return;if(!window.parent)return;window.parent.$xframer().OnMessage($.parseJSON(GetResult(code)));};Register=function(func){if(!func)return;$(this).bind("onMessage",func);};if(X==null){X=this;}
return X;};$xframer().Register(function(e,o){if(o.Type==1){alert(o.Tag);};});$xframer().Register(function(e,o){if(o.Type==2){var url=window.location.href;window.location.href=url.split("#")[0];};});$xframer().Register(function(e,o){if(o.Type==3){window.location.href=o.Tag;};});$xframer().Register(function(e,o){if(o.Type==4){eval(o.Tag);};});$xframer().Register(function(e,o){if(o.Type==5){eval(o.Tag);};});})(jQuery);
//ajax
function JsPostBack(postStr,ob,f,loading)
{var url='/jsrequest.php';var request=false;var obj=ob;fun=f;if(window.XMLHttpRequest){request=new XMLHttpRequest();if(request.overrideMimeType){request.overrideMimeType('text/xml');}}else if(window.ActiveXObject){var versions=['Microsoft.XMLHTTP','MSXML.XMLHTTP','Microsoft.XMLHTTP','Msxml2.XMLHTTP.7.0','Msxml2.XMLHTTP.6.0','Msxml2.XMLHTTP.5.0','Msxml2.XMLHTTP.4.0','MSXML2.XMLHTTP.3.0','MSXML2.XMLHTTP'];for(var i=0,icount=versions.length;i<icount;i++){try{request=new ActiveXObject(versions[i]);}catch(e){}}}
if(!request){window.alert("XMLHttpRequest Construct Error");return false;}
if(loading==1)
{}
else if(loading==2)
{setTimeout(function(){showloading2(request,obj)},500);}
else
{setTimeout(function(){showloading(request,obj)},500);}
request.onreadystatechange=function(){processHandle(request,obj,f);};request.open("POST",url,true);request.setRequestHeader("Content-Type","application/x-www-form-urlencoded");request.send(postStr);}
function getScript(str)
{var matchAll=new RegExp('(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)','img');var matchOne=new RegExp('(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)','im');var a=str.match(matchAll)||[];var result=[];for(var i=0;i<a.length;i++)
{result.push((a[i].match(matchOne)||[' ',' '])[1]);}
return result;}
function evalScript(str)
{var scripts=getScript(str);for(var i=0;i<scripts.length;i++)
{eval(scripts[i]);}}
function processHandle(request,obj,f){if(request.readyState==4&&request.status==200){var responseText=request.responseText;if(obj!=null)
{document.getElementById(obj).innerHTML=responseText;evalScript(responseText);}
if(f)
{f(responseText);}}}
function showloading(request,obj)
{if(request.readyState!=4||request.status!=200){if(obj!=null)
{document.getElementById(obj).innerHTML='<div style="text-align:center"><img src="/images/loading.gif" /></div>';}}}
function showloading1(request,obj)
{if(request.readyState!=4||request.status!=200){if(obj!=null)
{}}}
function showloading2(request,obj)
{if(request.readyState!=4||request.status!=200){if(obj!=null)
{document.getElementById(obj).innerHTML='<div style="margin:0 auto; text-align:center"><img src="/images/loading.gif" /></div>';}}}
//common
//修改编辑框地址
function seteditorsrc(obj,src)
{
	document.getElementById(obj).src=src;
}
//渐隐提示框
popbox=new function()
{
	var popid="globalpopdiv";
	this.Init=function()
	{
		if($('#'+popid).length<1)
		{
			$('body').append('<div id="'+popid+'" ></div>');
		}
		else
		{
			$('#'+popid).empty();
		}
	}
	this.fadeInOut=function(t,c)
	{
		this.Init();
		$("#"+popid).append('<span class="success_left"></span>');
		if(t==1)//成功
		{
			$("#"+popid).append('<span class="success_center"><img src="'+STATIC_URL+'images/success.png" />'+c+'</span>');
		}
		else if(t==2)//错误
		{
			$("#"+popid).append('<span class="success_center"><img src="'+STATIC_URL+'images/failure.png" />'+c+'</span>');
		}
		$("#"+popid).append('<span class="success_right"></span>');
		setfadestyle();
		showalertbox();
	}
	this.login=function()
	{
		setloginstyle();
		JsPostBack("a=Login",popid);
		popeffect.show('#'+popid);
	}
    this.buy=function(id,username)
    {
        setclass('buy');
        JsPostBack("a=Buy&username="+username+"&id="+id,popid);
        popeffect.show('#'+popid);
    }    
    this.comment=function(did)
    {
		setclass('comment');
		JsPostBack("a=Comment&did="+did,popid);
        popeffect.show('#'+popid);
    }
	//纠错
	this.correct=function(did,key)
	{
		setloginstyle();
		JsPostBack("a=Correct&did="+did+'&k='+key,popid);
		popeffect.show('#'+popid);
	}
	//注册
	this.register=function()
	{
		setclass('pop');
		JsPostBack("a=Register",popid);
		popeffect.show('#'+popid);
	}
	this.dialog=function( html )
	{
		setloginstyle();
		$('#'+popid).html(html);
		popeffect.show('#'+popid);
	}
	function mousePosition(e)
	{
		if(!e) var e = window.event;
		var x = e.pageX ||(e.clientX?e.clientX+document.documentElement.scrollLeft:0);
		var y = e.pageY ||(e.clientY?e.clientY+document.documentElement.scrollTop:0);
		return {x:x,y:y};
	}
	function setclass(cl)
	{
		$("#"+popid).attr('class',cl);
	}
	function setloginstyle()
	{
		$("#"+popid).attr('class','pop');
	}
	 function getTotalHeight(){
		if($.browser.msie){
		return document.compatMode == "CSS1Compat"? document.documentElement.clientHeight : document.body.clientHeight;
		}
		else {
		return self.innerHeight;
		}
		}
		
	function getTotalWidth(){
		if($.browser.msie){
		return document.compatMode == "CSS1Compat"? document.documentElement.clientWidth : document.body.clientWidth;
		}
		else{
		return self.innerWidth;
		}
	}
	function setfadestyle()
	{
		var popobj="#"+popid;
		$(popobj).attr('class','success_wai');
		var bodyTop=$(window).scrollTop();
		var popLeft=(getTotalWidth()-$(popobj).outerWidth())/2;
		//var popTop=(getTotalHeight()-$(popobj).outerHeight())/2+bodyTop;
		var popTop=200+bodyTop;
		$(popobj).css({position:"absolute",top:popTop,left:popLeft,"z-index":"999999"});
	}
	function showalertbox()
	{
		$("#"+popid).fadeIn(200);
		setTimeout(hidealertbox, 2000); 
	}
	function hidealertbox()
	{
		$("#"+popid).fadeOut(600);
		$("#"+popid).html('');
		$("#"+popid).attr('class','');
	}
}
var popeffect = {

	show:function(popme){
		$(popme).hide();
		var bodyTop=$(window).scrollTop();		
		$("body").append("<div id='popMask' style='width:100%;background:#000000;position:absolute;top:0px;left:0px;z-index:9999;display:none;'></div>");
		$("#popMask").height($("body").outerHeight()>$(window).height()?$("body").outerHeight():$(window).height());
		$("#popMask").fadeTo(300,.7,function(){
			$("body").append($(popme));
			var popLeft=($("body").outerWidth()-$(popme).outerWidth())/2;
			var popTop=($(window).height()-$(popme).outerHeight())/2+bodyTop;
			$(popme).css({height:$(popme).innerHeight(),position:"absolute",top:popTop,left:popLeft,"z-index":"10000",overflow:"auto"});
			$(popme).fadeIn(300);
		});
		$(window).resize(function(){
			if($("#popMask").length!=0){
				var bodyTop=$(window).scrollTop();
				var popLeft=($("body").outerWidth()-$(popme).outerWidth())/2;
				var popTop=($(window).height()-$(popme).outerHeight())/2+bodyTop;
  				$(popme).css({position:"absolute",top:popTop,left:popLeft,"z-index":"999999"});
			}
		});
	},
	
	hide:function(popme){
		$(popme).fadeOut(300,function(){
			$("#popMask").fadeOut(300,function(){
				$("#popMask").remove();
				$(popme).html('');
				$(popme).css({});
				$(popme).attr('class','');
				$(popme).attr('style','');
			});
		});
	},
	
	remove:function(popme){
		this.hide(popme);
		$(popme).remove();
	}
};
var hashData={
		read:function()
		{
			var hashData=document.location.hash;
			hashData=hashData.slice(1);
			var tempArr=new Array();
			tempArr=hashData.split(",");
			var value=0;
			var object={};
			for(var i=0;i<tempArr.length;i++){
				if(!tempArr[i])
					continue;
				var cutNum=(tempArr[i]).indexOf("=");//参数分割符号
				var menuName=(tempArr[i]).substr(0,cutNum); 
				object[menuName]=(tempArr[i]).substr(cutNum+1);
			}
			return object;
		},
		write:function(a,v)
		{
			var hd=hashData.read();
			var hashStr="#";
			var flag=0;
			for(var attr in hd)
			{
				if(a==attr)
				{
					if(v!=null)
					{
						hashStr=hashStr+attr+"="+v+",";
					}
					flag=1;
				}
				else
				{
					hashStr=hashStr+attr+"="+hd[attr]+",";
				}
			}
			if(flag==0&&v!=null)
			{
				hashStr=hashStr+a+"="+v+",";
			}
			document.location.hash=hashStr;
		}
}
var mchar = {
    count:function(ta, ha, maxlimit){
        var ta = $(ta);
        var ha = $(ha);        
        ta.keydown(function(){
            mchar.textCounter(ta,ha,maxlimit);
        });
        
        ta.keyup(function(){
            mchar.textCounter(ta,ha,maxlimit);
        });
    },
    textCounter:function(ta, ha, max){
        return ta.val().length >max ? $(ta).val( ta.val().substring(0, max) ) : ha.text((max - ta.val().length) + "/" + max);
    }
}
messagebox=new function()
{
	var mbid='messageboxdiv';
	this.sendmessage=function(e,obju)
	{
		if($('#'+mbid).length<1)
		{
			$('body').append('<div id="'+mbid+'"></div>');
		}
		else
		{
			$('#'+mbid).empty();
		}
		JsPostBack('&a=HomeSendMessage&u='+obju,mbid);
		setstyle(e);
	}
	this.addfriend=function(e,obju)
	{
		if($('#'+mbid).length<1)
		{
			$('body').append('<div id="'+mbid+'"></div>');
		}
		JsPostBack('&a=HomeAddFriend&u='+obju,mbid);
		setstyle(e);
	}
	function setstyle(e)
	{
		if(!e) var e = window.event;
		var  target    =  e.target  ||  e.srcElement;
		var x=pageX(target);
		var y=pageY(target)+5;
		$('#'+mbid).css({"display":"inline-block","position":"absolute","left":x+"px",'top':y+"px"});
	}
	function pageX(elem){
		return elem.offsetParent?(elem.offsetLeft+pageX(elem.offsetParent)):elem.offsetLeft;
		}
		
		　　//获取当前的Y坐标值
	function pageY(elem){
		return elem.offsetParent?(elem.offsetTop+pageY(elem.offsetParent)):elem.offsetTop;
		}
}
//倒计时跳转
function RedirectCoundDown(secs,surl){     
document.getElementById('jumpTo').innerHTML=secs;     
	if(--secs>0){     
	 	setTimeout("RedirectCoundDown("+secs+",'"+surl+"')",1000);     
	 }     
	else{       
		 location.href=surl;     
	 }     
} 
function playmp3sound(url)
{
	var playerobj=null; 
	if(navigator.appName.indexOf("Microsoft") != -1) 
	{
		playerobj= document.getElementById("SoundPlayer");
	}else {
		playerobj=document["SoundPlayer1"];
	}
	if(typeof(playerobj)=="undefined")
	{
		playerobj= document.getElementById("SoundPlayer1");
	}
	try{
				playerobj.FLoad(url);
	}catch(error)
	{
		setTimeout("playmp3sound('"+url+"')",300);
	}
} 


/**
* Url编码
**/
function urlencode(unzipStr){
    var zipstr="";
    var strSpecial="!\"#$%&'()*+,/:;<=>?[]^`{|}~%";
    var tt= "";
    for(var i=0;i<unzipStr.length;i++){
        var chr = unzipStr.charAt(i);
        var c=StringToAscii(chr);
        tt += chr+":"+c+"n";
        if(parseInt("0x"+c) > 0x7f)
        {
            zipstr+=encodeURI(unzipStr.substr(i,1));
        }else{
            if(chr==" ")
                zipstr+="+";
            else if(strSpecial.indexOf(chr)!=-1)
                zipstr+="%"+c.toString(16);
            else
            zipstr+=chr;
        }
    }
    return zipstr;
}

/**
* Url解码
**/
function urldecode(zipStr){
    var uzipStr="";
    for(var i=0;i<zipStr.length;i++)
    {
        var chr = zipStr.charAt(i);
        if(chr == "+")
        {
            uzipStr+=" ";
        }else if(chr=="%"){
            var asc = zipStr.substring(i+1,i+3);
            if(parseInt("0x"+asc)>0x7f)
            {
                uzipStr+=decodeURI("%"+asc.toString()+zipStr.substring(i+3,i+9).toString()); ;
                i+=8;
            }else{
                uzipStr+=AsciiToString(parseInt("0x"+asc));
                i+=2;
            }
        }else{
            uzipStr+= chr;
        }
    }
    return uzipStr;
}

function StringToAscii(str){
    return str.charCodeAt(0).toString(16);
}

function AsciiToString(asccode){
    return String.fromCharCode(asccode);
} 

function playsound(w,v) { 
var player=document.getElementById('soundplayer'); 
    player.src='/sound/'+w+'/'+v+'/'; 
    player.play(); 
}