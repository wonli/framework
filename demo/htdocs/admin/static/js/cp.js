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

function submit_form(form_id)
{
    $("#"+form_id).submit();
}

$.jheartbeat = {
    options: {delay: 10000},
    beatfunction:  function(){},
    timeoutobj:  {id: -1},

    set: function(options, onbeatfunction) {
        if (this.timeoutobj.id > -1) {
            clearTimeout(this.timeoutobj);
        }
        if (options) {
            $.extend(this.options, options);
        }
        if (onbeatfunction) {
            this.beatfunction = onbeatfunction;
        }

        this.timeoutobj.id = setTimeout("$.jheartbeat.beat();", this.options.delay);
    },

    beat: function() {
        this.timeoutobj.id = setTimeout("$.jheartbeat.beat();", this.options.delay);
        this.beatfunction();
    }
};

function timer(func, interval){
    $.jheartbeat.set({delay: interval}, func);
}

var pop = {
    option:{title:"消息",lock:true,background:'#333',fixed:true, opacity:0.17,id:'global_pop'},
    alert:function(type, msg, time) {
        time = time ? time : 2;
        $.dialog.tips(type, msg, time);
    },
    open:function(uri, title){
        this.option.title=title||"提示";
        $.get(SITE_URL+uri,function(data){
           pop.option.content = data;
           $.dialog(pop.option);
        });
    },
    fopen:function(uri, conf) {
        $.dialog.open(SITE_URL+uri, {
            title:'test',
        })
    },
    display:function(data, title){
        pop.option.title = title||"消息";
        pop.option.content = data;
        $.dialog(pop.option);
    },
    close:function() {
        var list = art.dialog.list;
        for (var i in list) {
            list[i].close();
        };
    }
};










