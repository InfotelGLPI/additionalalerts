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

include ('../../../inc/includes.php');

$state=new PluginAdditionalalertsInkPrinterState();
$alert=new PluginAdditionalalertsInkAlert();

if (isset($_POST["add"])) {
   if ($alert->canUpdate()) {
      $newID=$alert->add($_POST);
   }
} else if (isset($_POST["update"])) {
   if ($alert->canUpdate()) {
      $alert->update($_POST);
   }
} else if (isset($_POST["add_state"])) {
   if ($alert->canUpdate()) {
      $newID=$state->add($_POST);
   }
} else if (isset($_POST["delete_state"])) {
      if ($alert->canUpdate()) {
         $state->getFromDB($_POST["id"],-1);

         foreach ($_POST["item"] as $key => $val) {
         if ($val==1) {
            $state->delete(array('id'=>$key));
         }
      }
   }

} else if (isset($_POST["update_threshold"])) {

   $PluginAdditionalalertsInkThreshold=new PluginAdditionalalertsInkThreshold();
   if ($alert->canUpdate()) {
      $PluginAdditionalalertsInkThreshold->update($_POST);
   } else {
      Html::displayRightError();
   }
}
Html::back();

?>