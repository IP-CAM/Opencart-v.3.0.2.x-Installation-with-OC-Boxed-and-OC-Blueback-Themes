<?php if($theme_options->get( 'fixed_header' ) == 1) { ?>
<!-- HEADER
	================================================== -->
<div class="fixed-header-1 sticky-header header-type-24">
	<div class="background-header"></div>
	<div class="slider-header">
		<!-- Top of pages -->
		<div id="top" class="<?php if($theme_options->get( 'header_layout' ) == 1) { echo 'full-width'; } elseif($theme_options->get( 'header_layout' ) == 4) { echo 'fixed3 fixed2'; } elseif($theme_options->get( 'header_layout' ) == 3) { echo 'fixed2';  } else { echo 'fixed'; } ?>">
			<div class="background-top"></div>
			<div class="background">
				<div class="shadow"></div>
				<div class="pattern">
					<div class="megamenu-background">
					     <div class="">
					          <div class="overflow-megamenu container">
								<div class="row mega-menu-modules">
								     <div class="col-md-3">
								          <?php 
								          $menu92 = $modules_old_opencart->getModules('menu2');
								          if( count($menu92) ) { 
								          	foreach ($menu92 as $module) {
								          		echo $module;
								          	}
								          } ?>
								     </div>
								     
								     <div class="col-md-9">
								          <?php
								          $search_cats = $theme_options->getAllCategories();
								          $category_id = $theme_options->getCurrentCategory();
								          ?>
								          
								          <div class="search_form">     
								               <div class="button-search"></div>

								               <div class="search-cat">
								                   <select name="category_id" class="form-control">
								                     <option value="0"><?php echo $theme_options->get( 'all_categories_text', $config->get( 'config_language_id' ) ) != '' ? $theme_options->get( 'all_categories_text', $config->get( 'config_language_id' ) ) : 'All categories';  ?></option>
								                     <?php foreach ($search_cats as $category_1) { ?>
								                     <?php if ($category_1['category_id'] == $category_id) { ?>
								                     <option value="<?php echo $category_1['category_id']; ?>" selected="selected"><?php echo $category_1['name']; ?></option>
								                     <?php } else { ?>
								                     <option value="<?php echo $category_1['category_id']; ?>"><?php echo $category_1['name']; ?></option>
								                     <?php } ?>
								                     <?php foreach ($category_1['children'] as $category_2) { ?>
								                     <?php if ($category_2['category_id'] == $category_id) { ?>
								                     <option value="<?php echo $category_2['category_id']; ?>" selected="selected">&nbsp;&nbsp;<?php echo $category_2['name']; ?></option>
								                     <?php } else { ?>
								                     <option value="<?php echo $category_2['category_id']; ?>">&nbsp;&nbsp;<?php echo $category_2['name']; ?></option>
								                     <?php } ?>
								                     <?php foreach ($category_2['children'] as $category_3) { ?>
								                     <?php if ($category_3['category_id'] == $category_id) { ?>
								                     <option value="<?php echo $category_3['category_id']; ?>" selected="selected">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $category_3['name']; ?></option>
								                     <?php } else { ?>
								                     <option value="<?php echo $category_3['category_id']; ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $category_3['name']; ?></option>
								                     <?php } ?>
								                     <?php } ?>
								                     <?php } ?>
								                     <?php } ?>
								                   </select>
								               </div>
								               							         
								              <div class="overflow-input"><input type="text" class="input-block-level search-query" name="search" placeholder="<?php echo $search; ?>" id="search_query" value="" /></div>
								              
								              <?php if($theme_options->get( 'quick_search_autosuggest' ) != '0') { ?>
								                  <div id="autocomplete-results" class="autocomplete-results"></div>
								           
								                  <script type="text/javascript">
								                  $(document).ready(function() {
								           
								                      $('#search_query').autocomplete({
								                          delay: 0,
								                          appendTo: "#autocomplete-results",
								                          source: function(request, response) {  
								           
								                              var category_filter = $('header select[name=\'category_id\']').val();
								                              var category_filter_url = '';
								                              if (category_filter) {
								                                  category_filter_url = '&filter_category_id=' + encodeURIComponent(category_filter);
								                              }
								                              $.ajax({
								                                  url: 'index.php?route=search/autocomplete&filter_name=' +  encodeURIComponent(request.term) + category_filter_url,
								                                  dataType: 'json',
								                                  success: function(json) {
								                                      response($.map(json, function(item) {
								                                          return {
								                                              label: item.name,
								                                              value: item.product_id,
								                                              href: item.href,
								                                              thumb: item.thumb,
								                                              desc: item.desc,
								                                              price: item.price
								                                          }
								                                      }));
								                                  }
								                              });
								                          },
								                          select: function(event, ui) {
								                              document.location.href = ui.item.href;
								           
								                              return false;
								                          },
								                          focus: function(event, ui) {
								                              return false;
								                          },
								                          minLength: 2
								                      })
								                      .data( "ui-autocomplete" )._renderItem = function( ul, item ) {
								                        return $( "<li>" )
								                          .append( "<a>" + item.label + "</a>" )
								                          .appendTo( ul );
								                      };
								                  });
								                  </script>
								              <?php } ?>
								          </div>
								     </div>
								</div>
							</div>
						</div>
					</div><!-- End megamenu-background -->
				</div>
			</div>
		</div>
	</div>
	
	<?php $slideshow = $modules_old_opencart->getModules('slideshow'); ?>
	<?php  if(count($slideshow)) { ?>
	<!-- Slider -->
	<div id="slider" class="<?php if($theme_options->get( 'slideshow_layout' ) == 1) { echo 'full-width'; } elseif($theme_options->get( 'slideshow_layout' ) == 4) { echo 'fixed3 fixed2'; } elseif($theme_options->get( 'slideshow_layout' ) == 3) { echo 'fixed2'; } else { echo 'fixed'; } ?>">
		<div class="background-slider"></div>
		<div class="background">
			<div class="shadow"></div>
			<div class="pattern">
				<?php foreach($slideshow as $module) { ?>
				<?php echo $module; ?>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php } ?>
</div>
<?php } ?>

<!-- HEADER
	================================================== -->
<header class="header-type-24">
	<div class="background-header"></div>
	<div class="slider-header">
		<!-- Top of pages -->
		<div id="top" class="<?php if($theme_options->get( 'header_layout' ) == 1) { echo 'full-width'; } elseif($theme_options->get( 'header_layout' ) == 4) { echo 'fixed3 fixed2'; } elseif($theme_options->get( 'header_layout' ) == 3) { echo 'fixed2';  } else { echo 'fixed'; } ?>">
        
        <div class="top-bar">
        <div class="container">
        
        <div class="col-sm-4"><p>Welcome our Website <a href="<?php echo $register; ?>">Register</a> or <a href="<?php echo $account; ?>">Login</a></p>
       
         </div>
        
        
         <div class="col-sm-8">
        
        <?php 
							     $top_block = $modules_old_opencart->getModules('top_block');
							     if( count($top_block) ) { 
							     	foreach ($top_block as $module) {
							     		echo $module;
							     	}
							     } ?>
							     
							     <div class="overflow-change-menu">
							        
							          
							          <!-- Links -->
							          <ul class="menu">
							          	<li><a href="<?php echo $account; ?>"><i class="fa fa-user"></i><span><?php echo $text_account; ?></span></a></li>
							          	<li><a href="<?php echo $wishlist; ?>" id="wishlist-total"><i class="fa fa-gift"></i><span><?php echo $text_wishlist; ?></span></a></li>
							          	<li><a href="<?php echo $shopping_cart; ?>"><i class="fa fa-cart-plus"></i><span><?php echo $text_shopping_cart; ?></span></a></li>
							          	<li><a href="<?php echo $checkout; ?>"><i class="fa fa-check"></i><span><?php echo $text_checkout; ?></span></a></li>
                                        <li>  <?php echo $currency.$language; ?></li>
							          </ul>
							     </div>
							 </div>    
        
        </div>
        
        	</div>
        
			<div class="background-top"></div>
			<div class="background">
				<div class="shadow"></div>
				<div class="pattern">
					<div class="container">
						<div class="row">	
                       <!-- Header Left -->
							<div class="col-sm-3" id="header-left">
							     <?php if ($logo) { ?>
							     <!-- Logo -->
							     <div class="logo"><a href="<?php echo $home; ?>"><img src="<?php echo $logo; ?>" title="<?php echo $name; ?>" alt="<?php echo $name; ?>" /></a></div>
							     <?php } ?>
							</div>
							

							<!-- Header Right -->
							<div class="col-md-12 text-right" id="header-right">
							     
							     <div class="clear"></div>
							     
							     <?php 
							     $menu9 = $modules_old_opencart->getModules('menu');
							     if( count($menu9) ) { ?>
							          <div class="megamenu-background">
							               <div class="">
							                    <div class="overflow-megamenu container">
							     				<?php 
							     				if(count($menu9) > 1) echo '<div class="row mega-menu-modules">';
							     				$i = 0;
							     				    
							     				foreach ($menu9 as $module) {
							     				     if($i == 0 && count($menu9) > 1) echo '<div class="col-md-3">';
							     				     if($i == 1 && count($menu9) > 1) echo '<div class="col-md-9">';
							     					     echo $module;
							     					if(count($menu9) > 1 && ($i == 0 || $i == 1)) echo '</div>';
							     					if(count($menu9) > 1 && $i == 1) echo '</div>';
							     					$i++;
							     				} ?>
							     			</div>
							     		</div>
							     	</div>
							     <?php
							     } else {
							     	echo $menu;
							     }
							     ?>
							     
							   	<?php if ($logged) { ?>
							   		<div class="my-account-with-logout">
							   			<a href="<?php echo $account; ?>" onclick="window.location.href = '<?php echo $account; ?>'" class="my-account" data-hover="dropdown" data-toggle="dropdown"><i class="fa fa-user"></i></a>
							   			<ul class="dropdown-menu">
							   				<li><a href="<?php echo $account; ?>"><?php echo $text_account; ?></a></li>
							   				<li><a href="<?php echo $logout; ?>"><?php echo $text_logout; ?></a></li>
							   			</ul>
							   		</div>
							   	<?php } else { ?>
							   	
							   	<?php } ?>
								<?php echo $cart; ?>
							</div>
						</div>
					</div>
					
					<div class="megamenu-background">
					     <div class="">
					          <div class="overflow-megamenu container">
								<div class="row mega-menu-modules">
								     <div class="col-md-3">
								          <?php 
								          $menu92 = $modules_old_opencart->getModules('menu2');
								          if( count($menu92) ) { 
								          	foreach ($menu92 as $module) {
								          		echo $module;
								          	}
								          } ?>
								     </div>
								     
								     <div class="col-md-9">
								          <?php
								          $search_cats = $theme_options->getAllCategories();
								          $category_id = $theme_options->getCurrentCategory();
								          ?>
								          
								          <div class="search_form">     
								               <div class="button-search"></div>

								               <div class="search-cat">
								                   <select name="category_id" class="form-control">
								                     <option value="0"><?php echo $theme_options->get( 'all_categories_text', $config->get( 'config_language_id' ) ) != '' ? $theme_options->get( 'all_categories_text', $config->get( 'config_language_id' ) ) : 'All categories';  ?></option>
								                     <?php foreach ($search_cats as $category_1) { ?>
								                     <?php if ($category_1['category_id'] == $category_id) { ?>
								                     <option value="<?php echo $category_1['category_id']; ?>" selected="selected"><?php echo $category_1['name']; ?></option>
								                     <?php } else { ?>
								                     <option value="<?php echo $category_1['category_id']; ?>"><?php echo $category_1['name']; ?></option>
								                     <?php } ?>
								                     <?php foreach ($category_1['children'] as $category_2) { ?>
								                     <?php if ($category_2['category_id'] == $category_id) { ?>
								                     <option value="<?php echo $category_2['category_id']; ?>" selected="selected">&nbsp;&nbsp;<?php echo $category_2['name']; ?></option>
								                     <?php } else { ?>
								                     <option value="<?php echo $category_2['category_id']; ?>">&nbsp;&nbsp;<?php echo $category_2['name']; ?></option>
								                     <?php } ?>
								                     <?php foreach ($category_2['children'] as $category_3) { ?>
								                     <?php if ($category_3['category_id'] == $category_id) { ?>
								                     <option value="<?php echo $category_3['category_id']; ?>" selected="selected">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $category_3['name']; ?></option>
								                     <?php } else { ?>
								                     <option value="<?php echo $category_3['category_id']; ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $category_3['name']; ?></option>
								                     <?php } ?>
								                     <?php } ?>
								                     <?php } ?>
								                     <?php } ?>
								                   </select>
								               </div>
								               							         
								              <div class="overflow-input"><input type="text" class="input-block-level search-query" name="search" placeholder="<?php echo $search; ?>" id="search_query" value="" /></div>
								              
								              <?php if($theme_options->get( 'quick_search_autosuggest' ) != '0') { ?>
								                  <div id="autocomplete-results" class="autocomplete-results"></div>
								           
								                  <script type="text/javascript">
								                  $(document).ready(function() {
								           
								                      $('#search_query').autocomplete({
								                          delay: 0,
								                          appendTo: "#autocomplete-results",
								                          source: function(request, response) {  
								           
								                              var category_filter = $('header select[name=\'category_id\']').val();
								                              var category_filter_url = '';
								                              if (category_filter) {
								                                  category_filter_url = '&filter_category_id=' + encodeURIComponent(category_filter);
								                              }
								                              $.ajax({
								                                  url: 'index.php?route=search/autocomplete&filter_name=' +  encodeURIComponent(request.term) + category_filter_url,
								                                  dataType: 'json',
								                                  success: function(json) {
								                                      response($.map(json, function(item) {
								                                          return {
								                                              label: item.name,
								                                              value: item.product_id,
								                                              href: item.href,
								                                              thumb: item.thumb,
								                                              desc: item.desc,
								                                              price: item.price
								                                          }
								                                      }));
								                                  }
								                              });
								                          },
								                          select: function(event, ui) {
								                              document.location.href = ui.item.href;
								           
								                              return false;
								                          },
								                          focus: function(event, ui) {
								                              return false;
								                          },
								                          minLength: 2
								                      })
								                      .data( "ui-autocomplete" )._renderItem = function( ul, item ) {
								                        return $( "<li>" )
								                          .append( "<a>" + item.label + "</a>" )
								                          .appendTo( ul );
								                      };
								                  });
								                  </script>
								              <?php } ?>
								          </div>
								     </div>
								</div>
							</div>
						</div>
					</div><!-- End megamenu-background -->
				</div>
			</div>
		</div>
	</div>
	
	<?php $slideshow = $modules_old_opencart->getModules('slideshow'); ?>
	<?php  if(count($slideshow)) { ?>
	<!-- Slider -->
	<div id="slider" class="<?php if($theme_options->get( 'slideshow_layout' ) == 1) { echo 'full-width'; } elseif($theme_options->get( 'slideshow_layout' ) == 4) { echo 'fixed3 fixed2'; } elseif($theme_options->get( 'slideshow_layout' ) == 3) { echo 'fixed2'; } else { echo 'fixed'; } ?>">
		<div class="background-slider"></div>
		<div class="background">
			<div class="shadow"></div>
			<div class="pattern">
				<?php foreach($slideshow as $module) { ?>
				<?php echo $module; ?>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php } ?>
</header>