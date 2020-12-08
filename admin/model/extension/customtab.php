<?php
class ModelExtensionCustomtab extends Model {
	public function install() {
	$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."product_customtab` (
  `product_customtab_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`product_customtab_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."product_customtab_description` (
  `product_customtab_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `description` text CHARACTER SET utf8 NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
	}
	public function uninstall() {
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."product_customtab`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."product_customtab_description`");
	}
}
