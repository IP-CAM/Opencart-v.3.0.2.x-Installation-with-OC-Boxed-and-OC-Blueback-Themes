<div id="cookie-<?php echo $module_id; ?>" class="cookie "
     style="position: fixed;
            width: 100%;
            z-index: 9999;
            <?php if($background_color != '') { echo 'background-color: ' . $background_color . ';'; } 
            if($background_image != '') {
               echo 'background-image: url(image/' . $background_image . '); 
                    background-position: ' . $background_image_position . ';
                    background-repeat: ' . $background_image_repeat . ';'; 
            } ?>
            <?php if($border_color != '') {
               echo 'border-'.($display_position != '' && $display_position == 'top' ? 'bottom' : 'top').': 1px solid '.$border_color.';';
            } ?>
            <?php if($display_position != '') {
               echo $display_position.': 0';
            }else{
               echo 'bottom: 0; top: auto';
            } ?>
     ">
     <div class="standard-body">
          <div class="full-width">
              <div class="container">
                  <div class="content" style="<?php if($text_color != '') { echo 'color: ' . $text_color . ';'; } ?>"><?php echo $text_cookie  ?></div>
          
                  <div class="operations">          
                     <div class="button"><?php echo  $text_button ?></div>
                  </div>
              </div>
          </div>
     </div>
</div>

<script type="text/javascript">
$(document).ready(function() {

      $('#cookie-<?php echo $module_id; ?> .button').click(function () {
           var now = new Date();
             var time = now.getTime();
             time += 3600 * 24 * 1000 * 365;
             now.setTime(time);
           document.cookie = encodeURIComponent('cookie-module-<?php echo $module_id; ?>-accepted') + "=" + encodeURIComponent(1) + ';expires=' + now.toUTCString() + "; path=/";
           $('#cookie-<?php echo $module_id; ?>').fadeOut('slow');
      });

});
</script>