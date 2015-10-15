<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Additionalalerts plugin for GLPI
 Copyright (C) 2003-2011 by the Additionalalerts Development Team.

 https://forge.indepnet.net/projects/additionalalerts
 -------------------------------------------------------------------------

 LICENSE
      
 This file is part of Additionalalerts.

 Additionalalerts is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Additionalalerts is distributed in the hope that it will be useful,
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

class PluginAdditionalalertsConfig extends CommonDBTM {
   
   static $rightname = "plugin_additionalalerts";

   static function getTypeName($nb=0) {
      return __('Plugin Setup', 'additionalalerts');
   }
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType()=='NotificationMailSetting' 
            && $item->getField('id') 
               && $CFG_GLPI["use_mailing"]) {
            return PluginAdditionalalertsAdditionalalert::getTypeName(2);
      } else if ($item->getType()=='Entity') {
            return PluginAdditionalalertsAdditionalalert::getTypeName(2);
      }
         return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType()=='NotificationMailSetting') {

         $target = $CFG_GLPI["root_doc"]."/plugins/additionalalerts/front/config.form.php";
         $conf = new PluginAdditionalalertsConfig;
         $conf->showForm($target);
         
      } else if ($item->getType()=='Entity') {
      
         PluginAdditionalalertsInfocomAlert::showNotificationOptions($item);
         PluginAdditionalalertsOcsAlert::showNotificationOptions($item);
         PluginAdditionalalertsInkAlert::showNotificationOptions($item);

      }
      return true;
   }
   
   function showForm($options=array()) {

      $this->getFromDB(1);
      
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Reminders frequency', 'additionalalerts') . " " . PluginAdditionalalertsInfocomAlert::getTypeName(2) . "</td><td>";
      Alert::dropdownYesNo(array('name'=>"use_infocom_alert",
                              'value'=>$this->fields["use_infocom_alert"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td >" . __('Reminders frequency', 'additionalalerts') . " " . __('New imported computers from OCS-NG', 'additionalalerts') . "</td><td>";
      Alert::dropdownYesNo(array('name'=>"use_newocs_alert",
                              'value'=>$this->fields["use_newocs_alert"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td >" . __('OCS-NG Synchronization alerts', 'additionalalerts') . "</td><td>";
      Alert::dropdownIntegerNever('delay_ocs',
                                  $this->fields["delay_ocs"],
                                  array('max'=>99));
      echo "&nbsp;"._n('Day','Days',2)."</td></tr>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td >" . __('Cartridges whose level is low', 'additionalalerts') . "</td><td>";
      $plugin = new Plugin();
      if ($plugin->isActivated("fusioninventory") && 
            TableExists("glpi_plugin_fusioninventory_printercartridges")) {
         Alert::dropdownYesNo(array('name'=>"use_ink_alert",
                                   'value'=>$this->fields["use_ink_alert"]));
      } else {
        echo "<div align='center'><b>".__('Fusioninventory plugin is not installed', 'additionalalerts')."</b></div>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "</td></tr>";

      $this->showFormButtons($options);
      
      return true;
   }
}

?>