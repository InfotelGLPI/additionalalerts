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
 * Class PluginAdditionalalertsInkThreshold
 */
class PluginAdditionalalertsInkThreshold extends CommonDBTM {
   /**
    * @param $target
    * @param $id
    */
   function showForm($target, $id) {
      global $DB;

      $query  = "SELECT * FROM " . $this->getTable() . " WHERE cartridges_id='" . $id . "'";
      $result = $DB->query($query);
      if ($DB->numrows($result) == "0") {
         $this->add(["cartridges_id" => $id]);
         $result = $DB->query($query);
      }
      $data = $DB->fetch_assoc($result);

      echo "<form action='" . $target . "' method='post'>";
      echo "<table class='tab_cadre' cellpadding='5' width='950'>";
      echo "<tr><th colspan='2'>" . __('Ink level alerts', 'additionalalerts') . "</th></tr>";
      if ($DB->tableExists("glpi_plugin_fusioninventory_printercartridges")) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Ink level alerts', 'additionalalerts') . "</td>";
         echo "<td>";
         echo "<input type='text' name='threshold' size='3' value='" . $data["threshold"] . "'> %";
         echo "</td>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='2' align='center'>";
         echo "<input type='submit' name='update_threshold' class='submit' value='" . _sx('button', 'Save') . "'>";
         echo "</td/>";
         echo "</tr>";
      } else {
         echo "<tr><td><div align='center'><b>" . __('Fusioninventory plugin is not installed', 'additionalalerts') . "</b></div></td></tr>";
      }
      echo "</table>";
      echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
      Html::closeForm();
   }
}
