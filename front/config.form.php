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

use GlpiPlugin\Additionalalerts\AdditionalAlert;
use GlpiPlugin\Additionalalerts\Config;
use GlpiPlugin\Additionalalerts\Menu;

if (Plugin::isPluginActive("additionalalerts")) {

   $config = new Config();
   if (isset($_POST["update"])) {
      $config->update($_POST);
      Html::back();
   } else {
      Html::header(AdditionalAlert::getTypeName(2), '', "admin", Menu::class);
      $config = new Config();
      $config->showConfigForm();
      Html::footer();
   }
} else {
   Html::header(__('Setup'), '', "config", "plugins");
   echo "<div class='alert alert-important alert-warning d-flex'>";
   echo "<b>" . __('Please activate the plugin', 'additionalalerts') . "</b></div>";
   Html::footer();
}
