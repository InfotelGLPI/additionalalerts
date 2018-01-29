ALTER TABLE `glpi_plugin_additionalalerts_configs` ADD `delay_ticket_alert` int(11) NOT NULL DEFAULT '0';

DROP TABLE IF EXISTS `glpi_plugin_additionalalerts_ticketunresolveds`;
CREATE TABLE `glpi_plugin_additionalalerts_ticketunresolveds` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `delay_ticket_alert` int(11) NOT NULL DEFAULT '-1',
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Alert Ticket Unresolved', 'PluginAdditionalalertsTicketUnresolved', '2010-03-13 10:44:46','',NULL, '2010-03-13 10:44:46');