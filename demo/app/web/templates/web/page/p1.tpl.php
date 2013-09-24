  		
  		
<div class="pagination">
	        <?php if($page["p"] > $page["half"] + 1) : ?>
	        <a class="pagenav-cell" href="<?php echo $page ["link"].$_dot . 1 ?>">
	            1
	        </a>
	        <span class="pagenav-cell pagenav-cell-ellipsis">…</span>
	        <?php endif ?>   

            <?php
	            for($i = $page["p"] - $page["half"], $i = ($i > 0) ? $i : 1, $j = $page["p"] + $page["half"], $j = ($j > $page["total_page"]) ? $page["total_page"] : $j; $i <= $j; $i++)
	            {          	
	                ?>
	                    <?php if($i == $page["p"]) : ?>
	                    <a class="current" href="javascript:void(0)">
	                        <?php echo $i ?>
	                    </a>
	                    <?php else : ?>
	                    <a class="" href="<?php echo $page ["link"].$_dot.$i ?>">
	                        <?php echo $i ?>
	                    </a>
	                    <?php endif ?>
	                <?php
	            }
        	?>
        	
	        <?php if( $page["p"] + $page ["half"] < $page["total_page"] ) : ?>
	        <span class="pagenav-cell pagenav-cell-ellipsis">…</span>
	        <a class="pagenav-cell" href="<?php echo $page ["link"].$_dot.$page ["total_page"] ?>">
	            <?php echo $page["total_page"] ?>
	        </a>           
	        <?php endif ?>
</div>