<?php
if($registry->has('theme_options') == false) { 
 header("location: themeinstall/index.php"); 
 exit; 
}
$theme_options = $registry->get('theme_options');
$config = $registry->get('config');
$page_direction = $theme_options->get( 'page_direction' ); $language_id = $config->get( 'config_language_id' );
?>
<div id="banner<?php echo $module; ?>" class="owl-carousel banner-module">
  <?php foreach ($banners as $banner) { ?>
  <div class="item">
    <?php if ($banner['link']) { ?>
    <a href="<?php echo $banner['link']; ?>"><img src="<?php echo $banner['image']; ?>" alt="<?php echo $banner['title']; ?>" class="img-responsive" /></a>
    <?php } else { ?>
    <img src="<?php echo $banner['image']; ?>" alt="<?php echo $banner['title']; ?>" class="img-responsive" />
    <?php } ?>
  </div>
  <?php } ?>
</div>
<?php if(!isset($page_direction[$language_id])) {
	$page_direction[$language_id] = false;
} ?>
<script type="text/javascript"><!--
$('#banner<?php echo $module; ?>').owlCarousel({
	items: 6,
	autoPlay: 3000,
	singleItem: true,
	navigation: false,
	transitionStyle: 'fade',
	<?php if($page_direction[$language_id] == 'RTL'): ?>
	direction: 'rtl'
	<?php endif; ?>
});
--></script>