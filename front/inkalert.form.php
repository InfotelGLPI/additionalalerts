<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 additionalalerts plugin for GLPI
 Copyright (C) 2009-2022 by the additionalalerts Development Team.

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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Additionalalerts\InkAlert;
use GlpiPlugin\Additionalalerts\InkPrinterState;
use GlpiPlugin\Additionalalerts\InkThreshold;


$state = new InkPrinterState();
$alert = new InkAlert();

if (isset($_POST["add"])) {
   if ($alert->canUpdate()) {
      $newID = $alert->add($_POST);
   }
} else if (isset($_POST["update"])) {
   if ($alert->canUpdate()) {
      $alert->update($_POST);
   }
} else if (isset($_POST["add_state"])) {
   if ($alert->canUpdate()) {
      $newID = $state->add($_POST);
   }
} else if (isset($_POST["delete_state"])) {
   if ($alert->canUpdate()) {
      $state->getFromDB($_POST["id"]);

      foreach ($_POST["item"] as $key => $val) {
         if ($val == 1) {
            $state->delete(['id' => $key]);
         }
      }
   }

} else if (isset($_POST["update_threshold"])) {

   $InkThreshold = new InkThreshold();
   if ($alert->canUpdate()) {
       $InkThreshold->update($_POST);
   } else {
       throw new AccessDeniedHttpException();

   }
}
Html::back();
