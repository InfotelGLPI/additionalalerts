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

class PluginAdditionalalertsNotificationState extends CommonDBTM {
   
   static $rightname = "plugin_additionalalerts";
   
   function showForm($target) {
      global $DB;
      
      $rand=mt_rand();
      
      $query = "SELECT *
                 FROM `".$this->getTable()."`
                 ORDER BY `states_id` ASC ";
      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         if ($number != 0) {

            echo "<div align='center'><form method='post' name='massiveaction_form$rand' id='massiveaction_form$rand'  action=\"$target\">";
            echo "<table class='tab_cadre_fixe' cellpadding='5'>";
            echo "<tr>";
            echo "<th></th><th>"._n('Status','Statuses',2)."</th>";
            echo "</tr>";
            while($ligne= $DB->fetch_array($result)) {
               $ID=$ligne["id"];
               echo "<tr class='tab_bg_1'>";
               echo "<td width='10' class='center'>";
               echo "<input type='hidden' name='id' value='$ID'>";
               echo "<input type='checkbox' name='item[$ID]' value='1'>";
               echo "</td>";
               echo "<td>".Dropdown::getDropdownName("glpi_states",$ligne["states_id"])."</td>";
               echo "</tr>";
            }
            
            Html::openArrowMassives("massiveaction_form$rand", true);
            Html::closeArrowMassives(array('delete_state' => __('Delete permanently')));
            
            echo "</table>";
            Html::closeForm();
            echo "</div>";
         }
      }
   }
}

?>