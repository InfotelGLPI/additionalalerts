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
 * Class PluginAdditionalalertsInfocomAlert
 */
class PluginAdditionalalertsInfocomAlert extends CommonDBTM {

   static $rightname = "plugin_additionalalerts";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Computer with no buy date', 'Computers with no buy date', $nb, 'additionalalerts');
   }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'CronTask' && $item->getField('name') == "AdditionalalertsNotInfocom") {
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

         $target = $CFG_GLPI["root_doc"] . "/plugins/additionalalerts/front/infocomalert.form.php";
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
         case 'AdditionalalertsNotInfocom':
            return [
               'description' => PluginAdditionalalertsInfocomAlert::getTypeName(2)];   // Optional
            break;
      }
      return [];
   }

   /**
    * @param $entity
    *
    * @return string
    */
   static function query($entity) {
      global $DB;

      $query       = "SELECT `glpi_computers`.*, `glpi_items_operatingsystems`.`operatingsystems_id`
                     FROM `glpi_computers`
                     LEFT JOIN `glpi_infocoms` ON (`glpi_computers`.`id` = `glpi_infocoms`.`items_id` AND `glpi_infocoms`.`itemtype` = 'Computer')
                     LEFT JOIN `glpi_items_operatingsystems` ON (`glpi_computers`.`id` = `glpi_items_operatingsystems`.`items_id` 
                               AND `glpi_items_operatingsystems`.`itemtype` = 'Computer')
                     WHERE `glpi_computers`.`is_deleted` = 0
                     AND `glpi_computers`.`is_template` = 0
                     AND `glpi_infocoms`.`buy_date` IS NULL ";

      $query_type  = "SELECT `types_id`
                    FROM `glpi_plugin_additionalalerts_notificationtypes` ";
      $result_type = $DB->query($query_type);

      if ($DB->numrows($result_type) > 0) {
         $query .= " AND (`glpi_computers`.`computertypes_id` != 0 ";
         while ($data_type = $DB->fetch_array($result_type)) {
            $type_where = "AND `glpi_computers`.`computertypes_id` != '" . $data_type["types_id"] . "' ";
            $query      .= " $type_where ";
         }
         $query .= ") ";
      }
      $query .= "AND `glpi_computers`.`entities_id`= '" . $entity . "' ";

      $query .= " ORDER BY `glpi_computers`.`name` ASC";

      return $query;
   }


   /**
    * @param $data
    *
    * @return string
    */
   static function displayBody($data) {
      global $CFG_GLPI;

      $body = "<tr class='tab_bg_2'><td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/computer.form.php?id=" . $data["id"] . "\">" . $data["name"];

      if ($_SESSION["glpiis_ids_visible"] == 1 || empty($data["name"])) {
         $body .= " (";
         $body .= $data["id"] . ")";
      }
      $body .= "</a></td>";
      if (Session::isMultiEntitiesMode()) {
         $body .= "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data["entities_id"]) . "</td>";
      }
      $body .= "<td>" . Dropdown::getDropdownName("glpi_computertypes", $data["computertypes_id"]) . "</td>";
      $body .= "<td>" . Dropdown::getDropdownName("glpi_operatingsystems", $data["operatingsystems_id"]) . "</td>";
      $body .= "<td>" . Dropdown::getDropdownName("glpi_states", $data["states_id"]) . "</td>";
      $body .= "<td>" . Dropdown::getDropdownName("glpi_locations", $data["locations_id"]) . "</td>";
      $body .= "<td>";
      if (!empty($data["users_id"])) {
         $dbu = new DbUtils();
         $body .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/user.form.php?id=" . $data["users_id"] . "\">" .
                  $dbu->getUserName($data["users_id"]) . "</a>";

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
      if (!empty($data["contact"])) {
         $body .= " - " . $data["contact"];
      }

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
               FROM `glpi_plugin_additionalalerts_infocomalerts`";
      $query .= " ORDER BY `entities_id` ASC";

      $entities = [];
      $result   = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         foreach ($DB->request($query) as $entitydatas) {
            PluginAdditionalalertsInfocomAlert::getDefaultValueForNotification($field, $entities, $entitydatas);
         }
      } else {
         $config = new PluginAdditionalalertsConfig();
         $config->getFromDB(1);
         $dbu = new DbUtils();
         foreach ($dbu->getAllDataFromTable('glpi_entities') as $entity) {
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
      } //No configuration for this entity : if global config allows notification then add the entity
      //to the array of entities to be notified
      else if ((!isset($entitydatas[$field])
                || (isset($entitydatas[$field]) && $entitydatas[$field] == -1))
               && $config->fields[$field]) {
         $dbu = new DbUtils();
         foreach ($dbu->getAllDataFromTable('glpi_entities') as $entity) {
            $entities[$entity['id']] = $config->fields[$field];
         }
      }
   }

   /**
    * Cron action
    *
    * @param $task for log, if NULL display
    *
    *
    * @return int
    */
   static function cronAdditionalalertsNotInfocom($task = null) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["notifications_mailing"]) {
         return 0;
      }

      $config = PluginAdditionalalertsConfig::getConfig();

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginAdditionalalertsInfocomAlert", "AdditionalalertsNotInfocom")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE
             || !$config->useInfocomAlert()) {
            return 0;
         }
      } else {
         return 0;
      }

      $message     = [];
      $cron_status = 0;

      foreach (self::getEntitiesToNotify('use_infocom_alert') as $entity => $repeat) {
         $query_notinfocom = self::query($entity);

         $notinfocom_infos    = [];
         $notinfocom_messages = [];

         $type                    = Alert::END;
         $notinfocom_infos[$type] = [];
         foreach ($DB->request($query_notinfocom) as $data) {

            $entity                             = $data['entities_id'];
            $message                            = $data["name"];
            $notinfocom_infos[$type][$entity][] = $data;

            if (!isset($notinfocom_messages[$type][$entity])) {
               $notinfocom_messages[$type][$entity] = PluginAdditionalalertsInfocomAlert::getTypeName(2) . "<br />";
            }
            $notinfocom_messages[$type][$entity] .= $message;
         }

         foreach ($notinfocom_infos[$type] as $entity => $notinfocoms) {
            Plugin::loadLang('additionalalerts');

            if(count($notinfocoms) > 500) {
               //limit if it is too many element (does not work)
               $notinfocoms = array_slice($notinfocoms, 500);
            }
            if (NotificationEvent::raiseEvent("notinfocom",
                                              new PluginAdditionalalertsInfocomAlert(),
                                              ['entities_id' => $entity,
                                               'notinfocoms' => $notinfocoms])) {
               $message     = $notinfocom_messages[$type][$entity];
               $cron_status = 1;
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities",
                                                       $entity) . ":  $message\n");
                  $task->addVolume(1);
               } else {
                  Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                             $entity) . ":  $message");
               }

            } else {
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities", $entity) .
                             ":  Send infocoms alert failed\n");
               } else {
                  Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity) .
                                                   ":  Send infocoms alert failed", false, ERROR);
               }
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

      $type = new PluginAdditionalalertsNotificationType();
      $types = $type->find();
      $used = [];
      foreach ($types as $data) {
         $used[] = $data['types_id'];
      }

      echo "<div align='center'>";
      echo "<form method='post' action=\"$target\">";
      echo "<table class='tab_cadre_fixe' cellpadding='5'>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Parameter', 'additionalalerts') . "</td>";
      echo "<td>" . __('Type not used for check of buy date', 'additionalalerts');
      Dropdown::show('ComputerType', ['name' => "types_id",
                                      'used' => $used]);
      echo "&nbsp;<input type='submit' name='add_type' value=\"" . _sx('button', 'Add') . "\" class='submit' ></div></td>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();

      echo "</div>";

      $type = new PluginAdditionalalertsNotificationType();
      $type->configType();

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
      $entitynotification = new PluginAdditionalalertsInfocomAlert();
      if (!$entitynotification->getFromDBbyEntity($ID)) {
         $entitynotification->getEmpty();
      }

      if ($canedit) {
         echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='2'>" . __('Alarms options') . "</th></tr>";

      echo "<tr class='tab_bg_1'><td>" . PluginAdditionalalertsInfocomAlert::getTypeName(2) . "</td><td>";
      $default_value = $entitynotification->fields['use_infocom_alert'];
      Alert::dropdownYesNo(['name'           => "use_infocom_alert",
                            'value'          => $default_value,
                            'inherit_global' => 1]);
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
