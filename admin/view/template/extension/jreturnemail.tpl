<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <style type="text/css">
    .jreturn-wrap {
      margin-bottom: 10px;
    }
    .jreturn-wrap:last-child {
      margin-bottom: 0;
    }
    .jreturn-wrap .accordion-heading {
      margin-bottom: 0;
    }
            .jreturn-wrap .card {
              border: 1px solid #303030;
            }
            .jreturn-wrap .card-header {
              color: #fff;
              background: #303030;
              line-height: 1.5;
              padding-top: 10px;
              padding-bottom: 10px;
              padding-left: 10px;
              padding-right: 10px;
              cursor: pointer;
            }
            .jreturn-wrap .card-body {
              padding-top: 10px;
              padding-bottom: 10px;
              padding-left: 10px;
              padding-right: 10px;
            }
            .jreturn-wrap.got-error .card {
              border: 1px solid #c72f1d;
            }
            .jreturn-wrap.got-error .card-header {
              background: #c72f1d;
            }
          </style>
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-jreturnemail" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i> <span class="hidden-sm"><?php echo $button_save; ?></span></button>
      </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-jreturnemail" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-12 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-12">
              <div class="btn-group btn-group-toggle" data-toggle="buttons">
                <label class="btn btn-default <?php if ($jreturnemail_status) { ?>active<?php } ?>"><input type="radio" name="jreturnemail_status" value="1" <?php if ($jreturnemail_status) { ?>checked="checked"<?php } ?> /> <?php echo $text_enabled; ?></label>
                <label class="btn btn-default <?php if (!$jreturnemail_status) { ?>active<?php } ?>"><input type="radio" name="jreturnemail_status" value="0" <?php if (!$jreturnemail_status) { ?>checked="checked"<?php } ?> /> <?php echo $text_disabled; ?></label>
              </div>
            </div>
          </div>
          <div class="well">
            <span><i class="fa fa-info-circle"></i> <?php echo $text_sc_info; ?></span>
            <hr/>
            <div class="accordion jreturn-wrap" id="accordion-shortcodes">
              <div class="card">
                <div class="card-header" id="shortcodes">
                  <h5 class="accordion-heading sc_action" data-toggle="collapse" data-target="#shortcodesContent" aria-expanded="true" aria-controls="shortcodesContent" data-sc_view="<?php echo htmlspecialchars($button_sc_view, ENT_QUOTES, 'UTF-8'); ?>" data-sc_hide="<?php echo htmlspecialchars($button_sc_hide, ENT_QUOTES, 'UTF-8'); ?>" data-sc_view-i="fa fa-eye" data-sc_hide-i="fa fa-eye-slash">
                      <i class="fa fa-eye"></i> <?php echo $button_sc_view; ?>
                  </h5>
                </div>

                <div id="shortcodesContent" class="collapse" aria-labelledby="shortcodes" data-parent="#JReturneMail">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-striped table-bordered">
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
                            <td class="text-left">[STORE_EMAIL]</td>
                            <td class="text-left"><?php echo $sc_store_email; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[STORE_FAX]</td>
                            <td class="text-left"><?php echo $sc_store_fax; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[STORE_TELEPHONE]</td>
                            <td class="text-left"><?php echo $sc_store_telephone; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[PRODUCT_NAME]</td>
                            <td class="text-left"><?php echo $sc_product_name; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[PRODUCT_URL]</td>
                            <td class="text-left"><?php echo $sc_product_url; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[PRODUCT_MODEL]</td>
                            <td class="text-left"><?php echo $sc_product_model; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[PRODUCT_QTY]</td>
                            <td class="text-left"><?php echo $sc_product_qty; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[PRODUCT_OPENED_STATUS]</td>
                            <td class="text-left"><?php echo $sc_product_opened_status; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[PRODUCT_THUMB]</td>
                            <td class="text-left"><?php echo $sc_product_thumb; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[CUSTOMER_ID]</td>
                            <td class="text-left"><?php echo $sc_customer_id; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[CUSTOMER_FIRSTNAME]</td>
                            <td class="text-left"><?php echo $sc_customer_firstname; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[CUSTOMER_LASTNAME]</td>
                            <td class="text-left"><?php echo $sc_customer_lastname; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[CUSTOMER_EMAIL]</td>
                            <td class="text-left"><?php echo $sc_customer_email; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[CUSTOMER_TELEPHONE]</td>
                            <td class="text-left"><?php echo $sc_customer_telephone; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[RETURN_ID]</td>
                            <td class="text-left"><?php echo $sc_return_id; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[RETURN_DATE_ADDED]</td>
                            <td class="text-left"><?php echo $sc_return_date_added; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[RETURN_REASON]</td>
                            <td class="text-left"><?php echo $sc_return_reason; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[RETURN_STATUS]</td>
                            <td class="text-left"><?php echo $sc_return_status; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[RETURN_ACTION]</td>
                            <td class="text-left"><?php echo $sc_return_action; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[RETURN_COMMENT]</td>
                            <td class="text-left"><?php echo $sc_return_comment; ?></td>
                          </tr>
                          <tr>
                            <td class="text-left">[ORDER_ID]</td>
                            <td class="text-left"><?php echo $sc_order_id; ?></td>
                          </tr>
                          <tr class="alert alert-info">
                            <td class="text-left">[ORDER_*]</td>
                            <td class="text-left">You can also us any column from the "`<?php echo DB_PREFIX; ?>order`" table by using shortcode like format <strong>[ORDER_.column]</strong>, where <strong>ORDER_</strong> is prefix, <strong>.column</strong> is column name without space.</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-12 control-label" for="input-admin_dir"><?php echo $entry_admin_dir; ?><br/><span class="help"><?php echo $help_admin_dir; ?></span></label>
            <div class="col-sm-12">
              <input class="form-control" type="text" name="jreturnemail_admin_dir" value="<?php echo $jreturnemail_admin_dir; ?>" />
              <?php if ($error_admin_dir) { ?>
              <div class="text-danger"><?php echo $error_admin_dir; ?></div>
              <?php } ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-12 control-label" for="input-date_format"><?php echo $entry_date_format; ?><br/><span class="help"><?php echo $help_date_format; ?></span></label>
            <div class="col-sm-12">
              <input class="form-control" type="text" name="jreturnemail_date_format" value="<?php echo $jreturnemail_date_format; ?>" />
            </div>
          </div>
          <div class="form-group required">
            <label class="col-sm-12 control-label" for="input-product_thumb"><?php echo $entry_product_thumb; ?></label>
            <div class="col-sm-6">
              <div class="input-group">
                <input class="form-control" type="text" name="jreturnemail_productthumb_width" value="<?php echo $jreturnemail_productthumb_width; ?>" placeholder="<?php echo $entry_width; ?>" />
                <span class="input-group-addon" data-toggle="tooltip" title="<?php echo $entry_width; ?>"><i class="fa fa-arrows-h"></i></span>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="input-group">
                <input class="form-control" type="text" name="jreturnemail_productthumb_height" value="<?php echo $jreturnemail_productthumb_height; ?>" placeholder="<?php echo $entry_height; ?>" />
                <span class="input-group-addon" data-toggle="tooltip" title="<?php echo $entry_height; ?>" ><i class="fa fa-arrows-v"></i></span>
              </div>
            </div>
            <?php if ($error_productthumb) { ?>
            <div class="col-sm-12">
              <div class="text-danger"><?php echo $error_productthumb; ?></div>
            </div>
            <?php } ?>
          </div>



          <div class="accordion jreturn-wrap" id="accordion-adminEmail">
            <div class="card">
              <div class="card-header" id="adminEmail">
                <h5 class="accordion-heading" data-toggle="collapse" data-target="#adminEmailContent" aria-expanded="true" aria-controls="adminEmailContent">
                    <?php echo $text_emailadmin; ?>
                </h5>
              </div>

              <div id="adminEmailContent" class="collapse" aria-labelledby="adminEmail" data-parent="#JReturneMail">
                <div class="card-body">
                  <div class="form-group">
                    <label class="col-sm-12 control-label" for="input-emailadmin"><?php echo $entry_emailadmin; ?><br/><span class="help"><?php echo $help_emailadmin; ?></span></label>
                    <div class="col-sm-12">
                      <input class="form-control" type="text" name="jreturnemail_emailadmin" value="<?php echo $jreturnemail_emailadmin; ?>" />
                      <?php if ($error_emailadmin) { ?>
                      <div class="text-danger"><?php echo $error_emailadmin; ?></div>
                      <?php } ?>
                    </div>
                  </div>
                  <ul class="nav nav-tabs" id="emailadminlanguage">
                    <?php foreach ($languages as $language) { ?>
                    <li><a href="#emailadminlanguage<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                    <?php } ?>
                  </ul>
                  <div class="tab-content">
                    <?php foreach ($languages as $language) { ?>
                    <div class="tab-pane" id="emailadminlanguage<?php echo $language['language_id']; ?>">
                      <div class="form-group required">
                        <label class="col-sm-12 control-label" for="input-adminsubject<?php echo $language['language_id']; ?>"><?php echo $entry_subject; ?></label>
                        <div class="col-sm-12">
                          <input type="text" name="jreturnemail_email[admin][<?php echo $language['language_id']; ?>][subject]" value="<?php echo isset($jreturnemail_email['admin'][$language['language_id']]) ? $jreturnemail_email['admin'][$language['language_id']]['subject'] : ''; ?>" placeholder="<?php echo $entry_subject; ?>" id="input-adminsubject<?php echo $language['language_id']; ?>" class="form-control" />
                          <?php if (isset($error_email['admin'][$language['language_id']]['subject'])) { ?>
                          <div class="text-danger"><?php echo $error_email['admin'][$language['language_id']]['subject']; ?></div>
                          <?php } ?>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-12 control-label" for="input-adminmsg<?php echo $language['language_id']; ?>"><?php echo $entry_msg; ?></label>
                        <div class="col-sm-12">
                          <textarea name="jreturnemail_email[admin][<?php echo $language['language_id']; ?>][msg]" placeholder="<?php echo $entry_msg; ?>" id="input-adminmsg<?php echo $language['language_id']; ?>" data-toggle="summernote" class="form-control summernote"><?php echo isset($jreturnemail_email['admin'][$language['language_id']]) ? $jreturnemail_email['admin'][$language['language_id']]['msg'] : ''; ?></textarea>

                          <?php if (isset($error_email['admin'][$language['language_id']]['msg'])) { ?>
                          <div class="text-danger"><?php echo $error_email['admin'][$language['language_id']]['msg']; ?></div>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="accordion jreturn-wrap" id="accordion-customerEmail">
            <div class="card">
              <div class="card-header" id="customerEmail">
                <h5 class="accordion-heading" data-toggle="collapse" data-target="#customerEmailContent" aria-expanded="true" aria-controls="customerEmailContent">
                    <?php echo $text_emailcustomer; ?>
                </h5>
              </div>

              <div id="customerEmailContent" class="collapse" aria-labelledby="customerEmail" data-parent="#JReturneMail">
                <div class="card-body">
                  <div class="form-group">
                    <label class="col-sm-12 control-label" for="input-emailtocustomer"><?php echo $entry_emailtocustomer; ?><br/><span class="help"><?php echo $help_emailtocustomer; ?></span></label>
                    <div class="col-sm-5">
                      <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-default <?php if ($jreturnemail_emailtocustomer) { ?>active<?php } ?>"><input type="radio" name="jreturnemail_emailtocustomer" value="1" <?php if ($jreturnemail_emailtocustomer) { ?>checked="checked"<?php } ?> /> <?php echo $text_yes; ?></label>
                        <label class="btn btn-default <?php if (!$jreturnemail_emailtocustomer) { ?>active<?php } ?>"><input type="radio" name="jreturnemail_emailtocustomer" value="0" <?php if (!$jreturnemail_emailtocustomer) { ?>checked="checked"<?php } ?> /> <?php echo $text_no; ?></label>
                      </div>
                    </div>
                  </div>
                  <ul class="nav nav-tabs" id="emailcustomerlanguage">
                    <?php foreach ($languages as $language) { ?>
                    <li><a href="#emailcustomerlanguage<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                    <?php } ?>
                  </ul>
                  <div class="tab-content">
                    <?php foreach ($languages as $language) { ?>
                    <div class="tab-pane" id="emailcustomerlanguage<?php echo $language['language_id']; ?>">
                      <div class="form-group required">
                        <label class="col-sm-12 control-label" for="input-customersubject<?php echo $language['language_id']; ?>"><?php echo $entry_subject; ?></label>
                        <div class="col-sm-12">
                          <input type="text" name="jreturnemail_email[customer][<?php echo $language['language_id']; ?>][subject]" value="<?php echo isset($jreturnemail_email['customer'][$language['language_id']]) ? $jreturnemail_email['customer'][$language['language_id']]['subject'] : ''; ?>" placeholder="<?php echo $entry_subject; ?>" id="input-customersubject<?php echo $language['language_id']; ?>" class="form-control" />
                          <?php if (isset($error_email['customer'][$language['language_id']]['subject'])) { ?>
                          <div class="text-danger"><?php echo $error_email['customer'][$language['language_id']]['subject']; ?></div>
                          <?php } ?>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-12 control-label" for="input-customermsg<?php echo $language['language_id']; ?>"><?php echo $entry_msg; ?></label>
                        <div class="col-sm-12">
                          <textarea name="jreturnemail_email[customer][<?php echo $language['language_id']; ?>][msg]" placeholder="<?php echo $entry_msg; ?>" id="input-customermsg<?php echo $language['language_id']; ?>" data-toggle="summernote" class="form-control summernote"><?php echo isset($jreturnemail_email['customer'][$language['language_id']]) ? $jreturnemail_email['customer'][$language['language_id']]['msg'] : ''; ?></textarea>

                          <?php if (isset($error_email['customer'][$language['language_id']]['msg'])) { ?>
                          <div class="text-danger"><?php echo $error_email['customer'][$language['language_id']]['msg']; ?></div>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="accordion jreturn-wrap" id="accordion-customerEmailRH">
            <div class="card">
              <div class="card-header" id="customerEmailRH">
                <h5 class="accordion-heading" data-toggle="collapse" data-target="#customerEmailRHContent" aria-expanded="true" aria-controls="customerEmailRHContent">
                    <?php echo $text_emailcustomer_history; ?>
                </h5>
              </div>

              <div id="customerEmailRHContent" class="collapse" aria-labelledby="customerEmailRH" data-parent="#JReturneMail">
                <div class="card-body">
                  <div class="alert alert-info"><strong><?php echo $text_email_from_admin; ?></strong></div>
                  <div class="form-group">
                    <label class="col-sm-12 control-label" for="input-emailtocustomer_history"><?php echo $entry_emailtocustomer_history; ?><br/><span class="help"><?php echo $help_emailtocustomer_history; ?></span></label>
                    <div class="col-sm-5">
                      <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-default <?php if ($jreturnemail_emailtocustomer_history) { ?>active<?php } ?>"><input type="radio" name="jreturnemail_emailtocustomer_history" value="1" <?php if ($jreturnemail_emailtocustomer_history) { ?>checked="checked"<?php } ?> /> <?php echo $text_yes; ?></label>
                        <label class="btn btn-default <?php if (!$jreturnemail_emailtocustomer_history) { ?>active<?php } ?>"><input type="radio" name="jreturnemail_emailtocustomer_history" value="0" <?php if (!$jreturnemail_emailtocustomer_history) { ?>checked="checked"<?php } ?> /> <?php echo $text_no; ?></label>
                      </div>
                    </div>
                  </div>
                  <div class="alert alert-info"><strong><?php echo $text_info_return_history; ?></strong></div>
                  <ul class="nav nav-tabs" id="emailcustomerhistorylanguage">
                    <?php foreach ($languages as $language) { ?>
                    <li><a href="#emailcustomerhistorylanguage<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                    <?php } ?>
                  </ul>
                  <div class="tab-content">
                    <?php foreach ($languages as $language) { ?>
                    <div class="tab-pane" id="emailcustomerhistorylanguage<?php echo $language['language_id']; ?>">
                      <div class="form-group required">
                        <label class="col-sm-12 control-label" for="input-customersubject<?php echo $language['language_id']; ?>"><?php echo $entry_subject; ?></label>
                        <div class="col-sm-12">
                          <input type="text" name="jreturnemail_email[customerhistory][<?php echo $language['language_id']; ?>][subject]" value="<?php echo isset($jreturnemail_email['customerhistory'][$language['language_id']]) ? $jreturnemail_email['customerhistory'][$language['language_id']]['subject'] : ''; ?>" placeholder="<?php echo $entry_subject; ?>" id="input-customerhistorysubject<?php echo $language['language_id']; ?>" class="form-control" />
                          <?php if (isset($error_email['customerhistory'][$language['language_id']]['subject'])) { ?>
                          <div class="text-danger"><?php echo $error_email['customerhistory'][$language['language_id']]['subject']; ?></div>
                          <?php } ?>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-12 control-label" for="input-customerhistorymsg<?php echo $language['language_id']; ?>"><?php echo $entry_msg; ?></label>
                        <div class="col-sm-12">
                          <textarea name="jreturnemail_email[customerhistory][<?php echo $language['language_id']; ?>][msg]" placeholder="<?php echo $entry_msg; ?>" id="input-customerhistorymsg<?php echo $language['language_id']; ?>" data-toggle="summernote" class="form-control summernote"><?php echo isset($jreturnemail_email['customerhistory'][$language['language_id']]) ? $jreturnemail_email['customerhistory'][$language['language_id']]['msg'] : ''; ?></textarea>
                          <?php if (isset($error_email['customerhistory'][$language['language_id']]['msg'])) { ?>
                          <div class="text-danger"><?php echo $error_email['customerhistory'][$language['language_id']]['msg']; ?></div>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="accordion jreturn-wrap" id="accordion-customerEmailRA">
            <div class="card">
              <div class="card-header" id="customerEmailRA">
                <h5 class="accordion-heading" data-toggle="collapse" data-target="#customerEmailRAContent" aria-expanded="true" aria-controls="customerEmailRAContent">
                    <?php echo $text_emailcustomer_action; ?>
                </h5>
              </div>

              <div id="customerEmailRAContent" class="collapse" aria-labelledby="customerEmailRA" data-parent="#JReturneMail">
                <div class="card-body">
                  <div class="row">
                      <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                          <label class="col-sm-12 control-label" for="input-emailtocustomer_action"><?php echo $entry_emailtocustomer_action; ?><br/><span class="help"><?php echo $help_emailtocustomer_action; ?></span></label>
                          <div class="col-sm-5">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                              <label class="btn btn-default <?php if ($jreturnemail_emailtocustomer_action) { ?>active<?php } ?>"><input type="radio" name="jreturnemail_emailtocustomer_action" value="1" <?php if ($jreturnemail_emailtocustomer_action) { ?>checked="checked"<?php } ?> /> <?php echo $text_yes; ?></label>
                              <label class="btn btn-default <?php if (!$jreturnemail_emailtocustomer_action) { ?>active<?php } ?>"><input type="radio" name="jreturnemail_emailtocustomer_action" value="0" <?php if (!$jreturnemail_emailtocustomer_action) { ?>checked="checked"<?php } ?> /> <?php echo $text_no; ?></label>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                          <label class="col-sm-12 control-label"><?php echo $entry_return_action; ?><br/><span class="help"><?php echo $help_return_action; ?></span></label>
                          <div class="col-sm-12">
                            <?php foreach ($return_actions as $return_action) { ?>
                            <div class="checkbox">
                              <label>
                                <?php if (in_array($return_action['return_action_id'], $jreturnemail_return_action)) { ?>
                                <input type="checkbox" name="jreturnemail_return_action[]" value="<?php echo $return_action['return_action_id']; ?>" checked="checked" />
                                <?php echo $return_action['name']; ?>
                                <?php } else { ?>
                                <input type="checkbox" name="jreturnemail_return_action[]" value="<?php echo $return_action['return_action_id']; ?>" />
                                <?php echo $return_action['name']; ?>
                                <?php } ?>
                              </label>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                    </div>

                    <ul class="nav nav-tabs" id="emailcustomeractionlanguage">
                      <?php foreach ($languages as $language) { ?>
                      <li><a href="#emailcustomeractionlanguage<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                      <?php } ?>
                    </ul>
                    <div class="tab-content">
                      <?php foreach ($languages as $language) { ?>
                      <div class="tab-pane" id="emailcustomeractionlanguage<?php echo $language['language_id']; ?>">
                        <div class="form-group required">
                          <label class="col-sm-12 control-label" for="input-customersubject<?php echo $language['language_id']; ?>"><?php echo $entry_subject; ?></label>
                          <div class="col-sm-12">
                            <input type="text" name="jreturnemail_email[customeraction][<?php echo $language['language_id']; ?>][subject]" value="<?php echo isset($jreturnemail_email['customeraction'][$language['language_id']]) ? $jreturnemail_email['customeraction'][$language['language_id']]['subject'] : ''; ?>" placeholder="<?php echo $entry_subject; ?>" id="input-customeractionsubject<?php echo $language['language_id']; ?>" class="form-control" />
                            <?php if (isset($error_email['customeraction'][$language['language_id']]['subject'])) { ?>
                            <div class="text-danger"><?php echo $error_email['customeraction'][$language['language_id']]['subject']; ?></div>
                            <?php } ?>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-12 control-label" for="input-customeractionmsg<?php echo $language['language_id']; ?>"><?php echo $entry_msg; ?></label>
                          <div class="col-sm-12">
                            <textarea name="jreturnemail_email[customeraction][<?php echo $language['language_id']; ?>][msg]" placeholder="<?php echo $entry_msg; ?>" id="input-customeractionmsg<?php echo $language['language_id']; ?>" data-toggle="summernote" class="form-control summernote"><?php echo isset($jreturnemail_email['customeraction'][$language['language_id']]) ? $jreturnemail_email['customeraction'][$language['language_id']]['msg'] : ''; ?></textarea>

                            <?php if (isset($error_email['customeraction'][$language['language_id']]['msg'])) { ?>
                            <div class="text-danger"><?php echo $error_email['customeraction'][$language['language_id']]['msg']; ?></div>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                      <?php } ?>
                    </div>
                </div>
              </div>
            </div>
          </div>

          <fieldset>
            <legend><i class="fa fa-support"></i> <?php echo $tab_support; ?></legend>
            <div class="" id="tab-support">
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
          </fieldset>
        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $summernote_editor; ?>
<script type="text/javascript"><!--

$('.jreturn-wrap').each(function() {
  if ($(this).find('.text-danger').length || $(this).find('.has-error').length) {
    $(this).addClass('got-error');
    $(this).find('.accordion-heading').trigger('click');
  }
});

$('#accordion-shortcodes').on('hidden.bs.collapse', function () {
  var $sc_action = $(this).find('.sc_action')
  $sc_action.html('<i class="'+ $sc_action.attr('data-sc_view-i') +'"></i> ' + $sc_action.attr('data-sc_view'));
});
$('#accordion-shortcodes').on('shown.bs.collapse', function () {
  var $sc_action = $(this).find('.sc_action')
  $sc_action.html('<i class="'+ $sc_action.attr('data-sc_hide-i') +'"></i> ' + $sc_action.attr('data-sc_hide'));
});

$('.nav').each(function() {
  $(this).find('a:first').tab('show');
});
//--></script>
<?php echo $footer; ?>