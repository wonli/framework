jQuery.extend({
    //默认配置参数
    defaultset:
    {
        name :"dooogdiv",
        close:true,
        title:"\u6807\u9898",
        content:["ajax",""],
        style:"dooog",
        location:[300,200,0,0]
    },

    //生成一个DIV
    createdooogdiv:function (name,title,style) {
        
        if($("#"+name).length) {
            return jQuery.dooogclose(name);
        }
        
        var name  = name  ? name  : jQuery.defaultset.name;
        var title = title ? title : jQuery.defaultset.title;
        var style = style ? style : jQuery.defaultset.style;
        
        var dooogdiv =  '<div id="'+name+'" class="'+style+'" style="position:absolute;z-index:999999;left:-2000px;top:-2000px;">' +
                            '<div id="'+name+'-close" style="width:20px;height:20px;position:absolute;right:15px;top:10px;border:1px solid #646464;color:#646464;height:14px;line-height:10px;width:14px;text-align:center;cursor:pointer;font-size:20px;" onclick=jQuery.dooogclose("'+name+'")>x</div>'+
                            '<div id="'+name+'-title" class="title">'+title+'</div>' +
                            '<div id="'+name+'-content" class="content"><img src="../../app/home/views/templates/images/loading.gif"></div>'+
                        '</div>';
		$("body").append("<div id='"+name+"-Mask' style='opacity:0.5;filter:alpha(opacity=50);width:100%;background:#000000;position:absolute;top:0px;left:0px;z-index:99999;display:block;'></div>");
        $("#"+name+"-Mask").height($("body").outerHeight()>$(window).height()?$("body").outerHeight():$(window).height());
        
        jQuery(dooogdiv).appendTo('body');	        
        return $("#"+name);
    },

    //删除层
    dooogclose:function(o) {
        $("#"+o).remove();
        $("#"+o+"-Mask").remove();
    },

    //拖动层
    drag:function ( name ) {
        
        //设置拖动区域
        var dgarea = $("#"+name+"-title").length ? $("#"+name+"-title") : $("#"+name);
        var dgdiv  = $("#"+name);
        var move = false;
        
        dgarea.mouseover(function(){
            dgarea.css("cursor","move");
            
            dgarea.mousedown(function(event){
                
                dgdiv.css({"z-index":999999,"opacity":0.9});
                this.move = true;
                this._x   = event.pageX - $(this).offset().left;
                this._y   = event.pageY - $(this).offset().top;
                // 防止文字被选中
                document.onmousemove = function() {return false};

            }).mouseup(function(){
                dgdiv.css({"z-index":999999,"opacity":1});
                this.move  = false;
            });
            
            dgarea.mousemove(function(e){

                if( this.move ) {
                    x = e.pageX - this._x;
                    y = e.pageY - this._y;
                    dgdiv.css({"left":x, "top":y});
                }
                
            }).mouseover(function(){
                dgdiv.css({"z-index":999999,"opacity":1});
                this.move = false;
            });
            
        }).mouseover(function(){
            dgdiv.css({"z-index":999999,"opacity":1});
            this.move = false;
        });    
    
    },
    
    setposition:function(div, location){
    
        var _w = location[0];
        var _h = location[1];
        
        if(location[2] == 0) {
            var _x = ( $(window).width() - _w ) / 2
        } else {
            var _x = jQuery.defaultset.location[2];
        }
        
        if(location[3] == 0) {
            var _y = ( $(window).height() / 2 - (_h/2) ) + $(document).scrollTop();
                _y = _y < 0 ? 10 : _y;
        } else {
            var _y = jQuery.defaultset.location[3];
        }

        _w = _w == 0 ? 'auto' : _w+'px'; 
        _h = _h == 0 ? 'auto' : _h+'px';  

        if(div) {
            div.css({width:_w,height:_h,left:_x,top:_y});
        }
    },
    
    //PUT内容
    putcontent:function(name, content) {

        var contentdiv = $("#"+name+"-content");
        
        //大图浏览
        if(content[0] === 'image') {
        
            contentdiv.html('<img src="'+content[1]+'" style="cursor:pointer;" onclick=jQuery.dooogclose("'+name+'")>');
            
            var _imgw = $("#"+name+"-content > img").outerWidth();
            var _imgh = $("#"+name+"-content > img").outerHeight();
            
            var _xxp = ( $(window).width() - _imgw ) / 2;
            var _yyp = ( $(window).height() / 2 - (_imgh/2) ) + $(document).scrollTop();
            
            _yyp = _yyp < 0 ? 10 : _yyp;
            
            $("#"+name).css({"left":_xxp,"top":_yyp});
        }

        //AJAX内容
        if(content[0] === 'ajax') {
            $.post(content[1],function(data){ 
                if(data) {
                    contentdiv.html(data);
                } else {
                    alert("请检测路径是否正确");
                }
            });
        }
        
        //文字内容
        if(content[0] === 'text') {
            contentdiv.text(content[1]);
        }
    },
    
    //函数入口
    dooog:function(set) {
        //生成DIV
        var crdiv   = jQuery.createdooogdiv(set.name,set.title,set.style);
        //设置DIV位置
        jQuery.setposition(crdiv, set.location);
        //加入内容
        jQuery.putcontent(set.name, set.content)
        //添加拖动
        jQuery.drag(set.name);
    }

})