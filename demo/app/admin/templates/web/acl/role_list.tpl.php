<form action="" method="post">
    <table class="tb2">
        <?php foreach($data['role_list'] as $r) : ?>
            <tr>
                <td><?php echo $r['name'] ?></td>
                <td><a href="javascript:void(0)" onclick="edit_behavior(<?php echo $r['id'] ?>)" >编辑</a></td>
            </tr>
        <?php endforeach ?>
    </table>
</form>
<script type="text/javascript">
    function sele(o)
    {
        var token_name = $(o).attr('class');
        $("."+token_name+"_children").each(function( ){
            $(this).attr("checked", !!($(o).attr('checked') == 'checked'));
        })
    }

    function edit_behavior(rid)
    {
        $.get(SITE_URL+"/index.php/acl/edit_role", {"rid":rid}, function(d){
            pop.display(d);
        });
    }
</script>
