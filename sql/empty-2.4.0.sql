DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_configs`;
CREATE TABLE `glpi_plugin_additionalalerts_configs` (
   `id` int unsigned NOT NULL auto_increment,
   `use_infocom_alert` tinyint NOT NULL DEFAULT '-1',
   `use_ink_alert` tinyint NOT NULL DEFAULT '-1',
   `delay_ticket_alert` int unsigned NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_additionalalerts_configs` ( `id`, `use_infocom_alert`, `use_ink_alert`, `delay_ticket_alert`)
VALUES ('1','-1','-1','-1');

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_infocomalerts`;
CREATE TABLE `glpi_plugin_additionalalerts_infocomalerts` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `use_infocom_alert` tinyint NOT NULL DEFAULT '-1',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_ticketunresolveds`;
CREATE TABLE `glpi_plugin_additionalalerts_ticketunresolveds` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `delay_ticket_alert` int unsigned NOT NULL DEFAULT '-1',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_inkalerts`;
CREATE TABLE `glpi_plugin_additionalalerts_inkalerts` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `use_ink_alert` tinyint NOT NULL DEFAULT '-1',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_notificationtypes`;
CREATE TABLE `glpi_plugin_additionalalerts_notificationtypes` (
   `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
   `types_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_computertypes (id)',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_inkthresholds`;
CREATE TABLE `glpi_plugin_additionalalerts_inkthresholds` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `cartridges_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_cartridgeitems (id)',
   `threshold` int unsigned NOT NULL default '10',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_inkprinterstates`;
CREATE TABLE `glpi_plugin_additionalalerts_inkprinterstates` (
   `id` INT( 11 ) NOT NULL AUTO_INCREMENT,
   `states_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_states (id)',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Alert infocoms', 'PluginAdditionalalertsInfocomAlert', '2010-03-13 10:44:46','',NULL, '2010-03-13 10:44:46');
INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Alert machines ocs', 'PluginAdditionalalertsOcsAlert', '2010-03-13 10:44:46','',NULL, '2010-03-13 10:44:46');
INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Alert ink level', 'PluginAdditionalalertsInkAlert', '2010-03-13 10:44:46','',NULL, '2010-03-13 10:44:46');
INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Alert Ticket Unresolved', 'PluginAdditionalalertsTicketUnresolved', '2010-03-13 10:44:46','',NULL, '2010-03-13 10:44:46');
