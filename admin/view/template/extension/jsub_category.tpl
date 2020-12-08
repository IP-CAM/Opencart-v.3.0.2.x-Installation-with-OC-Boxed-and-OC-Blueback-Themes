<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-jsub_category" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $button_save; ?></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
        <div class="pull-right">
          <select name="store_id" onchange="window.location = this.value;">
            <?php foreach($stores as $store) {
            $sel = ''; if($store['store_id'] == $store_id){ $sel = 'selected="selected"'; }
            ?>
            <option <?php echo $sel; ?> value="<?php echo $store['href']; ?>"><?php echo $store['name']; ?></option>
            <?php } ?>
          </select>
        </div>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-jsub_category" class="form-horizontal">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-general" data-toggle="tab"><i class="fa fa-cogs"></i> <?php echo $tab_general; ?></a></li>
            <li><a href="#tab-languagetext" data-toggle="tab"><i class="fa fa-language"></i> <?php echo $tab_languagetext; ?></a></li>
            <li><a href="#tab-sizesetting" data-toggle="tab"><i class="fa fa-arrows"></i> <?php echo $tab_sizesetting; ?></a></li>
            <li><a href="#tab-colorsettings" data-toggle="tab"><i class="fa fa-paint-brush"></i> <?php echo $tab_colorsettings; ?></a></li>
            <li><a href="#tab-support" data-toggle="tab"><i class="fa fa-support"></i> <?php echo $tab_support; ?></a></li>
        	</ul>
        	<div class="tab-content">
            <div class="tab-pane active" id="tab-general">
            	<div class="form-group">
		            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
		            <div class="col-sm-3">
		              <div class="btn-group btn-group-justified" data-toggle="buttons">
		                <label class="btn btn-default <?php echo !empty($jsub_category_status) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_status" value="1" <?php echo (!empty($jsub_category_status)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_enabled; ?>
		                </label>
		                <label class="btn btn-default <?php echo empty($jsub_category_status) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_status" value="0" <?php echo (empty($jsub_category_status)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_disabled; ?>
		                </label>
		              </div>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label"><?php echo $entry_images; ?></label>
		            <div class="col-sm-3">
		              <div class="btn-group btn-group-justified" data-toggle="buttons">
		                <label class="btn btn-default <?php echo !empty($jsub_category_images) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_images" value="1" <?php echo (!empty($jsub_category_images)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_yes; ?>
		                </label>
		                <label class="btn btn-default <?php echo empty($jsub_category_images) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_images" value="0" <?php echo (empty($jsub_category_images)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_no; ?>
		                </label>
		              </div>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label"><?php echo $entry_title; ?></label>
		            <div class="col-sm-3">
		              <div class="btn-group btn-group-justified" data-toggle="buttons">
		                <label class="btn btn-default <?php echo !empty($jsub_category_title) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_title" value="1" <?php echo (!empty($jsub_category_title)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_yes; ?>
		                </label>
		                <label class="btn btn-default <?php echo empty($jsub_category_title) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_title" value="0" <?php echo (empty($jsub_category_title)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_no; ?>
		                </label>
		              </div>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label" for="input-description"><?php echo $entry_description; ?></label>
		            <div class="col-sm-3">
		              <div class="btn-group btn-group-justified" data-toggle="buttons">
		                <label class="btn btn-default <?php echo !empty($jsub_category_description) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_description" value="1" <?php echo (!empty($jsub_category_description)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_yes; ?>
		                </label>
		                <label class="btn btn-default <?php echo empty($jsub_category_description) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_description" value="0" <?php echo (empty($jsub_category_description)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_no; ?>
		                </label>
		              </div>
		            </div>
		          </div>
		          <fieldset>
		          	<legend><?php echo $text_carousel; ?></legend>
		          	<div class="form-group">
			            <label class="col-sm-2 control-label" for="input-carousel"><?php echo $entry_carousel; ?></label>
			            <div class="col-sm-3">
			              <div class="btn-group btn-group-justified" data-toggle="buttons">
			                <label class="btn btn-default <?php echo !empty($jsub_category_carousel) ? 'active' : ''; ?>">
			                  <input type="radio" name="jsub_category_carousel" value="1" <?php echo (!empty($jsub_category_carousel)) ? 'checked="checked"' : ''; ?> />
			                  <?php echo $text_enabled; ?>
			                </label>
			                <label class="btn btn-default <?php echo empty($jsub_category_carousel) ? 'active' : ''; ?>">
			                  <input type="radio" name="jsub_category_carousel" value="0" <?php echo (empty($jsub_category_carousel)) ? 'checked="checked"' : ''; ?> />
			                  <?php echo $text_disabled; ?>
			                </label>
			              </div>
			            </div>
			          </div>
			          <div class="form-group">
			            <label class="col-sm-2 control-label" for="input-carousel-navs"><?php echo $entry_carnav; ?></label>
			            <div class="col-sm-3">
			              <div class="btn-group btn-group-justified" data-toggle="buttons">
			                <label class="btn btn-default <?php echo !empty($jsub_category_carnav) ? 'active' : ''; ?>">
			                  <input type="radio" name="jsub_category_carnav" value="1" <?php echo (!empty($jsub_category_carnav)) ? 'checked="checked"' : ''; ?> />
			                  <?php echo $text_enabled; ?>
			                </label>
			                <label class="btn btn-default <?php echo empty($jsub_category_carnav) ? 'active' : ''; ?>">
			                  <input type="radio" name="jsub_category_carnav" value="0" <?php echo (empty($jsub_category_carnav)) ? 'checked="checked"' : ''; ?> />
			                  <?php echo $text_disabled; ?>
			                </label>
			              </div>
			            </div>
			          </div>
			          <div class="form-group">
			            <label class="col-sm-2 control-label" for="input-pagination"><?php echo $entry_carpage; ?></label>
			            <div class="col-sm-3">
			              <div class="btn-group btn-group-justified" data-toggle="buttons">
			                <label class="btn btn-default <?php echo !empty($jsub_category_carpage) ? 'active' : ''; ?>">
			                  <input type="radio" name="jsub_category_carpage" value="1" <?php echo (!empty($jsub_category_carpage)) ? 'checked="checked"' : ''; ?> />
			                  <?php echo $text_enabled; ?>
			                </label>
			                <label class="btn btn-default <?php echo empty($jsub_category_carpage) ? 'active' : ''; ?>">
			                  <input type="radio" name="jsub_category_carpage" value="0" <?php echo (empty($jsub_category_carpage)) ? 'checked="checked"' : ''; ?> />
			                  <?php echo $text_disabled; ?>
			                </label>
			              </div>
			            </div>
			          </div>
			          <div class="form-group">
			            <label class="col-sm-2 control-label" for="input-autoplay"><?php echo $entry_carautoplay; ?></label>
			            <div class="col-sm-3">
			              <div class="btn-group btn-group-justified" data-toggle="buttons">
			                <label class="btn btn-default <?php echo !empty($jsub_category_carautoplay) ? 'active' : ''; ?>">
			                  <input type="radio" name="jsub_category_carautoplay" value="1" <?php echo (!empty($jsub_category_carautoplay)) ? 'checked="checked"' : ''; ?> />
			                  <?php echo $text_enabled; ?>
			                </label>
			                <label class="btn btn-default <?php echo empty($jsub_category_carautoplay) ? 'active' : ''; ?>">
			                  <input type="radio" name="jsub_category_carautoplay" value="0" <?php echo (empty($jsub_category_carautoplay)) ? 'checked="checked"' : ''; ?> />
			                  <?php echo $text_disabled; ?>
			                </label>
			              </div>
			            </div>
			          </div>
			          <div class="form-group">
			            <label class="col-sm-2 control-label" for="input-caritem"><?php echo $entry_caritem; ?></label>
			            <div class="col-sm-3">
			              <input type="text" name="jsub_category_caritem" value="<?php echo $jsub_category_caritem; ?>" placeholder="<?php echo $entry_caritem; ?>" id="input-caritem" class="form-control" />
			              <?php if ($error_jsub_category_caritem) { ?>
			              <div class="text-danger"><?php echo $error_jsub_category_caritem; ?></div>
			              <?php } ?>
			            </div>
			          </div>
		          </fieldset>
          	</div>
          	<div class="tab-pane" id="tab-languagetext">
          		<div class="form-group">
		            <label class="col-sm-2 control-label"><?php echo $entry_sub_heading; ?></label>
		            <div class="col-sm-6">
		              <?php foreach ($languages as $language) { ?>
		              <div class="input-group">
		                <span class="input-group-addon">
		                  <?php if(VERSION >= '2.2.0.0') { ?>
		                  <img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" />
		                  <?php } else{ ?>
		                  <img src="view/image/flags/<?php echo $language['image']; ?>" title="<?php echo $language['name']; ?>" />
		                  <?php } ?>
		                </span>
		                <input type="text" name="jsub_category_data[<?php echo $language['language_id']; ?>][sub_heading]" value="<?php echo !empty($jsub_category_data[$language['language_id']]['sub_heading']) ? $jsub_category_data[$language['language_id']]['sub_heading'] : ''; ?>" placeholder="<?php echo $entry_sub_heading; ?>" class="form-control" />
		              </div>
		              <?php } ?>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label"><?php echo $entry_view_all; ?></label>
		            <div class="col-sm-6">
		              <?php foreach ($languages as $language) { ?>
		              <div class="input-group">
		                <span class="input-group-addon">
		                  <?php if(VERSION >= '2.2.0.0') { ?>
		                  <img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" />
		                  <?php } else{ ?>
		                  <img src="view/image/flags/<?php echo $language['image']; ?>" title="<?php echo $language['name']; ?>" />
		                  <?php } ?>
		                </span>
		                <input type="text" name="jsub_category_data[<?php echo $language['language_id']; ?>][sub_viewall]" value="<?php echo !empty($jsub_category_data[$language['language_id']]['sub_viewall']) ? $jsub_category_data[$language['language_id']]['sub_viewall'] : ''; ?>" placeholder="<?php echo $entry_view_all; ?>" class="form-control" />
		              </div>
		              <?php } ?>
		            </div>
		          </div>
		        </div>
		        <div class="tab-pane" id="tab-sizesetting">
		        	<div class="form-group">
		            <label class="col-sm-2 control-label" for="input-width"><?php echo $entry_width; ?></label>
		            <div class="col-sm-6">
		              <input type="text" name="jsub_category_width" value="<?php echo $jsub_category_width; ?>" placeholder="<?php echo $entry_width; ?>" id="input-width" class="form-control" />
		              <?php if ($error_jsub_category_width) { ?>
		              <div class="text-danger"><?php echo $error_jsub_category_width; ?></div>
		              <?php } ?>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label" for="input-height"><?php echo $entry_height; ?></label>
		            <div class="col-sm-6">
		              <input type="text" name="jsub_category_height" value="<?php echo $jsub_category_height; ?>" placeholder="<?php echo $entry_height; ?>" id="input-height" class="form-control" />
		              <?php if ($error_jsub_category_height) { ?>
		              <div class="text-danger"><?php echo $error_jsub_category_height; ?></div>
		              <?php } ?>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label" for="input-layout"><?php echo $entry_layout; ?></label>
		            <div class="col-sm-3">
		              <div class="btn-group btn-group-justified" data-toggle="buttons">
		                <label class="btn btn-default <?php echo !empty($jsub_category_layout) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_layout" value="1" <?php echo (!empty($jsub_category_layout)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_grid; ?>
		                </label>
		                <label class="btn btn-default <?php echo empty($jsub_category_layout) ? 'active' : ''; ?>">
		                  <input type="radio" name="jsub_category_layout" value="0" <?php echo (empty($jsub_category_layout)) ? 'checked="checked"' : ''; ?> />
		                  <?php echo $text_list; ?>
		                </label>
		              </div>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label" for="input-desclength"><?php echo $entry_desclength; ?></label>
		            <div class="col-sm-6">
		              <input type="text" name="jsub_category_desclength" value="<?php echo $jsub_category_desclength; ?>" placeholder="<?php echo $entry_desclength; ?>" id="input-desclength" class="form-control" />
		            </div>
		          </div>
		        </div>
		        <div class="tab-pane" id="tab-colorsettings">
		        	<div class="form-group">
		            <label class="col-sm-2 control-label" for="input-background"><?php echo $entry_background; ?></label>
		            <div class="col-sm-6">
		            	<div class="input-group colorpicker">
		            		<span class="input-group-addon"><i></i></span>
		              	<input type="text" name="jsub_category_bg" value="<?php echo $jsub_category_bg; ?>" placeholder="<?php echo $entry_background; ?>" id="input-titlecolor" class="form-control" />
		              </div>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label" for="input-bghovercolor"><?php echo $entry_bg_hover_color; ?></label>
		            <div class="col-sm-6">
		            	<div class="input-group colorpicker">
		            		<span class="input-group-addon"><i></i></span>
		              	<input type="text" name="jsub_category_bg_hover_color" value="<?php echo $jsub_category_bg_hover_color; ?>" placeholder="<?php echo $entry_bg_hover_color; ?>" id="input-titlecolor" class="form-control" />
		              </div>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label" for="input-bordercolor"><?php echo $entry_border_color; ?></label>
		            <div class="col-sm-6">
		            	<div class="input-group colorpicker">
		            		<span class="input-group-addon"><i></i></span>
		              	<input type="text" name="jsub_category_border" value="<?php echo $jsub_category_border; ?>" placeholder="<?php echo $entry_border_color; ?>" id="input-titlecolor" class="form-control" />
		              </div>
		            </div>
		          </div>
		        	<div class="form-group">
		            <label class="col-sm-2 control-label" for="input-titlecolor"><?php echo $entry_title_color; ?></label>
		            <div class="col-sm-6">
		            	<div class="input-group colorpicker">
		            		<span class="input-group-addon"><i></i></span>
		              	<input type="text" name="jsub_category_titlecolor" value="<?php echo $jsub_category_titlecolor; ?>" placeholder="<?php echo $entry_title_color; ?>" id="input-titlecolor" class="form-control" />
		              </div>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label" for="input-desccolor"><?php echo $entry_desc_color; ?></label>
		            <div class="col-sm-6">
		            	<div class="input-group colorpicker">
		            		<span class="input-group-addon"><i></i></span>
		              	<input type="text" name="jsub_category_desccolor" value="<?php echo $jsub_category_desccolor; ?>" placeholder="<?php echo $entry_desc_color; ?>" id="input-desccolor" class="form-control" />
		              </div>
		            </div>
		          </div>
		          <div class="form-group">
		            <label class="col-sm-2 control-label" for="input-viewallcolor"><?php echo $entry_viewall_color; ?></label>
		            <div class="col-sm-6">
		            	<div class="input-group colorpicker">
		            		<span class="input-group-addon"><i></i></span>
		              	<input type="text" name="jsub_category_viewallcolor" value="<?php echo $jsub_category_viewallcolor; ?>" placeholder="<?php echo $entry_viewall_color; ?>" id="input-viewallcolor" class="form-control" />
		              </div>
		            </div>
		          </div>
		        </div>
		        <div class="tab-pane" id="tab-support">
	              <div class="card-deck mb-3 text-center">
	                <div class="card mb-4 shadow-sm">
	                  <div class="card-header">
	                    <h4 class="my-0 font-weight-normal">Support</h4>
	                  </div>
	                  <div class="card-body">
	                    <h4 class="card-title pricing-card-title">For Support Send E-mail at <big class="text-muted">jadeagile1@gmail.com</big></h4>
	                    <a target="_BLANK" href="https://www.opencart.com/index.php?route=marketplace/extension&amp;filter_member=jadeagile" class="btn btn-lg btn-primary">View Other Extensions</a>
	                  </div>
	                </div>
	              </div>
	            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript"><!--
	$('#language a:first').tab('show');
//--></script>
<script type="text/javascript"><!--
  // Color Picker
  $(function() { $('.colorpicker').colorpicker(); });
  //--></script>
<?php echo $footer; ?>