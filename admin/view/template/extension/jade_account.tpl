<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-jade-account" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-success active"><i class="fa fa-check-circle"></i> <?php echo $button_save; ?></button>
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
            $sel = ''; if($store['store_id'] == $store_id) { $sel = 'selected="selected"'; }
            ?>
            <option <?php echo $sel; ?> value="<?php echo $store['href']; ?>"><?php echo $store['name']; ?></option>
            <?php } ?>
          </select>
        </div>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-jade-account" class="form-horizontal">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-general" data-toggle="tab"><i class="fa fa-cog"></i> <?php echo $tab_general; ?></a></li>
            <li><a href="#tab-profile" data-toggle="tab"><i class="fa fa-user"></i> <?php echo $tab_profile; ?></a></li>
            <li><a href="#tab-url" data-toggle="tab"><i class="fa fa-list"></i> <?php echo $tab_customlink; ?></a></li>
            <li><a href="#tab-affiliate-url" data-toggle="tab"><i class="fa fa-link"></i> <?php echo $tab_affiliate_link; ?></a></li>
            <li><a href="#tab-product" data-toggle="tab"><i class="fa fa-tag"></i> <?php echo $tab_product; ?></a></li>
            <li><a href="#tab-template" data-toggle="tab"><i class="fa fa-desktop"></i> <?php echo $tab_template; ?></a></li>
            <li><a href="#tab-colors" data-toggle="tab"><i class="fa fa-check-circle"></i> <?php echo $tab_colors; ?></a></li>
            <li><a href="#tab-contact" data-toggle="tab"><i class="fa fa-question-circle"></i> <?php echo $tab_contact; ?></a></li>
            <li><a href="#tab-support" data-toggle="tab"><i class="fa fa-support"></i> <?php echo $tab_support; ?></a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab-general">
              <fieldset>
                <legend class="notopmagin"><?php echo $text_control_panel; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_status; ?></label>
                  <div class="col-sm-10">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                      <label class="btn btn-default <?php echo $jade_account_status ? 'active' : ''; ?>"><input type="radio" name="jade_account_status" value="1" <?php echo $jade_account_status ? 'checked="checked"' : ''; ?> /> <?php echo $text_enabled; ?></label>
                      <label class="btn btn-default <?php echo !$jade_account_status ? 'active' : ''; ?>"><input type="radio" name="jade_account_status" value="0" <?php echo !$jade_account_status ? 'checked="checked"' : ''; ?> /> <?php echo $text_disabled; ?></label>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_heading_title; ?></label>
                  <div class="col-sm-10">
                    <?php foreach ($languages as $language) { ?>
                    <div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
                      <input type="text" name="jade_account_description[<?php echo $language['language_id']; ?>][heading_title]" value="<?php echo isset($jade_account_description[$language['language_id']]['heading_title']) ? $jade_account_description[$language['language_id']]['heading_title'] : ''; ?>" placeholder="<?php echo $entry_heading_title; ?>" class="form-control" />
                    </div>
                    <?php } ?>
                  </div>
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $text_widgets; ?></legend>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover">
                    <thead>
                      <tr>
                        <td><?php echo $column_widgets; ?></td>
                        <td><?php echo $column_status; ?></td>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach($widgets as $widget) { ?>
                      <tr>
                        <td><?php echo $widget['text']; ?></td>
                        <td>
                          <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-default <?php echo !empty($jade_account_widget[$widget['type']]) ? 'active' : ''; ?>"><input type="radio" name="jade_account_widget[<?php echo $widget['type']; ?>]" value="1" <?php echo !empty($jade_account_widget[$widget['type']]) ? 'checked="checked"' : ''; ?> /> <?php echo $text_show; ?></label>
                            <label class="btn btn-default <?php echo empty($jade_account_widget[$widget['type']]) ? 'active' : ''; ?>"><input type="radio" name="jade_account_widget[<?php echo $widget['type']; ?>]" value="0" <?php echo empty($jade_account_widget[$widget['type']]) ? 'checked="checked"' : ''; ?> /> <?php echo $text_hide; ?></label>
                          </div>
                        </td>
                      </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $text_contact; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_contactus; ?></label>
                  <div class="col-sm-10">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                      <label class="btn btn-default <?php echo $jade_account_contact ? 'active' : ''; ?>"><input type="radio" name="jade_account_contact" value="1" <?php echo $jade_account_contact ? 'checked="checked"' : ''; ?> /> <?php echo $text_enabled; ?></label>
                      <label class="btn btn-default <?php echo !$jade_account_contact ? 'active' : ''; ?>"><input type="radio" name="jade_account_contact" value="0" <?php echo !$jade_account_contact ? 'checked="checked"' : ''; ?> /> <?php echo $text_disabled; ?></label>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_button_text; ?></label>
                  <div class="col-sm-10">
                    <?php foreach ($languages as $language) { ?>
                    <div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
                      <input type="text" name="jade_account_description[<?php echo $language['language_id']; ?>][contact_button]" value="<?php echo isset($jade_account_description[$language['language_id']]['contact_button']) ? $jade_account_description[$language['language_id']]['contact_button'] : ''; ?>" placeholder="<?php echo $entry_button_text; ?>" class="form-control" />
                    </div>
                    <?php if (isset($error_contact_button[$language['language_id']])) { ?>
                    <div class="text-danger"><?php echo $error_contact_button[$language['language_id']]; ?></div>
                      <?php } ?>
                    <?php } ?>
                  </div>
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $text_orders; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_latestorders; ?></label>
                  <div class="col-sm-10">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                      <label class="btn btn-default <?php echo $jade_account_latestorders ? 'active' : ''; ?>"><input type="radio" name="jade_account_latestorders" value="1" <?php echo $jade_account_latestorders ? 'checked="checked"' : ''; ?> /> <?php echo $text_enabled; ?></label>
                      <label class="btn btn-default <?php echo !$jade_account_latestorders ? 'active' : ''; ?>"><input type="radio" name="jade_account_latestorders" value="0" <?php echo !$jade_account_latestorders ? 'checked="checked"' : ''; ?> /> <?php echo $text_disabled; ?></label>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_latest_orders; ?></label>
                  <div class="col-sm-10">
                    <?php foreach ($languages as $language) { ?>
                    <div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
                      <input type="text" name="jade_account_description[<?php echo $language['language_id']; ?>][latest_orders_title]" value="<?php echo isset($jade_account_description[$language['language_id']]['latest_orders_title']) ? $jade_account_description[$language['language_id']]['latest_orders_title'] : ''; ?>" placeholder="<?php echo $entry_latest_orders; ?>" class="form-control" />
                    </div>
                    <?php if (isset($error_latest_orders_title[$language['language_id']])) { ?>
                    <div class="text-danger"><?php echo $error_latest_orders_title[$language['language_id']]; ?></div>
                    <?php } ?>
                    <?php } ?>
                  </div>
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $text_modules; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_columnleft; ?></label>
                  <div class="col-sm-10">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                      <label class="btn btn-default <?php echo $jade_account_columnleft ? 'active' : ''; ?>"><input type="radio" name="jade_account_columnleft" value="1" <?php echo $jade_account_columnleft ? 'checked="checked"' : ''; ?> /> <?php echo $text_show; ?></label>
                      <label class="btn btn-default <?php echo !$jade_account_columnleft ? 'active' : ''; ?>"><input type="radio" name="jade_account_columnleft" value="0" <?php echo !$jade_account_columnleft ? 'checked="checked"' : ''; ?> /> <?php echo $text_hide; ?></label>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_columnright; ?></label>
                  <div class="col-sm-10">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                      <label class="btn btn-default <?php echo $jade_account_columnright ? 'active' : ''; ?>"><input type="radio" name="jade_account_columnright" value="1" <?php echo $jade_account_columnright ? 'checked="checked"' : ''; ?> /> <?php echo $text_show; ?></label>
                      <label class="btn btn-default <?php echo !$jade_account_columnright ? 'active' : ''; ?>"><input type="radio" name="jade_account_columnright" value="0" <?php echo !$jade_account_columnright ? 'checked="checked"' : ''; ?> /> <?php echo $text_hide; ?></label>
                    </div>
                  </div>
                </div>
              </fieldset>
            </div>
            <div class="tab-pane" id="tab-profile">
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_display_picture; ?></label>
                <div class="col-sm-10">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-default <?php echo $jade_account_display_picture ? 'active' : ''; ?>"><input type="radio" name="jade_account_display_picture" value="1" <?php echo $jade_account_display_picture ? 'checked="checked"' : ''; ?> /> <?php echo $text_show; ?></label>
                    <label class="btn btn-default <?php echo !$jade_account_display_picture ? 'active' : ''; ?>"><input type="radio" name="jade_account_display_picture" value="0" <?php echo !$jade_account_display_picture ? 'checked="checked"' : ''; ?> /> <?php echo $text_hide; ?></label>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-image"><?php echo $entry_default_image; ?></label>
                <div class="col-sm-10"><a href="" id="thumb-image" data-toggle="image" class="img-thumbnail"><img src="<?php echo $thumb; ?>" alt="" title="" data-placeholder="<?php echo $placeholder; ?>" /></a>
                  <input type="hidden" name="jade_account_default_image" value="<?php echo $jade_account_default_image; ?>" id="input-image" />
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_image_allow; ?></label>
                <div class="col-sm-10">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-default <?php echo $jade_account_image_allow ? 'active' : ''; ?>"><input type="radio" name="jade_account_image_allow" value="1" <?php echo $jade_account_image_allow ? 'checked="checked"' : ''; ?> /> <?php echo $text_yes; ?></label>
                    <label class="btn btn-default <?php echo !$jade_account_image_allow ? 'active' : ''; ?>"><input type="radio" name="jade_account_image_allow" value="0" <?php echo !$jade_account_image_allow ? 'checked="checked"' : ''; ?> /> <?php echo $text_no; ?></label>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_dp_size; ?></label>
                <div class="col-sm-10">
                  <div class="col-sm-3" style="padding-left: 0;">
                    <div class="input-group">
                      <input type="text" name="jade_account_dp_width" value="<?php echo $jade_account_dp_width; ?>" placeholder="<?php echo $entry_width; ?>" class="form-control" />
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-primary"><i class="fa fa-arrows-h"></i></button>
                      </span>
                    </div>
                    <?php if ($error_dp_width) { ?>
                    <div class="text-danger"><?php echo $error_dp_width; ?></div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-url">
              <div class="row">
                <div class="col-sm-3">
                  <ul class="nav nav-pills nav-stacked" id="url">
                    <?php $url_row = 0; ?>
                    <?php foreach ($urls as $url) { ?>
                    <li><a href="#tab-url<?php echo $url_row; ?>" data-toggle="tab"><i class="fa fa-minus-circle" onclick="$('a[href=\'#tab-url<?php echo $url_row; ?>\']').parent().remove(); $('#tab-url<?php echo $url_row; ?>').remove(); $('#url a:first').tab('show');"></i> <?php echo $url['desc'][$languageid]['name']; ?></a></li>
                    <?php $url_row++; ?>
                    <?php } ?>
                    <li>
                      <a class="btn btn-primary" onclick="addJadeAccountLink();"><i class="fa fa-plus-circle"></i> <?php echo $button_add_url; ?></a>
                    </li>
                  </ul>
                </div>
                <div class="col-sm-9">
                  <div class="tab-content">
                    <?php $url_row = 0; ?>
                    <?php foreach ($urls as $url) { ?>
                    <div class="tab-pane" id="tab-url<?php echo $url_row; ?>">
                      <div class="form-group required">
                        <label class="col-sm-2 control-label"><?php echo $entry_name; ?></label>
                        <div class="col-sm-10">
                          <?php foreach ($languages as $language) { ?>
                          <div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
                            <input type="text" name="jade_account_url[<?php echo $url_row; ?>][desc][<?php echo $language['language_id']; ?>][name]" value="<?php echo isset($url['desc'][$language['language_id']]['name']) ? $url['desc'][$language['language_id']]['name'] : ''; ?>" placeholder="<?php echo $entry_name; ?>" class="form-control" />
                          </div>
                          <?php if (isset($error_url_title[$url_row][$language['language_id']])) { ?>
                          <div class="text-danger"><?php echo $error_url_title[$url_row][$language['language_id']]; ?></div>
                          <?php } ?>
                          <?php } ?>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_url; ?></label>
                        <div class="col-sm-10">
                          <input type="text" name="jade_account_url[<?php echo $url_row; ?>][url]" value="<?php echo $url['url']; ?>" placeholder="<?php echo $entry_url; ?>" class="form-control" />
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_icon; ?></label>
                        <div class="col-sm-10">
                          <input type="text" name="jade_account_url[<?php echo $url_row; ?>][icon]" value="<?php echo $url['icon']; ?>" placeholder="<?php echo $entry_icon; ?>" class="form-control" />
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_sort_order; ?></label>
                        <div class="col-sm-10">
                          <input type="text" name="jade_account_url[<?php echo $url_row; ?>][sort_order]" value="<?php echo $url['sort_order']; ?>" placeholder="<?php echo $entry_icon; ?>" class="form-control" />
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_class; ?></label>
                        <div class="col-sm-10">
                          <input type="text" name="jade_account_url[<?php echo $url_row; ?>][class]" value="<?php echo $url['class']; ?>" placeholder="<?php echo $entry_class; ?>" class="form-control" />
                        </div>
                      </div>
                    </div>
                    <?php $url_row++; ?>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-affiliate-url">
              <fieldset>
                <legend class="notopmagin"><?php echo $text_affiliate_setting; ?></legend>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_affiliate_status; ?></label>
                  <div class="col-sm-10">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                      <label class="btn btn-default <?php echo $jade_account_affiliate_status ? 'active' : ''; ?>"><input type="radio" name="jade_account_affiliate_status" value="1" <?php echo $jade_account_affiliate_status ? 'checked="checked"' : ''; ?> /> <?php echo $text_enabled; ?></label>
                      <label class="btn btn-default <?php echo !$jade_account_affiliate_status ? 'active' : ''; ?>"><input type="radio" name="jade_account_affiliate_status" value="0" <?php echo !$jade_account_affiliate_status ? 'checked="checked"' : ''; ?> /> <?php echo $text_disabled; ?></label>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label"><?php echo $entry_affiliate_title; ?></label>
                  <div class="col-sm-10">
                    <?php foreach ($languages as $language) { ?>
                    <div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
                      <input type="text" name="jade_account_description[<?php echo $language['language_id']; ?>][affiliate_title]" value="<?php echo isset($jade_account_description[$language['language_id']]['affiliate_title']) ? $jade_account_description[$language['language_id']]['affiliate_title'] : ''; ?>" placeholder="<?php echo $entry_affiliate_title; ?>" class="form-control" />
                    </div>
                    <?php if (isset($error_affiliate_title[$language['language_id']])) { ?>
                    <div class="text-danger"><?php echo $error_affiliate_title[$language['language_id']]; ?></div>
                    <?php } ?>
                    <?php } ?>
                  </div>
                </div>
              </fieldset>
              <fieldset>
                <legend><?php echo $text_affiliate_links; ?></legend>
                <div class="row">
                  <div class="col-sm-3">
                    <ul class="nav nav-pills nav-stacked" id="affiliate-url">
                      <?php $affiliate_url_row = 0; ?>
                      <?php foreach ($affiliate_urls as $affiliate_url) { ?>
                      <li><a href="#tab-affiliate-url<?php echo $affiliate_url_row; ?>" data-toggle="tab"><i class="fa fa-minus-circle" onclick="$('a[href=\'#tab-affiliate-url<?php echo $affiliate_url_row; ?>\']').parent().remove(); $('#tab-affiliate-url<?php echo $affiliate_url_row; ?>').remove(); $('#affiliate-url a:first').tab('show');"></i> <?php echo !empty($affiliate_url['desc'][$languageid]['name']) ? $affiliate_url['desc'][$languageid]['name'] : $text_custom_url; ?></a></li>
                      <?php $affiliate_url_row++; ?>
                      <?php } ?>
                      <li>
                        <a class="btn btn-primary" onclick="addJadeAffiliateLink();"><i class="fa fa-plus-circle"></i> <?php echo $button_add_url; ?></a>
                      </li>
                    </ul>
                  </div>
                  <div class="col-sm-9">
                    <div class="tab-content">
                      <?php $affiliate_url_row = 0; ?>
                      <?php foreach ($affiliate_urls as $affiliate_url) { ?>
                      <div class="tab-pane" id="tab-affiliate-url<?php echo $affiliate_url_row; ?>">
                        <div class="form-group required">
                          <label class="col-sm-2 control-label"><?php echo $entry_name; ?></label>
                          <div class="col-sm-10">
                            <?php foreach ($languages as $language) { ?>
                            <div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
                              <input type="text" name="jade_account_affiliate_url[<?php echo $affiliate_url_row; ?>][desc][<?php echo $language['language_id']; ?>][name]" value="<?php echo isset($affiliate_url['desc'][$language['language_id']]['name']) ? $affiliate_url['desc'][$language['language_id']]['name'] : ''; ?>" placeholder="<?php echo $entry_name; ?>" class="form-control" />
                            </div>
                            <?php if (isset($error_affiliate_url_title[$affiliate_url_row][$language['language_id']])) { ?>
                            <div class="text-danger"><?php echo $error_affiliate_url_title[$affiliate_url_row][$language['language_id']]; ?></div>
                            <?php } ?>
                            <?php } ?>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-2 control-label"><?php echo $entry_url; ?></label>
                          <div class="col-sm-10">
                            <input type="text" name="jade_account_affiliate_url[<?php echo $affiliate_url_row; ?>][url]" value="<?php echo $affiliate_url['url']; ?>" placeholder="<?php echo $entry_url; ?>" class="form-control" />
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-2 control-label"><?php echo $entry_icon; ?></label>
                          <div class="col-sm-10">
                            <input type="text" name="jade_account_affiliate_url[<?php echo $affiliate_url_row; ?>][icon]" value="<?php echo $affiliate_url['icon']; ?>" placeholder="<?php echo $entry_icon; ?>" class="form-control" />
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-2 control-label"><?php echo $entry_sort_order; ?></label>
                          <div class="col-sm-10">
                            <input type="text" name="jade_account_affiliate_url[<?php echo $affiliate_url_row; ?>][sort_order]" value="<?php echo $affiliate_url['sort_order']; ?>" placeholder="<?php echo $entry_icon; ?>" class="form-control" />
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-2 control-label"><?php echo $entry_class; ?></label>
                          <div class="col-sm-10">
                            <input type="text" name="jade_account_affiliate_url[<?php echo $affiliate_url_row; ?>][class]" value="<?php echo $affiliate_url['class']; ?>" placeholder="<?php echo $entry_class; ?>" class="form-control" />
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-2 control-label"><?php echo $entry_logintype; ?></label>
                          <div class="col-sm-10">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                              <label class="btn btn-default <?php echo $affiliate_url['logintype'] == 'register' ? 'active' : ''; ?>"><input type="radio" name="jade_account_affiliate_url[<?php echo $affiliate_url_row; ?>][logintype]" value="register" <?php echo $affiliate_url['logintype'] == 'register' ? 'checked="checked"' : ''; ?> /> <?php echo $text_register; ?></label>
                              <label class="btn btn-default <?php echo $affiliate_url['logintype'] == 'logged' ? 'active' : ''; ?>"><input type="radio" name="jade_account_affiliate_url[<?php echo $affiliate_url_row; ?>][logintype]" value="logged" <?php echo $affiliate_url['logintype'] == 'logged' ? 'checked="checked"' : ''; ?> /> <?php echo $text_logged; ?></label>
                            </div>
                          </div>
                        </div>
                      </div>
                      <?php $affiliate_url_row++; ?>
                      <?php } ?>
                    </div>
                  </div>
                </div>
              </fieldset>
            </div>
            <div class="tab-pane" id="tab-product">
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_product_status; ?></label>
                <div class="col-sm-10">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-default <?php echo $jade_account_product_status ? 'active' : ''; ?>"><input type="radio" name="jade_account_product_status" value="1" <?php echo $jade_account_product_status ? 'checked="checked"' : ''; ?> /> <?php echo $text_enabled; ?></label>
                    <label class="btn btn-default <?php echo !$jade_account_product_status ? 'active' : ''; ?>"><input type="radio" name="jade_account_product_status" value="0" <?php echo !$jade_account_product_status ? 'checked="checked"' : ''; ?> /> <?php echo $text_disabled; ?></label>
                  </div>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label"><?php echo $entry_offer_title; ?></label>
                <div class="col-sm-10">
                  <?php foreach ($languages as $language) { ?>
                  <div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
                    <input type="text" name="jade_account_description[<?php echo $language['language_id']; ?>][offer_title]" value="<?php echo isset($jade_account_description[$language['language_id']]['offer_title']) ? $jade_account_description[$language['language_id']]['offer_title'] : ''; ?>" placeholder="<?php echo $entry_offer_title; ?>" class="form-control" />
                  </div>
                  <?php if (isset($error_offer_title[$language['language_id']])) { ?>
                  <div class="text-danger"><?php echo $error_offer_title[$language['language_id']]; ?></div>
                  <?php } ?>
                  <?php } ?>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-product"><span data-toggle="tooltip" title="<?php echo $help_offer_product; ?>"><?php echo $entry_offer_product; ?></span></label>
                <div class="col-sm-10">
                  <input type="text" name="product_name" value="" placeholder="<?php echo $entry_offer_product; ?>" id="input-product" class="form-control" style="height: 50px;"/>
                  <div id="offer-product" class="well well-sm" style="height: 150px; overflow: auto;">
                    <?php foreach ($products as $product) { ?>
                    <div id="offer-product<?php echo $product['product_id']; ?>"><i class="fa fa-minus-circle"></i> <?php echo $product['name']; ?>
                      <input type="hidden" name="jade_account_product[]" value="<?php echo $product['product_id']; ?>" />
                    </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label"><?php echo $entry_image_size; ?></label>
                <div class="col-sm-10">
                  <div class="col-sm-3" style="padding-left: 0;">
                    <div class="input-group">
                      <input type="text" name="jade_account_width" value="<?php echo $jade_account_width; ?>" placeholder="<?php echo $entry_width; ?>" class="form-control" />
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-primary"><i class="fa fa-arrows-h"></i></button>
                      </span>
                    </div>
                  </div>
                  <div class="col-sm-3">
                    <div class="input-group">
                      <input type="text" name="jade_account_height" value="<?php echo $jade_account_height; ?>" placeholder="<?php echo $entry_height; ?>" class="form-control" />
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-primary"><i class="fa fa-arrows-v"></i></button>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label"><?php echo $entry_description_limit; ?></label>
                <div class="col-sm-4">
                  <div class="input-group">
                    <input type="text" name="jade_account_description_limit" value="<?php echo $jade_account_description_limit; ?>" class="form-control" />
                    <span class="input-group-btn"><button type="button" class="btn btn-primary"><?php echo $text_char; ?></button></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-template">
              <div class="form-group">
                <div class="col-sm-12">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <?php foreach($templates as $template) { ?>
                    <label class="col-sm-6 col-xs-6 col-md-3 btn btn-default <?php echo $jade_account_template == $template['type'] ? 'active' : ''; ?>"><input type="radio" name="jade_account_template" value="<?php echo $template['type']; ?>" <?php echo $jade_account_template == $template['type'] ? 'checked="checked"' : ''; ?> /> <?php echo $template['text']; ?>
                      <img class="img-responsive" src="<?php echo $template['preview']; ?>" altt="<?php echo $template['text']; ?>">
                      <div class="selected-temp"><i class="fa fa-check"></i></div>
                    </label>

                    <?php } ?>
                  </div>
                  <?php if ($error_template) { ?>
                  <div class="text-danger"><?php echo $error_template; ?></div>
                  <?php } ?>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-colors">
              <div class="row">
              <?php foreach ($account_colors as $account_color_chunk) { ?>
                <div class="col-sm-5">
                <?php foreach ($account_color_chunk as $account_color) { ?>
                  <div class="form-group">
                    <label class="col-sm-12 control-label" style="text-align: left; padding-bottom: 5px;"><?php echo $account_color['text']; ?></label>
                    <div class="col-sm-12">
                      <div class="input-group colorpicker colorpicker-component">
                        <input type="text" name="jade_account_colors[<?php echo $account_color['var']; ?>]" value="<?php echo isset($jade_account_colors[$account_color['var']]) ? $jade_account_colors[$account_color['var']] : ''; ?>" class="form-control" />
                        <span class="input-group-addon"><i></i></span>
                      </div>
                    </div>
                  </div>
                <?php } ?>
                </div>
              <?php } ?>
              </div>
              <br>
              <div class="buttons text-center">
                <button type="button" class="btn btn-warning" onclick="resetall();"><i class="fa fa-refresh"></i> <?php echo $button_reset; ?></button>
              </div>
            </div>
            <div class="tab-pane" id="tab-contact">
              <div class="row">
                <div class="col-sm-8">
                  <ul class="nav nav-tabs" id="email">
                    <li class="active"><a href="#tab-contactlanguage" data-toggle="tab"><i class="fa fa-language"></i> <?php echo $tab_contactlanguage; ?></a></li>
                    <li><a href="#tab-customeremail" data-toggle="tab"><i class="fa fa-user"></i> <?php echo $tab_customeremail; ?></a></li>
                    <li><a href="#tab-adminemail" data-toggle="tab"><i class="fa fa-bell"></i> <?php echo $tab_adminemail; ?></a></li>
                  </ul>
                  <div class="tab-content">
                    <div class="tab-pane active" id="tab-contactlanguage">
                      <ul class="nav nav-tabs" id="contactlanguage">
                        <?php foreach ($languages as $language) { ?>
                        <li><a href="#contactlanguage<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                        <?php } ?>
                      </ul>
                      <div class="tab-content">
                        <?php foreach ($languages as $language) { ?>
                        <div class="tab-pane" id="contactlanguage<?php echo $language['language_id']; ?>">
                          <div class="form-group required">
                            <label class="col-sm-2 control-label"><?php echo $entry_popup_title; ?></label>
                            <div class="col-sm-10">
                              <input type="text" name="jade_account_description[<?php echo $language['language_id']; ?>][popup_title]" placeholder="<?php echo $entry_heading_title; ?>" class="form-control" value="<?php echo isset($jade_account_description[$language['language_id']]['popup_title']) ? $jade_account_description[$language['language_id']]['popup_title'] : ''; ?>">
                              <?php if (isset($error_popup_title[$language['language_id']])) { ?>
                              <div class="text-danger"><?php echo $error_popup_title[$language['language_id']]; ?></div>
                              <?php } ?>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-sm-2 control-label"><?php echo $entry_submit_button_text; ?></label>
                            <div class="col-sm-10">
                              <input type="text" name="jade_account_description[<?php echo $language['language_id']; ?>][submit_button_text]" placeholder="<?php echo $entry_submit_button_text; ?>" class="form-control" value="<?php echo isset($jade_account_description[$language['language_id']]['submit_button_text']) ? $jade_account_description[$language['language_id']]['submit_button_text'] : ''; ?>">
                              <?php if (isset($error_submit_button_text[$language['language_id']])) { ?>
                              <div class="text-danger"><?php echo $error_submit_button_text[$language['language_id']]; ?></div>
                              <?php } ?>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-sm-2 control-label"><?php echo $entry_description; ?></label>
                            <div class="col-sm-10">
                              <textarea name="jade_account_description[<?php echo $language['language_id']; ?>][description]" placeholder="<?php echo $entry_description; ?>" id="input-description<?php echo $language['language_id']; ?>" class="form-control" data-toggle="summernote" data-lang="{{ summernote }}"><?php echo isset($jade_account_description[$language['language_id']]['description']) ? $jade_account_description[$language['language_id']]['description'] : ''; ?></textarea>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-sm-2 control-label"><?php echo $entry_success_message; ?></label>
                            <div class="col-sm-10">
                              <input type="text" name="jade_account_description[<?php echo $language['language_id']; ?>][success_message]" placeholder="<?php echo $entry_success_message; ?>" class="form-control" value="<?php echo isset($jade_account_description[$language['language_id']]['success_message']) ? $jade_account_description[$language['language_id']]['success_message'] : ''; ?>">
                              <?php if (isset($error_success_message[$language['language_id']])) { ?>
                              <div class="text-danger"><?php echo $error_success_message[$language['language_id']]; ?></div>
                              <?php } ?>
                            </div>
                          </div>
                        </div>
                        <?php } ?>
                      </div>
                    </div>
                    <div class="tab-pane" id="tab-customeremail">
                      <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_customeremail_status; ?></label>
                        <div class="col-sm-3">
                          <select name="jade_account_customeremail_status" class="form-control">
                            <?php if ($jade_account_customeremail_status) { ?>
                            <option value="1" selected="selected"><?php echo $text_yes; ?></option>
                            <option value="0"><?php echo $text_no; ?></option>
                            <?php } else { ?>
                            <option value="1"><?php echo $text_yes; ?></option>
                            <option value="0" selected="selected"><?php echo $text_no; ?></option>
                            <?php } ?>
                          </select>
                        </div>
                      </div>
                      <ul class="nav nav-tabs" id="customeremail-lang">
                        <?php foreach ($languages as $language) { ?>
                        <li><a href="#customeremail-lang<?php echo $language['language_id']; ?>" data-toggle="tab"><?php if(VERSION >= '2.2.0.0') { ?>
                        <img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" />
                        <?php } else{ ?>
                        <img src="view/image/flags/<?php echo $language['image']; ?>" title="<?php echo $language['name']; ?>" />
                        <?php } ?> <?php echo $language['name']; ?></a></li>
                        <?php } ?>
                      </ul>
                      <div class="tab-content">
                        <?php foreach ($languages as $language) { ?>
                        <div class="tab-pane" id="customeremail-lang<?php echo $language['language_id']; ?>">
                          <div class="form-group required">
                            <label class="col-sm-2 control-label"><?php echo $entry_customersubject; ?></label>
                            <div class="col-sm-10">
                              <input type="text" name="jade_account_email[<?php echo $language['language_id']; ?>][customersubject]" value="<?php echo isset($jade_account_email[$language['language_id']]) ? $jade_account_email[$language['language_id']]['customersubject'] : ''; ?>" placeholder="<?php echo $entry_customersubject; ?>" id="input-customersubject<?php echo $language['language_id']; ?>" class="form-control" />
                              <?php if (isset($error_customersubject[$language['language_id']])) { ?>
                              <div class="text-danger"><?php echo $error_customersubject[$language['language_id']]; ?></div>
                              <?php } ?>
                            </div>
                          </div>
                          <div class="form-group required">
                            <label class="col-sm-2 control-label"><?php echo $entry_customermessage; ?></label>
                            <div class="col-sm-10">
                              <textarea name="jade_account_email[<?php echo $language['language_id']; ?>][customermessage]" placeholder="<?php echo $entry_customermessage; ?>" id="input-customermessage<?php echo $language['language_id']; ?>" class="form-control" data-toggle="summernote" data-lang="{{ summernote }}"><?php echo isset($jade_account_email[$language['language_id']]) ? $jade_account_email[$language['language_id']]['customermessage'] : ''; ?></textarea>
                              <?php if (isset($error_customermessage[$language['language_id']])) { ?>
                              <div class="text-danger"><?php echo $error_customermessage[$language['language_id']]; ?></div>
                              <?php } ?>
                            </div>
                          </div>
                        </div>
                        <?php } ?>
                      </div>
                    </div>
                    <div class="tab-pane" id="tab-adminemail">
                      <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_adminemail_status; ?></label>
                        <div class="col-sm-3">
                          <select name="jade_account_adminemail_status" class="form-control">
                            <?php if ($jade_account_adminemail_status) { ?>
                            <option value="1" selected="selected"><?php echo $text_yes; ?></option>
                            <option value="0"><?php echo $text_no; ?></option>
                            <?php } else { ?>
                            <option value="1"><?php echo $text_yes; ?></option>
                            <option value="0" selected="selected"><?php echo $text_no; ?></option>
                            <?php } ?>
                          </select>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label"><?php echo $entry_adminemail; ?></label>
                        <div class="col-sm-10">
                          <input type="text" name="jade_account_adminemail_email" value="<?php echo $jade_account_adminemail_email; ?>" placeholder="<?php echo $entry_adminemail; ?>" class="form-control" />
                          <?php if($error_adminemail) { ?>
                          <div class="text-danger"><?php echo $error_adminemail; ?></div>
                          <?php } ?>
                        </div>
                      </div>
                      <ul class="nav nav-tabs" id="adminemail-lang">
                        <?php foreach ($languages as $language) { ?>
                        <li><a href="#adminemail-lang<?php echo $language['language_id']; ?>" data-toggle="tab"><?php if(VERSION >= '2.2.0.0') { ?>
                        <img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" />
                        <?php } else{ ?>
                        <img src="view/image/flags/<?php echo $language['image']; ?>" title="<?php echo $language['name']; ?>" />
                        <?php } ?> <?php echo $language['name']; ?></a></li>
                        <?php } ?>
                      </ul>
                      <div class="tab-content">
                        <?php foreach ($languages as $language) { ?>
                        <div class="tab-pane" id="adminemail-lang<?php echo $language['language_id']; ?>">
                          <div class="form-group required">
                            <label class="col-sm-2 control-label"><?php echo $entry_adminsubject; ?></label>
                            <div class="col-sm-10">
                              <input type="text" name="jade_account_email[<?php echo $language['language_id']; ?>][adminsubject]" value="<?php echo isset($jade_account_email[$language['language_id']]) ? $jade_account_email[$language['language_id']]['adminsubject'] : ''; ?>" placeholder="<?php echo $entry_adminsubject; ?>" id="input-adminsubject<?php echo $language['language_id']; ?>" class="form-control" />
                              <?php if (isset($error_adminsubject[$language['language_id']])) { ?>
                              <div class="text-danger"><?php echo $error_adminsubject[$language['language_id']]; ?></div>
                              <?php } ?>
                            </div>
                          </div>
                          <div class="form-group required">
                            <label class="col-sm-2 control-label"><?php echo $entry_adminmessage; ?></label>
                            <div class="col-sm-10">
                              <textarea name="jade_account_email[<?php echo $language['language_id']; ?>][adminmessage]" placeholder="<?php echo $entry_adminmessage; ?>" id="input-adminmessage<?php echo $language['language_id']; ?>" class="form-control" data-toggle="summernote" data-lang="{{ summernote }}"><?php echo isset($jade_account_email[$language['language_id']]) ? $jade_account_email[$language['language_id']]['adminmessage'] : ''; ?></textarea>
                              <?php if (isset($error_adminmessage[$language['language_id']])) { ?>
                              <div class="text-danger"><?php echo $error_adminmessage[$language['language_id']]; ?></div>
                              <?php } ?>
                            </div>
                          </div>
                        </div>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                      <thead>
                        <tr>
                          <td class="text-left"><?php echo $column_variale; ?></td>
                          <td class="text-left"><?php echo $column_variale_value; ?></td>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td class="text-left">[STORE_NAME]</td>
                          <td class="text-left"><?php echo $sc_store_name; ?></td>
                        </tr>
                        <tr>
                          <td class="text-left">[STORE_URL]</td>
                          <td class="text-left"><?php echo $sc_store_url; ?></td>
                        </tr>
                        <tr>
                          <td class="text-left">[STORE_LOGO]</td>
                          <td class="text-left"><?php echo $sc_store_logo; ?></td>
                        </tr>
                        <tr>
                          <td class="text-left">[USER_NAME]</td>
                          <td class="text-left"><?php echo $sc_name; ?></td>
                        </tr>
                        <tr>
                          <td class="text-left">[USER_EMAIL]</td>
                          <td class="text-left"><?php echo $sc_email; ?></td>
                        </tr>
                        <tr>
                          <td class="text-left">[USER_TELEPHONE]</td>
                          <td class="text-left"><?php echo $sc_telephone; ?></td>
                        </tr>
                        <tr>
                          <td class="text-left">[USER_ENQUIRY]</td>
                          <td class="text-left"><?php echo $sc_enquiry; ?></td>
                        </tr>
                        <tr>
                          <td class="text-left">[DATE_ADDED]</td>
                          <td class="text-left"><?php echo $sc_date_added; ?></td>
                        </tr>
                      </tbody>
                    </table>
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
<link href="view/javascript/codemirror/lib/codemirror.css" rel="stylesheet" />
<link href="view/javascript/codemirror/theme/monokai.css" rel="stylesheet" />
<script type="text/javascript" src="view/javascript/codemirror/lib/codemirror.js"></script>
<script type="text/javascript" src="view/javascript/codemirror/lib/xml.js"></script>
<script type="text/javascript" src="view/javascript/codemirror/lib/formatting.js"></script>

<script type="text/javascript" src="view/javascript/summernote/summernote.js"></script>
<link href="view/javascript/summernote/summernote.css" rel="stylesheet" />
<script type="text/javascript" src="view/javascript/summernote/summernote-image-attributes.js"></script>
<script type="text/javascript" src="view/javascript/summernote/opencart.js"></script>
<script type="text/javascript"><!--
$('#customeremail-lang a:first').tab('show');
$('#adminemail-lang a:first').tab('show');
$('#contactlanguage a:first').tab('show');
//--></script>
<script type="text/javascript"><!--
$(function() {
  $('.colorpicker').colorpicker();
});
//--></script>
<script type="text/javascript"><!--
$('input[name=\'product_name\']').autocomplete({
  source: function(request, response) {
    $.ajax({
      url: 'index.php?route=catalog/product/autocomplete&user_token=<?php echo $user_token; ?>&filter_name=' +  encodeURIComponent(request),
      dataType: 'json',
      success: function(json) {
        response($.map(json, function(item) {
          return {
            label: item['name'],
            value: item['product_id']
          }
        }));
      }
    });
  },
  select: function(item) {
    $('input[name=\'product_name\']').val('');

    $('#featured-product' + item['value']).remove();

    $('#offer-product').append('<div id="offer-product' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="jade_account_product[]" value="' + item['value'] + '" /></div>');
  }
});

$('#offer-product').delegate('.fa-minus-circle', 'click', function() {
  $(this).parent().remove();
});
//--></script>
<script type="text/javascript"><!--
var url_row = <?php echo $url_row; ?>;
function addJadeAccountLink() {
  html = '<div class="tab-pane" id="tab-url' + url_row + '">';
    html += '<div class="form-group required">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_name; ?></label>';
      html += '<div class="col-sm-10">';
        <?php foreach ($languages as $language) { ?>
        html += '<div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>';
          html += '<input type="text" name="jade_account_url[' + url_row + '][desc][<?php echo $language['language_id']; ?>][name]" value="" placeholder="<?php echo $entry_name; ?>" class="form-control" />';
        html += '</div>';
        <?php } ?>
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_url; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<input type="text" name="jade_account_url[' + url_row + '][url]" value="" placeholder="<?php echo $entry_url; ?>" class="form-control" />';
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_icon; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<input type="text" name="jade_account_url[' + url_row + '][icon]" value="" placeholder="<?php echo $entry_icon; ?>" class="form-control" />';
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_sort_order; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<input type="text" name="jade_account_url[' + url_row + '][sort_order]" value="" placeholder="<?php echo $entry_sort_order; ?>" class="form-control" />';
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_class; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<input type="text" name="jade_account_url[' + url_row + '][class]" value="" placeholder="<?php echo $entry_class; ?>" class="form-control" />';
      html += '</div>';
    html += '</div>';
  html += '</div>';

  $('#tab-url .tab-content').append(html);

  $('#url > li:last-child').before('<li><a href="#tab-url' + url_row + '" data-toggle="tab"><i class="fa fa-minus-circle" onclick=" $(\'#url a:first\').tab(\'show\');$(\'a[href=\\\'#tab-url' + url_row + '\\\']\').parent().remove(); $(\'#tab-url' + url_row + '\').remove();"></i> <?php echo $text_custom_url; ?></li>');

  $('#url a[href=\'#tab-url' + url_row + '\']').tab('show');

  $('[data-toggle=\'tooltip\']').tooltip({
    container: 'body',
    html: true
  });

  url_row++;
}

$('#url a:first').tab('show');
//--></script>

<script type="text/javascript"><!--
var affiliate_url_row = <?php echo $affiliate_url_row; ?>;
function addJadeAffiliateLink() {
  html = '<div class="tab-pane" id="tab-affiliate-url' + affiliate_url_row + '">';
    html += '<div class="form-group required">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_name; ?></label>';
      html += '<div class="col-sm-10">';
        <?php foreach ($languages as $language) { ?>
        html += '<div class="input-group"><span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>';
          html += '<input type="text" name="jade_account_affiliate_url[' + affiliate_url_row + '][desc][<?php echo $language['language_id']; ?>][name]" value="" placeholder="<?php echo $entry_name; ?>" class="form-control" />';
        html += '</div>';
        <?php } ?>
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_url; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<input type="text" name="jade_account_affiliate_url[' + affiliate_url_row + '][url]" value="" placeholder="<?php echo $entry_url; ?>" class="form-control" />';
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_icon; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<input type="text" name="jade_account_affiliate_url[' + affiliate_url_row + '][icon]" value="" placeholder="<?php echo $entry_icon; ?>" class="form-control" />';
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_sort_order; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<input type="text" name="jade_account_affiliate_url[' + affiliate_url_row + '][sort_order]" value="" placeholder="<?php echo $entry_sort_order; ?>" class="form-control" />';
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_class; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<input type="text" name="jade_account_affiliate_url[' + affiliate_url_row + '][class]" value="" placeholder="<?php echo $entry_class; ?>" class="form-control" />';
      html += '</div>';
    html += '</div>';
    html += '<div class="form-group">';
      html += '<label class="col-sm-2 control-label"><?php echo $entry_logintype; ?></label>';
      html += '<div class="col-sm-10">';
        html += '<div class="btn-group btn-group-toggle" data-toggle="buttons">';
          html += '<label class="btn btn-default active"><input type="radio" name="jade_account_affiliate_url[' + affiliate_url_row + '][logintype]" value="register" checked="checked" /> <?php echo $text_register; ?></label>';
          html += '<label class="btn btn-default"><input type="radio" name="jade_account_affiliate_url[' + affiliate_url_row + '][logintype]" value="logged" /> <?php echo $text_logged; ?></label>';
        html += '</div>';
      html += '</div>';
    html += '</div>';
  html += '</div>';

  $('#tab-affiliate-url .tab-content').append(html);

  $('#affiliate-url > li:last-child').before('<li><a href="#tab-affiliate-url' + affiliate_url_row + '" data-toggle="tab"><i class="fa fa-minus-circle" onclick=" $(\'#affiliate-url a:first\').tab(\'show\');$(\'a[href=\\\'#tab-affiliate-url' + affiliate_url_row + '\\\']\').parent().remove(); $(\'#tab-affiliate-url' + affiliate_url_row + '\').remove();"></i> <?php echo $text_custom_url; ?></li>');

  $('#affiliate-url a[href=\'#tab-affiliate-url' + affiliate_url_row + '\']').tab('show');

  $('[data-toggle=\'tooltip\']').tooltip({
    container: 'body',
    html: true
  });

  affiliate_url_row++;
}

$('#affiliate-url a:first').tab('show');
//--></script>
<script type="text/javascript"><!--
function resetall() {
  $('#tab-colors input').val('');
  $('#tab-colors .input-group-addon i').removeAttr('style');
}
//--></script>
</div>
<?php echo $footer; ?>