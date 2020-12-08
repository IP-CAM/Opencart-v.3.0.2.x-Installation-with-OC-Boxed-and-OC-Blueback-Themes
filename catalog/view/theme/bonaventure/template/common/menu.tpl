<?php if($categories) { ?>
<div class="megamenu-background">
     <div class="">
          <div class="overflow-megamenu container">
				<div class="container-megamenu horizontal">
					<div class="megaMenuToggle">
						<div class="megamenuToogle-wrapper">
							<div class="megamenuToogle-pattern">
								<div class="container">
									<div><span></span><span></span><span></span></div>
									Navigation
								</div>
							</div>
						</div>
					</div>
					
					<div class="megamenu-wrapper">
						<div class="megamenu-pattern">
							<div class="container">
								<ul class="megamenu shift-up">
									<?php foreach ($categories as $category) { ?>
									<?php if ($category['children']) { ?>
									<li class="with-sub-menu hover"><p class="close-menu"></p><p class="open-menu"></p>
										<a href="<?php echo $category['href'];?>"><span><strong><?php echo $category['name']; ?></strong></span></a>
									<?php } else { ?>
									<li>
										<a href="<?php echo $category['href']; ?>"><span><strong><?php echo $category['name']; ?></strong></span></a>
									<?php } ?>
										<?php if ($category['children']) { ?>
										<?php 
											$width = '100%';
											$row_fluid = 3;
											if($category['column'] == 1) { $width = '220px'; $row_fluid = 12; }
											if($category['column'] == 2) { $width = '500px'; $row_fluid = 6; }
											if($category['column'] == 3) { $width = '700px'; $row_fluid = 4; }
										?>
										<div class="sub-menu" style="width: <?php echo $width; ?>">
											<div class="content">
												<p class="arrow"></p>
												<div class="row hover-menu">
													<?php for ($i = 0; $i < count($category['children']);) { ?>
													<div class="col-sm-<?php echo $row_fluid; ?> mobile-enabled">
														<div class="menu">
															<ul>
															  <?php $j = $i + ceil(count($category['children']) / $category['column']); ?>
															  <?php for (; $i < $j; $i++) { ?>
															  <?php if (isset($category['children'][$i])) { ?>
															  <li><a href="<?php echo $category['children'][$i]['href']; ?>" class="main-menu"><?php echo $category['children'][$i]['name']; ?></a></li>
															  <?php } ?>
															  <?php } ?>
															</ul>
														</div>
													</div>
													<?php } ?>
												</div>
											</div>
										</div>
										<?php } ?>
									</li>
									<?php } ?>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
</div>
<?php
}
?>