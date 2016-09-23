ALTER TABLE `glpi_plugin_additionalalerts_configs` ADD `use_ink_alert` TINYINT( 1 ) NOT NULL DEFAULT '-1';

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_inkalerts`;
CREATE TABLE `glpi_plugin_additionalalerts_inkalerts` (
      `id` int(11) NOT NULL auto_increment,
      `entities_id` int(11) NOT NULL default '0',
      `use_ink_alert` TINYINT( 1 ) NOT NULL DEFAULT '-1',
      PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_inkthresholds`;
CREATE TABLE `glpi_plugin_additionalalerts_inkthresholds` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `cartridges_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_cartridgeitems (id)',
    `threshold` int(3) NOT NULL default '10',
      PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_inkprinterstates`;
CREATE TABLE `glpi_plugin_additionalalerts_inkprinterstates` (
    `id` INT( 11 ) NOT NULL AUTO_INCREMENT,
      `states_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_states (id)',
      PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Alert ink level', 'PluginAdditionalalertsInkAlert', '2010-03-13 10:44:46','',NULL, '2010-03-13 10:44:46');