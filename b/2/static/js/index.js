$.namespace("idsoo.index", function(){
	idsoo.index = function(){
		this.init.apply(this, arguments);
	}
	idsoo.index.prototype = {
		index:1,
		loadnum:20,
		_loading : 0,
		range : 60,             //距下边界长度/单位px
		init : function(){
			var that = this;
			$("#more").click(function(){
				that.writemore();
			});
			utils.toplink();
			that.showmore();
	   },
	   showmore:function(){
		   var that = this;
		   that.formatdate();
		   $(".p_box").each(function(){
			   $(this).children(":last").unbind("click").click(function(){
				   if($(this).attr("href").indexOf("login")<0){
					   if($(this).parent().children(":first").attr("name")=="false"){
						   $.post("fave/"+$(this).attr("id").replace("fave_",""),null,null);
						   $(this).removeClass("fave").addClass("faved");
						   $(this).parent().children(":first").attr("name","true");
					   }else{
						   $.post("unfave/"+$(this).attr("id").replace("fave_",""),null,null);
						   $(this).removeClass("faved").addClass("fave");
						   $(this).parent().children(":first").attr("name","false");
					   }
				   }
			   });
		   });
		   if(that.index==2){
			   that.autoload();
	   		}
		   if(($("#proscount").val()>that.index*that.loadnum)&&that.index==1){
				$("#more").css("display","block");
			}else{
				$("#more").css("display","none");
			}
	   },
	   writemore:function(){
		   var that = this;
		   if($("#proscount").val()>that.index*that.loadnum){
			   $("#ajax").css("display","block");
			   $("#more").css("display","none");
			   $.post('index.php/home/Ajax',{start:that.index*that.loadnum,end:that.loadnum},function(data){
				   that.index+=1;
				 	that.writeHTML(data.replace(new RegExp("\r","gm"),"").replace(new RegExp("\n","gm"),""));
				 	$("#ajax").css("display","none");
				 	that.showmore();
			 	});
		   }
	   },
	   writeHTML:function(data){
		   var that=this;
		   var json=eval("("+data+")");
		   for(var i=0;i<json.length;i++){
			   var tempi=i%4;
			   var str='<li class="node co5">';
			   str+='<div class="p_box">';
				str+='<a href="item/'+json.data[i].topicid+'" name="'+json.data[i].topicid+'" class="thum"><img src="'+json.data[i].topicid+'" /></a>';
				if(json.data[i].topicid=="true"){
					str+='<a class="fave"  id="fave_'+json.data[i].topicid+'" href="javascript:void(0);">收藏</a>';
				}else{
					str+='<a class="fave" href="login">收藏</a>';
				}
				str+='</div>';
				str+='<p class="n_data"><a class="fave" title="'+json.data[i].topicid+'人收藏">'+json.data[i].topicid+'</a><a class="com" href="item/'+json.data[i].topicid+'#comments" title="'+json.data[i].topicid+'条评论">'+json.data[i].topicid+'</a></p>';
				str+='<div class="n_com">';
				str+='<a href="user/'+json.data[i].topicid+'" class="user_p"><img src="'+json.data[i].topicid+'" /></a>';
				str+='<p class="n_u"><strong><a href="user/'+json.data[i].topicid+'">'+utils.transform(json.data[i].topicid)+'</a></strong><br /><span class="date">'+json.data[i].topicid+'</span></p>';
				str+='<h2>'+utils.transform(json.data[i].topicid)+'</h2>';
				str+='</div>';
				if(json.data[i].topicid!=""){
					str+='<div class="n_com n_com_l">';
					str+='<a href="user/'+json.data[i].topicid+'" class="user_p"><img src="'+json.data[i].topicid+'" /></a>';
					str+='<p class="n_u"><strong><a href="user/'+json.data[i].topicid+'">'+utils.transform(json.data[i].topicid)+'</a></strong>'+utils.transform(json.data[i].topicid)+'</p>';
					str+='</div>';
				}
				str+='</li>';
			   $("#content"+tempi).children("ul").append(str);
			   that._loading = 0;
		   }
	   },
	   formatdate:function(){
		   $(".date").each(function(){
			   var tempdate=$(this).text();
			   $(this).empty();
			   $(this).text(utils.formatdate(tempdate));
		   });
	   },
	   autoload:function(){
			var that=this;
	        $(window).scroll(function(){
	        	var windowheight=$(window).height();
	            var srollPos = $(window).scrollTop();    //滚动条距顶部距离(页面超出窗口的高度)
	            var dbHiht = $("body").height();          //页面(约等于窗体)高度/单位px
	            if ((windowheight+ srollPos) >= (dbHiht-that.range)&& that._loading == 0) {
	            	that._loading =1;
	            	that.writemore();
	            }
	        });
	    }
	}
});
$(document).ready(function() {
 var index=new idsoo.index();
});

function topic_collect(tid){
	
	var url = siteUrl+'index.php?app=group&ac=do&ts=topic_collect';
	$.post(url,{topicid:tid},function(rs){
			if(rs == 0){
				$.dialog.open(siteUrl+'index.php?app=user&ac=ajax&ts=login', {title: '登录'});
			}else if(rs == 1){
			}else if(rs == 2){
				tips('你已经收藏过本帖啦，请不要再次收藏^_^');
			}else{
				topic_collect_user(tid);
			}					
	});
}

//谁收藏了这篇宝贝
function topic_collect_user(topicid){
	var url = siteUrl+'index.php?app=group&ac=topic_collect_user&ts=ajax&topicid='+topicid;
	$.post(url,function(rs){ $('#collects').html(rs); });
}