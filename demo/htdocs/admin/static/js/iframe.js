$(window.parent.document).find("#main").load(function(){
	var main = $(window.parent.document).find("#main");
	var mainLeft = $(window.parent.document).find(".left");
	var thisheight = $(window.parent.document).height();

	if ($.browser.msie && ($.browser.version == "6.0") && !$.support.style) { 
		$(window.parent.document).find("body").height(thisheight-200);
	} else{
		main.height(thisheight-200);
		mainLeft.height(thisheight-196);
	}
	
});
 $(function(){  
	   $('.table').each(function(){
			$(this).find('tr:odd').find('td').css("background","#f1f1f1");
	});

 }) 
