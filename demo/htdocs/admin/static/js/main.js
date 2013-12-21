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

function showMenu(e, id)
{
    $(".nav_tip").removeClass("selected");
    $(e).addClass("selected");
    $("#"+id).show().siblings().hide();
}

function setRef(p)
{
    hashData.write("ref", p);
}

$(function(){

    var hd = hashData.read();
    if(hd.ref) {
        var par = hd.ref.split(":");
        document.getElementById("mainFrame").src= "http://127.0.0.1/h5/admin.php/"+par[0]+"/"+par[1];
    }

	$("#side_switch").click(function(){
		$(".left").hide();
		$("#right_body").css('margin-left',0);
		$(this).hide();
		$("#side_switchl").show();
	});

	$("#side_switchl").click(function(){
		$(".left").show();
		$("#right_body").css('margin-left',200);
		$(this).hide();
		$("#side_switch").show();
	});

    $(".act_tip").click(function(){
        $(".act_tip").removeClass("selected");
        $(this).addClass("selected");
        var act = $(this).html();
        $("#cur_act").html(act);
    });    
})