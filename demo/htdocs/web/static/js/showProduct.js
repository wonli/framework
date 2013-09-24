$.namespace("idsoo.showProduct", function(){
	idsoo.showProduct = function(){
		this.init.apply(this, arguments);
	}
	idsoo.showProduct.prototype = {
		index:1,
		loadnum:12,
		albumarray:"",
		tempalbumarray:"",
		flag:"true",
		init : function(){
			var that = this;
			$(window).keydown(function(event){
				switch(event.keyCode){
				case 37:
				case 75: 
					if($(".pre").attr("href"))
						window.location = $(".pre").attr("href");
					break;
				case 39: 
				case 74: 
					if($(".next").attr("href"))
						window.location = $(".next").attr("href");
					break;
				default: break;
				}
			});
			if($("#album")){
				$("#album").mouseenter(function(){
					that.getAlbum();
				});
				$("#album").mouseleave(function(){
					that.submitAlbum();
				});
			}
			$("#createalbum").click(function(){
				that.createAlbum();
			});
			$(".report a").click(function(){
				that.report();
			});
			$("#submit").click(function(){
				that.submit();
			});
			$("#followed").click(function(){
				if($(this).attr("href").indexOf("login")<0){
					that.unfollow();
				}
			});
			$("#unfollow").click(function(){
				if($(this).children(":first").attr("href").indexOf("login")<0){
					that.follow();
				}
			});
			if($("#isfollow").val()=="true"){
				$("#followed").parent().parent().show();
			}else if($("#isfollow").val()=="false"){
				$("#unfollow").show();
			}else{
			}
			if($("#fave")){
				$("#fave").children("a:first").click(function(){
					if($(this).attr("href").indexOf("login")<0){
						$.post("fave/"+$(this).attr("id").replace("fave_",""),null,null);
						
						$("#fave").children("a:first").text("已收藏");
						$("#fave").unbind().mouseout(function(){
							$("#fave").children("a:first").text("收藏");
							$("#fave").unbind("mouseout").hide();
							$("#marked").show();
						});
						
						
					}
				});
			}
			$("#marked").children("a:first").hover(function(){$(this).text("取消收藏");},function(){$(this).text("已收藏");});
			$("#marked").children("a:first").click(function(){
				if($(this).attr("href").indexOf("login")<0){
					if($(this).attr("id")){
						$.post("unfave/"+$(this).attr("id").replace("unfave_",""),null,null);
						$("#marked").hide();
						$("#fave").show();
					}
				}
			});
			if($("#isfavorite").val()=="true"){
				$("#marked").show();
			}else{
				if($("#fave")){
					$("#fave").show();
				}
			}
			that.predel();
			
			$("#comment").focus(function(){
				$("#submit").fadeIn(300);
			});
			$("#albumname").focus(function(){
				$("#createalbum").fadeIn(300);
			});
			
			var albumText = "创建新专辑";
			var albumtitle = $("input[name='albumname']").val();
			if(albumtitle == "null"){
				albumtitle = "";
			}
			$("#albumname").focus(function(){
				var tag = $.trim($(this).val());
				if(tag == albumText){
					$(this).removeClass("text tips").addClass("text").val("");
				}
			});
			$("#albumname").blur(function(){
				var tag = $.trim($(this).val());
				if(tag.length == 0){
					$(this).removeClass("text").addClass("text tips").val(albumText);
				}
			});
	   },
	   predel:function(){
		   if($(".com_del")){
			   $(".com_del").each(function(){
					$(this).children("a").unbind("click").click(function(){
						var _a=$(this);
						$.post("comment/"+_a.attr("id").replace("comment_","")+"/del",null,function(){
							var _str=$("#commentnum").html();
							_str=_str.replace("共","").replace("条评论","")-1;
							$("#commentnum").empty().html("共"+_str+"条评论");
							_a.parent().parent().parent().hide(800).empty();
						});
					});
				});
		   }
		   utils.fDomAutoLink($(".com_list")[0]);
	   },
	   submit:function(){
		   var that=this;
		   if($.trim($("#comment").val())==""){
			   $("#comment").focus();
			   return false;
		   }
		   var commentvalue=($("#comment").val()).replace(new RegExp("\n","gm"),"<br>");
		   $.post('index.php?app=group&ac=do&ts=addcomment',{content:commentvalue,topicid:$("#topicid").val()},function(data){
			   $("#comment").val('');
			   that.addcomment(data.replace(new RegExp("\r","gm"),"").replace(new RegExp("\n","gm"),""));
		   });
	   },
	   addcomment:function(data){
		   var that=this;
		   var json=eval("("+data+")");
		   $("#commentnum").empty().html("共"+json.commentnum+"条评论");
		   var $li = $('<li></li>');
		   var str="<div class=\"com_icon\"><a href=\"user/space/userid-space/userid-"+json.userid+"\"\"><img src=\""+json.useravatar+"\" alt="+json.username+" /></a></div>";
		   str+="<div class=\"com_con\">";
		   str+="<p><span class=\"fr date\">"+json.createtime+"</span><a href=\"user/space/userid-"+json.userid+"\">"+json.username+"</a></p>";
		   str+="<p class=\"com_d\">"+utils.transform(json.comment)+"</p>";
		   str+="<p class=\"com_del\"><a href=\"javascript:void('0');\" onclick=\"comment_del('{"+$("#topicid").val()+"}','{"+json.id+"}');\" rel=\"nofollow\">删除</a></p>";
		   str+="</div>";
		   $li.prepend(str);
		   $(".com_list").prepend($li.hide().fadeIn(1000));
		   that.predel();
	   },
	   unfollow:function(){
		   $("#unfollow").show();
		   $("#followed").parent().parent().hide();
		   $.post('follow/del',{followuid:$("#userid").val()},null);
	   },
	   follow:function(){
		   $("#unfollow").hide();
		   $("#followed").parent().parent().show();
		   $.post('follow/add',{followuid:$("#userid").val()},null);
	   },
	   getAlbum:function(){
		   var that=this;
		   if(that.flag=="true"){
			   that.flag="false";
			   $.post('album/get',{pid:$("#productid").val()},function(data){
				   that.writeAlbum(data.replace(new RegExp("\r","gm"),"").replace(new RegExp("\n","gm"),""));
			   });
		   }
	   },
	   submitAlbum:function(){
		   var that=this;
		   if(that.flag=="true"){
			   $(".pop_a").hide();
			   $("#createalbum").hide();
			   $("#albumname").blur();
			   that.flag="false";
			   if(that.check()){
				   $.post('album/add',{pid:$("#productid").val(),albumids:that.albumarray,sourceids:that.tempalbumarray},function(){
					   that.flag="true";
				   });
			   }else{
				   that.flag="true";
			   }
		   }
	   },
	   check:function(){
		 var that=this;
		 if(that.tempalbumarray!=""){
			 that.tempalbumarray=that.tempalbumarray.substring(0, that.tempalbumarray.length-1);
		 }
		 if(that.albumarray!=""){
			 that.albumarray=that.albumarray.substring(0, that.albumarray.length-1);
		 }
		 var arr1=that.tempalbumarray.split(",");
		 var arr2=that.albumarray.split(",");
		 if(arr1&&arr2){
			 $.unique(arr1);
			 $.unique(arr2);
			 if(arr1.length!=arr2.length){
				 return true;
			 }else{
				 arr1.sort(function(a, b) {return a-b;});
				 arr2.sort(function(a, b) {return a-b;});
				 return arr1.toString()==arr2.toString()?false:true;
			 }
		 }
	   },
	   writeAlbum:function(data){
		   var that=this;
		   var json=eval("("+data+")");
		   that.albumarray="";
		   $("#albumlist").empty();
		   for(var i=0;i<json.data.length;i++){
			   var str="";
			   if(json.data[i].flag=="true"){
				   that.albumarray+=json.data[i].id+",";
				   str='<li><a class="selected" id="album_'+json.data[i].id+'" href="javascript:void(0);">'+utils.transform(json.data[i].name)+'</a></li>';
			   }else{
				   str='<li><a id="album_'+json.data[i].id+'" href="javascript:void(0);">'+utils.transform(json.data[i].name)+'</a></li>';
			   }
			   
			   $("#albumlist").append($(str));
		   }
		   that.tempalbumarray=that.albumarray;
		   that.bindclick();
		   $(".pop_a").show();
		   that.flag="true";
	   },
	   createAlbum:function(){
		   var that=this;
		   var albumname=$.trim($("#albumname").val());
		   if(albumname==""){
			   $("#albumname").empty().focus();
			   return false;
		   }
		   $.post('album/save',{name:albumname},function(data){
			   data=data.replace(new RegExp("\r","gm"),"").replace(new RegExp("\n","gm"),"")
			   var json=eval("("+data+")");
			   if(json.data.length==0){
				   $("#albumname").val("").focus();
			   }else{
				   $("#albumname").val("");
				   that.albumarray+=json.data[0].id+",";
				   var str=str='<li><a class="selected" id="album_'+json.data[0].id+'" href="javascript:void(0);">'+utils.transform(json.data[0].name)+'</a></li>';
				   $("#albumlist").append($(str));
			   }
			   that.bindclick();
		   });
	   },
	   bindclick:function(){
		   var that=this;
		   $("#albumlist").children("li").each(function(){
			   $(this).unbind("click").click(function(){
				   if($(this).children("a").hasClass("selected")){
					   $(this).children("a").removeClass("selected");
					   that.albumarray=that.albumarray.replace(($(this).children("a").attr("id")).replace("album_","")+",", "");
				   }else{
					   $(this).children("a").addClass("selected");
					   that.albumarray+=($(this).children("a").attr("id")).replace("album_","")+",";
				   }
				});
		   });
	   },
	   report:function(){
		   if(!document.getElementById("formreport")){
				var _form=document.createElement("form");
				_form.action="report";
				_form.method="post";
				_form.id="formreport";
				var _input=document.createElement("input");
				_input.name="content";
				_input.id="contentid";
				_form.appendChild(_input);
				var _input2=document.createElement("input");
				_input2.name="url";
				_input2.id="urlid";
				_form.appendChild(_input2);
				document.body.appendChild(_form);
			}
			$("#contentid").val($("#content").val().trim());
			$("#urlid").val($("#url").val().trim());
		   $("#formreport").submit();
	   }
	}
});
$(document).ready(function() {
 var showProduct=new idsoo.showProduct();
});

function open_url(url){if(!url){return false;}

window.open(url);}
