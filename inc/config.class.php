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
 * Class PluginAdditionalalertsConfig
 */
class PluginAdditionalalertsConfig extends CommonDBTM {

   static $rightname = "plugin_additionalalerts";

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __('Plugin setup', 'additionalalerts');
   }

   public static function getConfig() {
      static $config = null;

      if (is_null($config)) {
         $config = new self();
      }
      $config->getFromDB(1);

      return $config;
   }

   /**
    * @param CommonGLPI $item
    * @param int $withtemplate
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $CFG_GLPI;

      if ($item->getType()=='NotificationMailSetting'
            && $item->getField('id')
               && $CFG_GLPI["notifications_mailing"]) {
            return PluginAdditionalalertsAdditionalalert::getTypeName(2);
      } else if ($item->getType()=='Entity') {
            return PluginAdditionalalertsAdditionalalert::getTypeName(2);
      }
         return '';
   }

   /**
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;

      if ($item->getType()=='NotificationMailSetting') {

         $target = $CFG_GLPI["root_doc"]."/plugins/additionalalerts/front/config.form.php";
         $conf = new PluginAdditionalalertsConfig;
         $conf->showForm(['target' =>$target]);

      } else if ($item->getType()=='Entity') {

         PluginAdditionalalertsInfocomAlert::showNotificationOptions($item);
         PluginAdditionalalertsInkAlert::showNotificationOptions($item);
         PluginAdditionalalertsTicketUnresolved::showNotificationOptions($item);

      }
      return true;
   }

   /**
    * @param array $options
    * @return bool
    */
   function showForm($options = []) {
      global $DB;

      $this->getFromDB(1);
      $options['colspan'] = 1;
      $this->showFormHeader($options);
      $plugin = new Plugin();

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . PluginAdditionalalertsInfocomAlert::getTypeName(2) . "</td><td>";
      Alert::dropdownYesNo(['name'=>"use_infocom_alert",
                              'value'=>$this->fields["use_infocom_alert"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td >" . __('Cartridges whose level is low', 'additionalalerts') . "</td><td>";
      if ($plugin->isActivated("fusioninventory") &&
            $DB->tableExists("glpi_plugin_fusioninventory_printercartridges")) {
         Alert::dropdownYesNo(['name'=>"use_ink_alert",
                                   'value'=>$this->fields["use_ink_alert"]]);
      } else {
         echo "<div align='center'><b>".__('Fusioninventory plugin is not installed', 'additionalalerts')."</b></div>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Unresolved Ticket Alerts', 'additionalalerts') . "</td><td>";

      Alert::dropdownIntegerNever('delay_ticket_alert',
                                  $this->fields["delay_ticket_alert"],
                                  ['max'=>99]);
      echo "&nbsp;"._n('Day', 'Days', 2)."</td></tr>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   //----------------- Getters and setters -------------------//

   public function useInfocomAlert() {
      return $this->fields['use_infocom_alert'];
   }

   public function useInkAlert() {
      return $this->fields['use_ink_alert'];
   }

   public function getDelayTicketAlert() {
      return $this->fields['delay_ticket_alert'];
   }
}

