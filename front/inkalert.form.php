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
use GlpiPlugin\Additionalalerts\AdditionalAlert;
use GlpiPlugin\Additionalalerts\InkAlert;
use GlpiPlugin\Additionalalerts\InkPrinterState;
use GlpiPlugin\Additionalalerts\InkThreshold;


$state = new InkPrinterState();
$alert = new InkAlert();

if (isset($_POST["add"])) {
   $input = AdditionalAlert::checkEntitySettingWrite($alert, $_POST);
   unset($input['id']);
   $alert->add($input);
} else if (isset($_POST["update"])) {
   $input = AdditionalAlert::checkEntitySettingWrite($alert, $_POST);
   $alert->update($input);
} else if (isset($_POST["add_state"])) {
   if (!$alert->canUpdate()) {
      throw new AccessDeniedHttpException();
   }
   $state->add(['states_id' => (int) ($_POST['states_id'] ?? 0)]);
} else if (isset($_POST["delete_state"])) {
   if (!$alert->canUpdate()) {
      throw new AccessDeniedHttpException();
   }
   AdditionalAlert::deleteCheckedItems($state, $_POST);
} else if (isset($_POST["update_threshold"])) {
   $printers_id = (int) ($_POST['printers_id'] ?? 0);
   $printer     = new Printer();
   if (!InkThreshold::canUpdate()) {
      throw new AccessDeniedHttpException();
   }
   // The threshold belongs to a printer: the user must be allowed to update it
   $printer->check($printers_id, UPDATE);
   InkThreshold::saveForPrinter($printers_id, $_POST['threshold'] ?? InkThreshold::DEFAULT_THRESHOLD);
}
Html::back();
