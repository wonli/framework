(function($){
    $.fn.extend({
        dooog: function(name,option) {

            var defaultset = {
                close:true,
                title:"\u6807\u9898",
                contenttype:"ajax",
                content:"",
                style:"box",
                local:[300,200,0,0]
            };

            var _x,_y,_w,_h;
            var move   = false;
            var click  = false;
            var set    = new Object;
            
            //是否开启关闭按钮,默认开启
            if(option.close) {
                set.close = option.close;
            } else {
                set.close = defaultset.local;
            }
            
            //是否自定义弹出曾层位置及大小
            if(option.local) {
                set.local = option.local;
            } else {
                set.local = defaultset.local;
            }
            
            //是否自定义标题
            if(option.title) {
                set.title = option.title;
            } else {
                set.title = defaultset.title;
            }
            
            //弹出层内容类型 ajax images text
            if(option.contenttype) {
                set.contenttype = option.contenttype;
            } else {
                set.contenttype = defaultset.contenttype;
            }
            
            //弹出层内容
            if(option.content) {
                set.content = option.content;
            } else {
                set.content = defaultset.content;
            }
            
            //弹出层样式
            if(option.style) {
                set.style = option.style;
            } else {
                set.style = defaultset.style;
            }
            
            _w = set.local[0];
            _h = set.local[1];
            
            if(set.local[2] == 0) {
                _x = ( $(window).width() - _w ) / 2
            } else {
                _x = set.local[2];
            }
            
            if(set.local[3] == 0) {
                _y = ( $(window).height() / 2 - (_h/2) ) + $(document).scrollTop();
                _y = _y < 0 ? 10 : _y;
            } else {
                _y = set.local[3];
            }
            
            if(_w === 0 ) {
                _w = 'auto';
            } else {
                _w = _w + 'px';
            }
            
            if(_h === 0 ) {
                _h = 'auto';
            } else {
                _h = _h + 'px';
            }
            
            if( this.length ) {
            
                var id     = $(this).attr("id");
                var dgdiv  = this;
                var dgarea = $("#"+id+" > #"+id+"-title").length ? $("#"+id+" > #"+id+"-title") : dgdiv;
            
            } else if(name) {
                //避免重复创建
                if($("#"+name).length) {return;}

                $("body").append(
                        '<div id="'+name+'" class="'+set.style+'" style="width:'+_w+';height:'+_h+';left:'+_x+'px;top:'+_y+'px">' +
                            '<div id="'+name+'-title" class="title">'+set.title+'</div>' +
                            '<div id="'+name+'-content" class="content"><img src="../../app/home/views/templates/images/loading.gif"></div>'+
                        '</div>'
                );
                
                if(set.contenttype == "ajax") {
                    $.post(set.content,function(data){ 
                        if(data) {
                            $("#"+name+"-content").html(data);
                        } else {
                            alert("请检测路径是否正确");
                        }
                    });
                }
                
                if(set.contenttype == "image") {
                    $("#"+name+"-content").html("<img src="+set.content+">");
                    
                    var _xp = $("#"+name+"-content > img").outerWidth();
                    var _yp = $("#"+name+"-content > img").outerHeight();
                    var _xxp = ( $(window).width() - _xp ) / 2;
                    var _yyp = ( $(window).height() / 2 - (_yp/2) ) + $(document).scrollTop();
                    
                    _yyp = _yyp < 0 ? 10 : _yyp;
                    
                    
                    $("#"+name).css({"left":_xxp,"top":_yyp});
                }
                
                var id     = name;
                var dgdiv  = $("#"+id);
                var dgarea = $("#"+id+" > #"+id+"-title").length ? $("#"+id+" > #"+id+"-title") : dgdiv;
                
                $("#"+name+"-content > img").attr("onclick","this.remove()");
            }
            
            
            //拖动开始
            if(!dgarea || !dgdiv){return}

            dgdiv.css("position","absolute");
            
            if(set.close === true) {
                dgdiv.append('<div id="'+id+'-close" style="width:20px;height:20px;position:absolute;right:15px;top:10px;border:1px solid #646464;color:#646464;height:14px;line-height:10px;width:14px;text-align:center;cursor:pointer;font-size:20px;">x</div>');
                var close  =  $("#"+id+" > #"+id+"-close");
                
                close.click(function(){
                    dgdiv.remove();
                })
            }
            
            dgarea.mouseover(function(){
                dgarea.css("cursor","move");
                
                dgarea.mousedown(function(event){
                    
                    dgdiv.css({"z-index":999,"opacity":0.9});
                    this.move = true;
                    this._x   = event.pageX - $(this).offset().left;
                    this._y   = event.pageY - $(this).offset().top;
                    //防止文字被选中
                    document.onmousemove = function() {return false};
                    
                }).mouseup(function(){
                    dgdiv.css({"z-index":1,"opacity":1});
                    this.move  = false;
                });
                
                dgarea.mousemove(function(e){

                    if( this.move ) {
                        x = e.pageX - this._x;
                        y = e.pageY - this._y;
                        dgdiv.css({"left":x, "top":y});
                    }
                    
                }).mouseover(function(){
                    dgdiv.css({"z-index":1,"opacity":1});
                    this.move = false;
                });
                
            }).mouseover(function(){
                dgdiv.css({"z-index":1,"opacity":1});
                this.move = false;
            });
        
            login=function(){
                alert("jquery.myplus.js - login");
            };
        }
    });
})(jQuery);



