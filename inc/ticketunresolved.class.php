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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginAdditionalalertsTicketUnresolved
 */
class PluginAdditionalalertsTicketUnresolved extends CommonDBTM {

   static $rightname = "plugin_additionalalerts";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Ticket unresolved', 'Tickets unresolved', $nb, 'additionalalerts');
   }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'CronTask' && $item->getField('name') == "AdditionalalertsTicketUnresolved") {
         return __('Plugin setup', 'additionalalerts');
      }
      return '';
   }


   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;

      if ($item->getType() == 'CronTask') {

         $target = $CFG_GLPI["root_doc"] . "/plugins/additionalalerts/front/ticketunresolved.form.php";
         self::configCron($target, $item->getField('id'));
      }
      return true;
   }

   // Cron action
   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name) {

      switch ($name) {
         case 'AdditionalalertsTicketUnresolved':
            return array(
               'description' => PluginAdditionalalertsTicketUnresolved::getTypeName(2));   // Optional
            break;
      }
      return array();
   }

   /**
    * @param $delay_ticket_alert
    * @param $entity
    *
    * @return string
    */
   static function queryTechnician($delay_ticket_alert, $entity) {

      $delay_stamp = mktime(0, 0, 0, date("m"), date("d") - $delay_ticket_alert, date("y"));
      $date        = date("Y-m-d", $delay_stamp);
      $date        = $date . " 00:00:00";

      $querytechnician = "SELECT `glpi_tickets`.*, `glpi_tickets_users`.users_id
      FROM `glpi_tickets`
      LEFT JOIN `glpi_tickets_users` ON `glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` 
      WHERE `glpi_tickets`.`date` <= '" . $date . "'
      AND `glpi_tickets`.`status` <= 4
      AND `glpi_tickets_users`.`type` = 2 
      AND `glpi_tickets`.`entities_id` = '" . $entity . "'
      AND `glpi_tickets`.`is_deleted` = 0
      ORDER BY `glpi_tickets_users`.`users_id`";

      return $querytechnician;
   }

   /**
    * @param $delay_ticket_alert
    * @param $entity
    *
    * @return string
    */
   static function querySupervisor($delay_ticket_alert, $entity) {
      global $DB;

      $delay_stamp = mktime(0, 0, 0, date("m"), date("d") - $delay_ticket_alert, date("y"));
      $date        = date("Y-m-d", $delay_stamp);
      $date        = $date . " 00:00:00";

      $query_id_technician  = "SELECT `glpi_tickets`.`id`, `glpi_tickets_users`.users_id
      FROM `glpi_tickets`
      LEFT JOIN `glpi_tickets_users` ON `glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` 
      WHERE `glpi_tickets`.`date` <= '" . $date . "'
      AND `glpi_tickets`.`status` <= 4
      AND `glpi_tickets_users`.`type` = 2 
      AND `glpi_tickets`.`is_deleted` = 0
      AND `glpi_tickets`.`entities_id` = '" . $entity . "' ";
      $result_id_technician = $DB->query($query_id_technician);

      $querysupervisor = "SELECT `glpi_tickets`.*, `glpi_groups_users`.`users_id`
      FROM `glpi_tickets`
      LEFT JOIN `glpi_groups_tickets` ON `glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id` 
      LEFT JOIN `glpi_groups_users` ON `glpi_groups_users`.`groups_id` = `glpi_groups_tickets`.`groups_id`
      WHERE `glpi_tickets`.`date` <= '" . $date . "'
      AND `glpi_tickets`.`status` <= 4
      AND `glpi_groups_tickets`.`type` = 2
      AND `glpi_groups_users`.`is_manager` = 1 
      AND `glpi_tickets`.`is_deleted` = 0
      AND `glpi_tickets`.`entities_id` = '" . $entity . "' ";

      if ($DB->numrows($result_id_technician) > 0) {
         while ($data_type = $DB->fetch_array($result_id_technician)) {
            $type_where = "AND `glpi_tickets`.`id` != '" . $data_type["id"] . "' ";
            $querysupervisor .= " $type_where ";
         }
      }
      $querysupervisor .= " ORDER BY `glpi_groups_users`.`users_id`";

      return $querysupervisor;
   }


   /**
    * @param $data
    *
    * @return string
    */
   static function displayBody($data) {
      global $CFG_GLPI;

      $body = "<tr class='tab_bg_2'><td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $data["id"] . "\">" . $data["name"];
      $body .= "</a></td>";

      $body .= "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data["entities_id"]) . "</td>";
      $body .= "<td>" . Ticket::getStatus($data["status"]) . "</td>";
      $body .= "<td>" . Html::convDateTime($data["date"]) . "</td>";
      $body .= "<td>" . Html::convDateTime($data["date_mod"]) . "</td>";
      $body .= "<td>";
      if (!empty($data["users_id"])) {

         $body .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/user.form.php?id=" . $data["users_id"] . "\">" . getUserName($data["users_id"]) . "</a>";

      }
      if (!empty($data["groups_id"])) {

         $body .= " - <a href=\"" . $CFG_GLPI["root_doc"] . "/front/group.form.php?id=" . $data["groups_id"] . "\">";

         $body .= Dropdown::getDropdownName("glpi_groups", $data["groups_id"]);
         if ($_SESSION["glpiis_ids_visible"] == 1) {
            $body .= " (";
            $body .= $data["groups_id"] . ")";
         }
         $body .= "</a>";
      }
      if (!empty($data["contact"]))
         $body .= " - " . $data["contact"];

      $body .= "</td>";
      $body .= "</tr>";

      return $body;
   }


   /**
    * @param      $field
    * @param bool $with_value
    *
    * @return array
    */
   static function getEntitiesToNotify($field, $with_value = false) {
      global $DB;
      $query = "SELECT `entities_id` as `entity`,`$field`
               FROM `glpi_plugin_additionalalerts_ticketunresolveds`";
      $query .= " ORDER BY `entities_id` ASC";

      $entities = array();
      $result   = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         foreach ($result as $entitydatas) {
            PluginAdditionalalertsTicketUnresolved::getDefaultValueForNotification($field, $entities, $entitydatas);
         }
      } else {
         $config = new PluginAdditionalalertsConfig();
         $config->getFromDB(1);
         foreach (getAllDatasFromTable('glpi_entities') as $entity) {
            $entities[$entity['id']] = $config->fields[$field];
         }
      }


      return $entities;
   }

   /**
    * @param $field
    * @param $entities
    * @param $entitydatas
    */
   static function getDefaultValueForNotification($field, &$entities, $entitydatas) {

      $config = new PluginAdditionalalertsConfig();
      $config->getFromDB(1);
      //If there's a configuration for this entity & the value is not the one of the global config
      if (isset($entitydatas[$field]) && $entitydatas[$field] > 0) {
         $entities[$entitydatas['entity']] = $entitydatas[$field];
      }
      //No configuration for this entity : if global config allows notification then add the entity
      //to the array of entities to be notified
      else if ((!isset($entitydatas[$field])
                || (isset($entitydatas[$field]) && $entitydatas[$field] == -1))
               && $config->fields[$field]
      ) {

         foreach (getAllDatasFromTable('glpi_entities') as $entity) {
            $entities[$entity['id']] = $config->fields[$field];
         }
      }
   }


   /**
    * Cron action
    *
    * @param $task for log, if NULL display
    *
    * @return int
    */
   static function cronAdditionalalertsTicketUnresolved($task = NULL) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginAdditionalalertsTicketUnresolved", "AdditionalalertsTicketUnresolved")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }
      $entities    = self::getEntitiesToNotify('delay_ticket_alert');
      $cron_status = 0;
      foreach ($entities as $entity => $delay_ticket_alert) {

         $query_technician = self::queryTechnician($delay_ticket_alert, $entity);
         $query_supervisor = self::querySupervisor($delay_ticket_alert, $entity);

         $ticket_technician = array();
         foreach ($DB->request($query_technician) as $tick) {
            $ticket_technician[$tick['users_id']][] = $tick;
         }

         foreach ($ticket_technician as $tickets) {
            $ticket = new PluginAdditionalalertsTicketUnresolved();
            if (PluginAdditionalalertsNotificationTargetTicketUnresolved::raiseEventTicket('ticketunresolved',
                                                                                           $ticket,
                                                                                           array('entities_id' => $entity,
                                                                                                 'items'       => $tickets,
                                                                                                 'notifType'   => "TECH"))
            ) {
               $task->addVolume(1);
               $cron_status = 1;
            }
         }

         $ticket_supervisor = array();
         foreach ($DB->request($query_supervisor) as $tick) {
            $ticket_supervisor[$tick['users_id']][] = $tick;
         }

         foreach ($ticket_supervisor as $tickets) {
            $ticket = new PluginAdditionalalertsTicketUnresolved();
            if (PluginAdditionalalertsNotificationTargetTicketUnresolved::raiseEventTicket('ticketunresolved',
                                                                                           $ticket,
                                                                                           array('entities_id' => $entity,
                                                                                                 'items'       => $tickets,
                                                                                                 'notifType'   => "SUPERVISOR"))
            ) {
               $task->addVolume(1);
               $cron_status = 1;
            }
         }
      }

      return $cron_status;
   }

   /**
    * @param $target
    * @param $ID
    */
   static function configCron($target, $ID) {


   }

   /**
    * @param $entities_id
    *
    * @return bool
    */
   function getFromDBbyEntity($entities_id) {
      global $DB;

      $query = "SELECT *
                FROM `" . $this->getTable() . "`
                WHERE `entities_id` = '$entities_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         }
         return false;
      }
      return false;
   }

   /**
    * @param Entity $entity
    *
    * @return bool
    */
   static function showNotificationOptions(Entity $entity) {
      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      // Notification right applied
      $canedit = Session::haveRight('notification', UPDATE) && Session::haveAccessToEntity($ID);

      // Get data
      $entitynotification = new PluginAdditionalalertsTicketUnresolved();

      if (!$entitynotification->getFromDBbyEntity($ID)) {
         $entitynotification->getEmpty();
      }
      if (empty($entitynotification->fields["delay_ticket_alert"])) {
         $entitynotification->fields["delay_ticket_alert"] = 0;
      }
      if ($canedit) {
         echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'><td>" . PluginAdditionalalertsTicketUnresolved::getTypeName(2) . "</td><td>";
      Alert::dropdownIntegerNever('delay_ticket_alert',
                                  $entitynotification->fields["delay_ticket_alert"],
                                  array('max' => 99));

      echo "</td></tr>";


      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";
         if ($entitynotification->fields["id"]) {
            echo "<input type='hidden' name='id' value=\"" . $entitynotification->fields["id"] . "\">";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='submit' >";
         } else {
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Save') . "\" class='submit' >";
         }
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
      } else {
         echo "</table>";
      }
   }

}