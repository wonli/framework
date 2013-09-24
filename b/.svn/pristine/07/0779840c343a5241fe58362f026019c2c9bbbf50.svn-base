function JsPostBack(postStr,ob,f,loading)
{
    var url='http://127.0.0.1:922/my/register';
    var request=false;
    var obj=ob;fun=f;
    
    if(window.XMLHttpRequest) {
        request=new XMLHttpRequest();
        if(request.overrideMimeType) 
        {
            request.overrideMimeType('text/xml');
        }
    } else if(window.ActiveXObject) {
        var versions=['Microsoft.XMLHTTP','MSXML.XMLHTTP','Microsoft.XMLHTTP','Msxml2.XMLHTTP.7.0','Msxml2.XMLHTTP.6.0','Msxml2.XMLHTTP.5.0','Msxml2.XMLHTTP.4.0','MSXML2.XMLHTTP.3.0','MSXML2.XMLHTTP'];
        for(var i=0,icount=versions.length;i<icount;i++) { 
            try {
                request=new ActiveXObject(versions[i]);
            }catch(e){}
        }
    }

    if(!request) {
        window.alert("XMLHttpRequest Construct Error");
        return false;
    }
    
    if(loading==1) {
        setTimeout(function(){showloading1(request,obj)},500);
    }
    else if(loading==2) {
        setTimeout(function(){showloading2(request,obj)},500);
    }
    else {
        setTimeout(function(){showloading(request,obj)},500);
    }

    request.onreadystatechange=function(){
        processHandle(request,obj,f);
    };
    request.open("POST",url,true);
    request.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    request.send(postStr);
}

function processHandle(request,obj,f){
    if(request.readyState==4 && request.status==200){
        var responseText=request.responseText;
        if(obj!=null){
            document.getElementById(obj).innerHTML=responseText;
            evalScript(responseText);
        }
        if(f) {
            f(responseText);
        }
    }
}

function getScript(str) {
    var matchAll=new RegExp('(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)','img');
    var matchOne=new RegExp('(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)','im');
    var a=str.match(matchAll)||[];
    var result=[];
    
    for(var i=0;i<a.length;i++){
        result.push((a[i].match(matchOne)||[' ',' '])[1]);
    }
    return result;
}

function evalScript(str) {
    var scripts=getScript(str);
    for(var i=0;i<scripts.length;i++){
        eval(scripts[i]);
    }
}

function showloading(request,obj) {
    if(request.readyState!=4||request.status!=200) {
        if(obj!=null){
            document.getElementById(obj).innerHTML='<div style="text-align:center"><img src="/images/loading.gif" /></div>';
        }
    }
}

function showloading1(request,obj) {
    if(request.readyState!=4||request.status!=200){
        if(obj!=null){}
    }
}

function showloading2(request,obj){
    if(request.readyState!=4||request.status!=200) {
        if(obj!=null) {
            document.getElementById(obj).innerHTML='<div style="margin:0 auto; text-align:center"><img src="/images/loading.gif" /></div>';
        }
    }
}
//xframe
(function($)
{
    var X=null;
    $xframer=function() 
    {
        OnMessage=function(obj) {
            if(!obj)return;
            $(this).trigger("onMessage",obj);
        };
        
        GetResult=function(code) {
            return code;
        };
        
        DoMessage=function(code) {
            if(code==null)return;
            if(!window.parent)return;
            window.parent.$xframer().OnMessage($.parseJSON(GetResult(code)));
        };
        
        Register=function(func) {
            if(!func)return;
            $(this).bind("onMessage",func);
        };
        
        if(X==null) {
            X=this;
        }
        return X;
    };
    
    $xframer().Register(function(e,o){
        if(o.Type==1) {
            alert(o.Tag);
        };
    });
    
    $xframer().Register(function(e,o){
        if(o.Type==2){ 
            window.location.href=window.location.href;
        };
    });
    
    $xframer().Register(function(e,o){
        if(o.Type==3){
            window.location.href=o.Tag;};
    });
    
    $xframer().Register(function(e,o){
        if(o.Type==4){
            eval(o.Tag);
        };
    });
    
    $xframer().Register(function(e,o){
        if(o.Type==5){
            eval(o.Tag);
        };
    });
})(jQuery);























