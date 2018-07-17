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
 * Class PluginAdditionalalertsNotificationType
 */
class PluginAdditionalalertsNotificationType extends CommonDBTM {

   static $rightname = "plugin_additionalalerts";

   /**
    * Get the standard massive actions which are forbidden
    *
    * @since version 0.84
    *
    * @return array of massive actions
    **/
   public function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   /**
    *
    */
   function configType() {
      global $DB;

      $rand = mt_rand();

      $query = "SELECT *
              FROM `" . $this->getTable() . "`
              ORDER BY `types_id` ASC ";
      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         if ($number != 0) {

            echo "<div align='center'>";

            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['item' => __CLASS__, 'container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);

            echo "<table class='tab_cadre_fixe' cellpadding='5'>";
            echo "<tr>";
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            echo "</th>";
            echo "<th>" . __('Type') . "</th>";
            echo "</tr>";
            while ($ligne = $DB->fetch_array($result)) {
               echo "<tr class='tab_bg_1'>";
               echo "<td width='10' class='center'>";
               Html::showMassiveActionCheckBox(__CLASS__, $ligne['id']);
               echo "</td>";
               echo "<td>" . Dropdown::getDropdownName("glpi_computertypes", $ligne["types_id"]) . "</td>";
               echo "</tr>";
            }
            echo "</table>";

            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);

            Html::closeForm();
            echo "</div>";

         }
      }
   }

   /**
    * Get the specific massive actions
    *
    * @since version 0.84
    *
    * @param $checkitem link item to check right   (default NULL)
    *
    * @return an $array of massive actions
    */
   public function getSpecificMassiveActions($checkitem = null) {


      $actions['PluginAdditionalalertsNotificationType' . MassiveAction::CLASS_ACTION_SEPARATOR . 'purge'] = __('Delete');

      return $actions;
   }

   /**
    * @param MassiveAction $ma
    *
    * @return bool|false
    */
   /**
    * @param MassiveAction $ma
    *
    * @return bool|false
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'purge':
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    *
    * @param MassiveAction $ma
    * @param CommonDBTM    $item
    * @param array         $ids
    *
    * @return nothing|void
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      $type = new self();

      switch ($ma->getAction()) {
         case "purge":

            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  if ($type->delete(['id' => $key])) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            break;
      }
   }
}
