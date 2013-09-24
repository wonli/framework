//删除秀店
function talk_del(gid){
	if(confirm("确定删除吗?")){
		var url = siteUrl+'index.php?app=talk&ac=admin&mg=talk&ts=del';
		$.post(url,{talkid:gid},function(rs){
					if(rs == 0){
						window.location.reload(); 
					}
		})	
	}
}

function cate_del(cid){
	if(confirm("确定删除吗?")){
		var url = siteUrl+'index.php?app=talk&ac=admin&mg=cate&ts=del';
		$.post(url,{cateid:cid},function(rs){
					if(rs == 0){
						window.location.reload(); 
					}
		})	
	}
}