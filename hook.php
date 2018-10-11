<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 additionalalerts plugin for GLPI
 Copyright (C) 2009-2016 by the additionalalerts Development Team.

 https://github.com/InfotelGLPI/additionalalerts
 -------------------------------------------------------------------------

 LICENSE

 This file is part of additionalalerts.

 additionalalerts is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 additionalalerts is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with additionalalerts. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * @return bool
 */
function plugin_additionalalerts_install() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/additionalalerts/inc/profile.class.php");

   $install = false;
   $update78 = false;
   $update90 = false;

   //INSTALL
   if (!$DB->tableExists("glpi_plugin_additionalalerts_ticketunresolveds")
      && !$DB->tableExists("glpi_plugin_additionalalerts_configs")) {

      $install = true;
      $DB->runFile(GLPI_ROOT . "/plugins/additionalalerts/sql/empty-2.1.2.sql");

   }
   //UPDATE
   if ($DB->tableExists("glpi_plugin_alerting_profiles")
      && $DB->fieldExists("glpi_plugin_alerting_profiles", "interface")) {

      $update78 = true;
      $DB->runFile(GLPI_ROOT . "/plugins/additionalalerts/sql/update-1.2.0.sql");
      $DB->runFile(GLPI_ROOT . "/plugins/additionalalerts/sql/update-1.3.0.sql");

   }
   if (!$DB->tableExists("glpi_plugin_additionalalerts_infocomalerts")) {

      $update78 = true;
      $DB->runFile(GLPI_ROOT . "/plugins/additionalalerts/sql/update-1.3.0.sql");

   }
   if ($DB->tableExists("glpi_plugin_additionalalerts_reminderalerts")) {

      $DB->runFile(GLPI_ROOT . "/plugins/additionalalerts/sql/update-1.5.0.sql");

      $notif = new Notification();

      $options = ['itemtype' => 'PluginAdditionalalertsReminderAlert',
         'event' => 'reminder',
         'FIELDS' => 'id'];
      foreach ($DB->request('glpi_notifications', $options) as $data) {
         $notif->delete($data);
      }

      $template = new NotificationTemplate();
      $translation = new NotificationTemplateTranslation();
      $notif_template = new Notification_NotificationTemplate();
      $options = ['itemtype' => 'PluginAdditionalalertsReminderAlert',
         'FIELDS' => 'id'];
      foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
         $options_template = ['notificationtemplates_id' => $data['id'],
            'FIELDS' => 'id'];

         foreach ($DB->request('glpi_notificationtemplatetranslations', $options_template) as $data_template) {
            $translation->delete($data_template);
         }
         $template->delete($data);

         foreach ($DB->request('glpi_notifications_notificationtemplates', $options_template) as $data_template) {
            $notif_template->delete($data_template);
         }
      }

      $temp = new CronTask();
      if ($temp->getFromDBbyName('PluginAdditionalalertsReminderAlert', 'AdditionalalertsReminder')) {
         $temp->delete(['id' => $temp->fields["id"]]);
      }
   }
   if (!$DB->tableExists("glpi_plugin_additionalalerts_inkalerts")) {

      $DB->runFile(GLPI_ROOT . "/plugins/additionalalerts/sql/update-1.7.1.sql");

      $query_id = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype`='PluginAdditionalalertsInkAlert' AND `name` = 'Alert ink level'";
      $result = $DB->query($query_id) or die ($DB->error());
      $itemtype = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                                VALUES(NULL, " . $itemtype . ", '','##lang.ink.title## : ##ink.entity##',
      '##lang.ink.title## :
      ##FOREACHinks##
      - ##ink.printer## - ##ink.cartridge## - ##ink.state##%
      ##ENDFOREACHinks##',
      '&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
      &lt;tbody&gt;
      &lt;tr&gt;
      &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.printer##&lt;/span&gt;&lt;/td&gt;
      &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.cartridge##&lt;/span&gt;&lt;/td&gt;
      &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.state##&lt;/span&gt;&lt;/td&gt;
      &lt;/tr&gt;
      ##FOREACHinks##
      &lt;tr&gt;
      &lt;td&gt;&lt;a href=\"##ink.urlprinter##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.printer##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
      &lt;td&gt;&lt;a href=\"##ink.urlcartridge##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.cartridge##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
      &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.state##%&lt;/span&gt;&lt;/td&gt;
      &lt;/tr&gt;
      ##ENDFOREACHinks##
      &lt;/tbody&gt;
      &lt;/table&gt;');";

      $DB->query($query);

      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`) 
                VALUES ('Alert ink level', 0, 'PluginAdditionalalertsInkAlert', 'ink', 1, 1);";
       $DB->query($query);

      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Alert ink level' AND `itemtype` = 'PluginAdditionalalertsInkAlert' AND `event` = 'ink'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $itemtype . ");";
      $DB->query($query);
   }
   //version 1.8.0
   if (!$DB->tableExists("glpi_plugin_additionalalerts_ticketunresolveds")) {

      $update90 = true;
      $DB->runFile(GLPI_ROOT . "/plugins/additionalalerts/sql/update-1.8.0.sql");
   }

   //version 2.1.2
   if ($DB->tableExists("glpi_plugin_additionalalerts_ocsalerts")) {
      //ocsalert migrated to the ocsinventoryng plugin
      $notif = new Notification();

      $options = ['itemtype' => 'PluginAdditionalalertsOcsAlert',
                  'event' => 'ocs',
                  'FIELDS' => 'id'];
      foreach ($DB->request('glpi_notifications', $options) as $data) {
         $notif->delete($data);
      }
      $options = ['itemtype' => 'PluginAdditionalalertsOcsAlert',
                  'event' => 'newocs',
                  'FIELDS' => 'id'];
      foreach ($DB->request('glpi_notifications', $options) as $data) {
         $notif->delete($data);
      }

      //templates
      $template = new NotificationTemplate();
      $translation = new NotificationTemplateTranslation();
      $notif_template = new Notification_NotificationTemplate();
      $options = ['itemtype' => 'PluginAdditionalalertsOcsAlert',
                  'FIELDS' => 'id'];
      foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
         $options_template = ['notificationtemplates_id' => $data['id'],
                              'FIELDS' => 'id'];

         foreach ($DB->request('glpi_notificationtemplatetranslations', $options_template) as $data_template) {
            $translation->delete($data_template);
         }
         $template->delete($data);
         foreach ($DB->request('glpi_notifications_notificationtemplates', $options_template) as $data_template) {
            $notif_template->delete($data_template);
         }
      }
      //delete tables
      $tables = [
         "glpi_plugin_additionalalerts_ocsalerts",
         "glpi_plugin_additionalalerts_notificationstates"];

      foreach ($tables as $table) {
         $DB->query("DROP TABLE IF EXISTS `$table`;");
      }
      //delete fields
      $DB->query("ALTER TABLE `glpi_plugin_additionalalerts_configs`
                 DROP `delay_ocs`,
                 DROP `use_newocs_alert`;");

      CronTask::Unregister('PluginAdditionalalertsOcsAlert');

   }

   if ($install || $update78) {
      //Do One time on 0.78
      $query_id = "SELECT `id` 
                  FROM `glpi_notificationtemplates` 
                  WHERE `itemtype`='PluginAdditionalalertsInfocomAlert' AND `name` = 'Alert infocoms'";
      $result = $DB->query($query_id) or die ($DB->error());
      $itemtype = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                                 VALUES(NULL, " . $itemtype . ", '','##lang.notinfocom.title## : ##notinfocom.entity##',
                        '##FOREACHnotinfocoms##
   ##lang.notinfocom.name## : ##notinfocom.name##
   ##lang.notinfocom.computertype## : ##notinfocom.computertype##
   ##lang.notinfocom.operatingsystem## : ##notinfocom.operatingsystem##
   ##lang.notinfocom.state## : ##notinfocom.state##
   ##lang.notinfocom.location## : ##notinfocom.location##
   ##lang.notinfocom.user## : ##notinfocom.user## / ##notinfocom.group## / ##notinfocom.contact##
   ##ENDFOREACHnotinfocoms##',
                        '&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
   &lt;tbody&gt;
   &lt;tr&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.name##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.computertype##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.operatingsystem##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.state##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.location##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.user##&lt;/span&gt;&lt;/td&gt;
   &lt;/tr&gt;
   ##FOREACHnotinfocoms##            
   &lt;tr&gt;
   &lt;td&gt;&lt;a href=\"##notinfocom.urlname##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.name##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.computertype##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.operatingsystem##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.state##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.location##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;a href=\"##notinfocom.urluser##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.user##&lt;/span&gt;&lt;/a&gt; / &lt;a href=\"##notinfocom.urlgroup##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.group##&lt;/span&gt;&lt;/a&gt; / &lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.contact##&lt;/span&gt;&lt;/td&gt;
   &lt;/tr&gt;
   ##ENDFOREACHnotinfocoms##
   &lt;/tbody&gt;
   &lt;/table&gt;');";
      $DB->query($query);

      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`) 
                 VALUES ('Alert infocoms', 0, 'PluginAdditionalalertsInfocomAlert', 'notinfocom', 1, 1);";
      $DB->query($query);

      //retrieve notification id
      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Alert infocoms' AND `itemtype` = 'PluginAdditionalalertsInfocomAlert' AND `event` = 'notinfocom'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $itemtype . ");";
      $DB->query($query);

      //////////////////////
      $query_id = "SELECT `id` 
                  FROM `glpi_notificationtemplates` 
                  WHERE `itemtype`='PluginAdditionalalertsInkAlert' AND `name` = 'Alert ink level'";
      $result = $DB->query($query_id) or die ($DB->error());
      $itemtype = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                                VALUES(NULL, " . $itemtype . ", '','##lang.ink.title## : ##ink.entity##',
      '##lang.ink.title## :
      ##FOREACHinks##
      - ##ink.printer## - ##ink.cartridge## - ##ink.state##%
      ##ENDFOREACHinks##',
      '&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
      &lt;tbody&gt;
      &lt;tr&gt;
      &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.printer##&lt;/span&gt;&lt;/td&gt;
      &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.cartridge##&lt;/span&gt;&lt;/td&gt;
      &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.state##&lt;/span&gt;&lt;/td&gt;
      &lt;/tr&gt;
      ##FOREACHinks##
      &lt;tr&gt;
      &lt;td&gt;&lt;a href=\"##ink.urlprinter##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.printer##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
      &lt;td&gt;&lt;a href=\"##ink.urlcartridge##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.cartridge##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
      &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.state##%&lt;/span&gt;&lt;/td&gt;
      &lt;/tr&gt;
      ##ENDFOREACHinks##
      &lt;/tbody&gt;
      &lt;/table&gt;');";

      $DB->query($query);

      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`) 
                 VALUES ('Alert ink level', 0, 'PluginAdditionalalertsInkAlert', 'ink', 1, 1);";
      $DB->query($query);

      //retrieve notification id
      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Alert ink level' AND `itemtype` = 'PluginAdditionalalertsInkAlert' AND `event` = 'ink'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $itemtype . ");";
      $DB->query($query);

   }
   if ($update78) {
      //Do One time on 0.78
      $query_ = "SELECT *
            FROM `glpi_plugin_additionalalerts_profiles` ";
      $result_ = $DB->query($query_);
      if ($DB->numrows($result_) > 0) {

         while ($data = $DB->fetch_array($result_)) {
            $query = "UPDATE `glpi_plugin_additionalalerts_profiles`
                  SET `profiles_id` = '" . $data["id"] . "'
                  WHERE `id` = '" . $data["id"] . "';";
            $DB->query($query);

         }
      }

      $query = "ALTER TABLE `glpi_plugin_additionalalerts_profiles`
               DROP `name` ;";
      $DB->query($query);
   }

   if ($install || $update90) {
      ////////////////
      $query_id = "SELECT `id` 
                  FROM `glpi_notificationtemplates` 
                  WHERE `itemtype`='PluginAdditionalalertsTicketUnresolved' AND `name` = 'Alert Ticket Unresolved'";
      $result = $DB->query($query_id) or die ($DB->error());
      $itemtype = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                                VALUES(NULL, " . $itemtype . ", '','##ticket.action## ##ticket.entity##',
      '##lang.ticket.entity## : ##ticket.entity##
     ##FOREACHtickets##

      ##lang.ticket.title## : ##ticket.title##
       ##lang.ticket.status## : ##ticket.status##

       ##ticket.url## 
       ##ENDFOREACHtickets##','&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
&lt;tbody&gt;
&lt;tr&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.title##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.attribution##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.content##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##FOREACHtickets## 
&lt;tr&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;&lt;a href=\"##ticket.url##\"&gt;##ticket.title##&lt;/a&gt;&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##IFticket.assigntousers####ticket.assigntousers##&lt;br /&gt;##ENDIFticket.assigntousers####IFticket.assigntogroups##&lt;br /&gt;##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##&lt;br /&gt;##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.content##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##ENDFOREACHtickets##
&lt;/tbody&gt;
&lt;/table&gt;')";

      $DB->query($query);

      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`) 
                VALUES ('Alert Ticket Unresolved', 0, 'PluginAdditionalalertsTicketUnresolved', 'ticketunresolved', 1, 1);";
      $DB->query($query);

      //retrieve notification id
      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Alert Ticket Unresolved' 
               AND `itemtype` = 'PluginAdditionalalertsTicketUnresolved' 
               AND `event` = 'ticketunresolved'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $itemtype . ");";
      $DB->query($query);

   }

   // To be called for each task the plugin manage
   CronTask::Register('PluginAdditionalalertsInfocomAlert', 'AdditionalalertsNotInfocom', HOUR_TIMESTAMP);
   CronTask::Register('PluginAdditionalalertsInkAlert', 'AdditionalalertsInk', DAY_TIMESTAMP);
   CronTask::Register('PluginAdditionalalertsTicketUnresolved', 'AdditionalalertsTicketUnresolved', DAY_TIMESTAMP);

   PluginAdditionalalertsProfile::initProfile();
   PluginAdditionalalertsProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   if ($DB->tableExists("glpi_plugin_additionalalerts_profiles")) {
      $query = "DROP TABLE `glpi_plugin_additionalalerts_profiles`;";
      $DB->query($query);
   }

   return true;
}

/**
 * @return bool
 */
function plugin_additionalalerts_uninstall() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/additionalalerts/inc/profile.class.php");
   include_once(GLPI_ROOT . "/plugins/additionalalerts/inc/menu.class.php");

   $tables = [
      "glpi_plugin_additionalalerts_infocomalerts",
      "glpi_plugin_additionalalerts_inkalerts",
      "glpi_plugin_additionalalerts_notificationtypes",
      "glpi_plugin_additionalalerts_configs",
      "glpi_plugin_additionalalerts_inkthresholds",
      "glpi_plugin_additionalalerts_inkprinterstates",
      "glpi_plugin_additionalalerts_ticketunresolveds"];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   //old versions
   $tables = ["glpi_plugin_additionalalerts_reminderalerts",
      "glpi_plugin_alerting_config",
      "glpi_plugin_alerting_state",
      "glpi_plugin_alerting_profiles",
      "glpi_plugin_alerting_mailing",
      "glpi_plugin_alerting_type",
      "glpi_plugin_additionalalerts_profiles",
      "glpi_plugin_alerting_cartridges",
      "glpi_plugin_alerting_cartridges_printer_state",
      "glpi_plugin_additionalalerts_profiles",
      "glpi_plugin_additionalalerts_ocsalerts",
      "glpi_plugin_additionalalerts_notificationstates"];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   $notif = new Notification();

   $options = ['itemtype' => 'PluginAdditionalalertsInkAlert',
      'event' => 'ink',
      'FIELDS' => 'id'];
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }

   $options = ['itemtype' => 'PluginAdditionalalertsInfocomAlert',
      'event' => 'notinfocom',
      'FIELDS' => 'id'];
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }

   $options = ['itemtype' => 'PluginAdditionalalertsTicketUnresolved',
      'event' => 'ticketunresolved',
      'FIELDS' => 'id'];
   foreach ($DB->request('glpi_notifications', $options) as $data) {
      $notif->delete($data);
   }

   //templates
   $template = new NotificationTemplate();
   $translation = new NotificationTemplateTranslation();
   $options = ['itemtype' => 'PluginAdditionalalertsInfocomAlert',
      'FIELDS' => 'id'];
   foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
      $options_template = ['notificationtemplates_id' => $data['id'],
         'FIELDS' => 'id'];

      foreach ($DB->request('glpi_notificationtemplatetranslations', $options_template) as $data_template) {
         $translation->delete($data_template);
      }
      $template->delete($data);
   }

   //templates
   $template = new NotificationTemplate();
   $translation = new NotificationTemplateTranslation();
   $options = ['itemtype' => 'PluginAdditionalalertsInkAlert',
      'FIELDS' => 'id'];
   foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
      $options_template = ['notificationtemplates_id' => $data['id'],
         'FIELDS' => 'id'];

      foreach ($DB->request('glpi_notificationtemplatetranslations', $options_template) as $data_template) {
         $translation->delete($data_template);
      }
      $template->delete($data);
   }

   //templates
   $template = new NotificationTemplate();
   $translation = new NotificationTemplateTranslation();
   $options = ['itemtype' => 'PluginAdditionalalertsTicketUnresolved',
      'FIELDS' => 'id'];
   foreach ($DB->request('glpi_notificationtemplates', $options) as $data) {
      $options_template = ['notificationtemplates_id' => $data['id'],
         'FIELDS' => 'id'];

      foreach ($DB->request('glpi_notificationtemplatetranslations', $options_template) as $data_template) {
         $translation->delete($data_template);
      }
      $template->delete($data);
   }

   //Plugin::registerClass('PluginAdditionalalertsProfile');

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginAdditionalalertsProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }
   PluginAdditionalalertsProfile::removeRightsFromSession();

   PluginAdditionalalertsMenu::removeRightsFromSession();

   CronTask::Unregister('additionalalerts');

   return true;
}

// Define database relations
/**
 * @return array
 */
function plugin_additionalalerts_getDatabaseRelations() {

   $plugin = new Plugin();
   $links = [];
   if ($plugin->isActivated("additionalalerts")) {
      $links = [
         "glpi_states" => [
            "glpi_plugin_additionalalerts_notificationstates" => "states_id"
         ],
         "glpi_computertypes" => [
            "glpi_plugin_additionalalerts_notificationtypes" => "types_id"
         ]];
   }
   if ($plugin->isActivated("fusioninventory")) {
      $links[] = ["glpi_plugin_fusioninventory_printercartridges" => [
         "glpi_plugin_additionalalerts_ink" => "cartridges_id"
      ]];
   }

   return $links;
}
