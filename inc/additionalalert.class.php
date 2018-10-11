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
 * Class PluginAdditionalalertsAdditionalalert
 */
class PluginAdditionalalertsAdditionalalert extends CommonDBTM {

   static $rightname = "plugin_additionalalerts";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Other alert', 'Others alerts', $nb, 'additionalalerts');
   }


   static function displayAlerts() {
      global $DB;

      $CronTask = new CronTask();

      $config = PluginAdditionalalertsConfig::getConfig();

      $infocom = new PluginAdditionalalertsInfocomAlert();
      $infocom->getFromDBbyEntity($_SESSION["glpiactive_entity"]);
      if (isset($infocom->fields["use_infocom_alert"])
          && $infocom->fields["use_infocom_alert"] > 0) {
         $use_infocom_alert = $infocom->fields["use_infocom_alert"];
      } else {
         $use_infocom_alert = $config->useInfocomAlert();
      }

      $ticketunresolved = new PluginAdditionalalertsTicketUnresolved();
      $ticketunresolved->getFromDBbyEntity($_SESSION["glpiactive_entity"]);
      if (isset($ticketunresolved->fields["delay_ticket_alert"])
          && $ticketunresolved->fields["delay_ticket_alert"] > 0) {
         $delay_ticket_alert = $ticketunresolved->fields["delay_ticket_alert"];
      } else {
         $delay_ticket_alert = $config->getDelayTicketAlert();
      }

      $inkalert = new PluginAdditionalalertsInkAlert();
      $inkalert->getFromDBbyEntity($_SESSION["glpiactive_entity"]);
      if (isset($inkalert->fields["use_ink_alert"])
          && $inkalert->fields["use_ink_alert"] > 0) {
         $use_ink_alert = $inkalert->fields["use_ink_alert"];
      } else {
         $use_ink_alert = $config->useInkAlert();
      }

      $additionalalerts_ink = 0;
      if ($CronTask->getFromDBbyName("PluginAdditionalalertsInkAlert", "AdditionalalertsInk")) {
         if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $use_ink_alert > 0) {
            $additionalalerts_ink = 1;
         }
      }

      $additionalalerts_not_infocom = 0;
      if ($CronTask->getFromDBbyName("PluginAdditionalalertsInfocomAlert", "AdditionalalertsNotInfocom")) {
         if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $use_infocom_alert > 0) {
            $additionalalerts_not_infocom = 1;
         }
      }

      $additionalalerts_ticket_unresolved = 0;
      if ($CronTask->getFromDBbyName("PluginAdditionalalertsTicketUnresolved", "AdditionalalertsTicketUnresolved")) {
         if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $delay_ticket_alert > 0) {
            $additionalalerts_ticket_unresolved = 1;
         }
      }

      if ($additionalalerts_not_infocom == 0
          && $additionalalerts_ink == 0
          && $additionalalerts_ticket_unresolved == 0) {
         echo "<div align='center'><b>" . __('No used alerts', 'additionalalerts') . "</b></div>";
      }
      if ($additionalalerts_not_infocom != 0) {
         if (Session::haveRight("infocom", READ)) {

            $query  = PluginAdditionalalertsInfocomAlert::query($_SESSION["glpiactive_entity"]);
            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {

               if (Session::isMultiEntitiesMode()) {
                  $nbcol = 7;
               } else {
                  $nbcol = 6;
               }
               echo "<div align='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'><tr><th colspan='$nbcol'>";
               echo PluginAdditionalalertsInfocomAlert::getTypeName(2) . "</th></tr>";
               echo "<tr><th>" . __('Name') . "</th>";
               if (Session::isMultiEntitiesMode()) {
                  echo "<th>" . __('Entity') . "</th>";
               }
               echo "<th>" . __('Type') . "</th>";
               echo "<th>" . __('Operating system') . "</th>";
               echo "<th>" . __('Status') . "</th>";
               echo "<th>" . __('Location') . "</th>";
               echo "<th>" . __('User') . " / " . __('Group') . " / " . __('Alternate username') . "</th></tr>";
               while ($data = $DB->fetch_array($result)) {

                  echo PluginAdditionalalertsInfocomAlert::displayBody($data);
               }
               echo "</table></div>";
            } else {
               echo "<br><div align='center'><b>" . __('No computers with no buy date', 'additionalalerts') . "</b></div>";
            }
            echo "<br>";
         }
      }

      if ($additionalalerts_ink != 0) {
         $plugin = new Plugin();
         if ($plugin->isActivated("fusioninventory")
            && $DB->tableExists("glpi_plugin_fusioninventory_printercartridges")) {
            if (Session::haveRight("cartridge", READ)) {
               $query  = PluginAdditionalalertsInkAlert::query($_SESSION["glpiactiveentities_string"]);
               $result = $DB->query($query);

               if ($DB->numrows($result) > 0) {
                  if (Session::isMultiEntitiesMode()) {
                     $nbcol = 4;
                  } else {
                     $nbcol = 3;
                  }
                  echo "<div align='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'>";
                  echo "<tr><th colspan='$nbcol'>" . __('Cartridges whose level is low', 'additionalalerts') . "</th></tr>";
                  echo "<tr>";
                  echo "<th>" . __('Printer') . "</th>";
                  if (Session::isMultiEntitiesMode()) {
                     echo "<th>" . __('Entity') . "</th>";
                  }
                  echo "<th>" . __('Cartridge') . "</th>";
                  echo "<th>" . __('Ink level', 'additionalalerts') . "</th></tr>";

                  while ($data = $DB->fetch_array($result)) {
                     echo PluginAdditionalalertsInkAlert::displayBody($data);
                  }
                  echo "</table></div>";
               } else {
                  echo "<br><div align='center'><b>" . __('No cartridge is below the threshold', 'additionalalerts') . "</b></div>";
               }
            }
         } else {
            echo "<br><div align='center'><b>" . __('Ink level alerts', 'additionalalerts') . " : " . __('Fusioninventory plugin is not installed', 'additionalalerts') . "</b></div>";
         }
      }

      if ($additionalalerts_ticket_unresolved != 0) {
         $entities = PluginAdditionalalertsTicketUnresolved::getEntitiesToNotify('delay_ticket_alert');

         foreach ($entities as $entity => $delay_ticket_alert) {
            $query  = PluginAdditionalalertsTicketUnresolved::query($delay_ticket_alert, $entity);
            $result = $DB->query($query);
            $nbcol  = 7;


            if ($DB->numrows($result) > 0) {

               echo "<div align='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'><tr><th colspan='$nbcol'>";
               echo __('Tickets unresolved since more', 'additionalalerts') . " " . $delay_ticket_alert . " " . _n('Day', 'Days', 2) . ", " . __('Entity') . " : " . Dropdown::getDropdownName("glpi_entities", $entity) . "</th></tr>";
               echo "<tr><th>" . __('Title') . "</th>";
               echo "<th>" . __('Entity') . "</th>";
               echo "<th>" . __('Status') . "</th>";
               echo "<th>" . __('Opening date') . "</th>";
               echo "<th>" . __('Last update') . "</th>";
               echo "<th>" . __('Technician') . "</th>";
               echo "<th>" . __('Manager') . "</th>";

               while ($data = $DB->fetch_array($result)) {
                  echo PluginAdditionalalertsTicketUnresolved::displayBody($data);
               }


               echo "</table></div>";
            } else {
               echo "<br><div align='center'><b>" . __('No tickets unresolved since more', 'additionalalerts') . " " .
                    $delay_ticket_alert . " " . _n('Day', 'Days', 2) . ", " . __('Entity') . " : " . Dropdown::getDropdownName("glpi_entities", $entity) . "</b></div>";
            }

            echo "<br>";
         }
      }
   }

}
