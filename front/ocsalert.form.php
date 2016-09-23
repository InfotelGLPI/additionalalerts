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

$state=new PluginAdditionalalertsNotificationState();
$ocsalert=new PluginAdditionalalertsOcsAlert();

if (isset($_POST["add"])) {
   
   if ($ocsalert->canUpdate()) {
      $newID=$ocsalert->add($_POST);
   }
   Html::back();

} else if (isset($_POST["update"])) {
   
   if ($ocsalert->canUpdate()) {
      $ocsalert->update($_POST);
   }
   Html::back();

} else if (isset($_POST["add_state"])) {
   
   if ($ocsalert->canUpdate()) {
      $newID=$state->add($_POST);
   }
   Html::back();

} else if (isset($_POST["delete_state"])) {
   
   if ($ocsalert->canUpdate()) {
      $state->getFromDB($_POST["id"],-1);
      foreach ($_POST["item"] as $key => $val) {
         if ($val==1) {
            $state->delete(array('id'=>$key));
         }
      }
   }
   Html::back();
   
}

?>