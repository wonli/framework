<div class="user">
    <form action="<?php echo $this->link("user:post") ?>" method="post">
        <ul>
            <li>
                <div class="ctxt">标题: </div>
                <div class="cinp">
                    <input type="hidden" name="id" id="" value="<?php echo $data["id"] ?>"/>
                    <input type="text" name="title" id="" value="<?php echo $data["title"] ?>"/>
                    <span style="line-height:25px;">
                        <input type="checkbox" name="status" id="" value="1" <?php if(! $data["status"] || $data["status"] == 1) : ?>checked<?php endif ?> /> 是否显示
                    </span>
                </div>
            </li> 
            <li>
                <div class="ctxt">发布日期: </div>
                <div class="cinp">
                    <input type="text" name="date" id="" value="<?php echo date('Y-m-d H:i:s')?>"/>
                </div>
            </li> 
            <li>
                <div class="ctxt">标签: </div>
                <div class="cinp" style="position: relative;" id="tag_info">
                    <input type="text" name="tag" id="tag_view" value="<?php echo $data['tag_str'] ?>"/>
                </div>
            </li>            
            <li>
                <div class="ctxt">文章简介: </div>
                <div class="cinp">
                    <textarea name="desc" id="desc" cols="93" rows="5"><?php echo $data["desc"] ?></textarea>
                </div>
            </li>             
            <li>
                <div class="ctxt">内容: </div>
                <div class="cinp">
                    <textarea name="content" id="content" cols="93" rows="25"><?php echo $data["content"] ?></textarea>
                </div>
            </li>
            <li>
                <div class="ctxt">&nbsp;</div>
                <div class="cinp"><input style="height:40px;width:100px;" type="submit" value="发表" /></div>
            </li>
        </ul>
    </form>
</div>
<script src="<?php echo STATIC_URL ?>editor/kindeditor-min.js" charset="utf-8"></script>
<script>
    KindEditor.ready(function(K) {
        K.create('#content', {
            themeType : 'simple'
        });
    });
</script>
<script type="text/javascript">


$(function(){

    $("#get_tag_list").click(function(){
        $("#tag_list_div").show();
    });

    
    var tag_val = [];        
    $(".tag_point").click(function(){
        var tag = $("#tag_view"); 
        var tagname = $(this).attr("tag_name");
        var tagid = $(this).attr("tag_id");		
        var tag_str = tag.val();

        if( jQuery.inArray(tagid, tag_val ) >= 0 ) {
            
        } else {
            tag_val.push(tagid);

            var el = document.createElement("input");
            el.type = "hidden";
            el.name = "tag[]";
            el.id = "tagid_"+tagid;
            el.value = tagid;
            $("#tag_info").append(el);
                
            tag.val(tag_str+tagname+',');
        }
    });
});
</script>
