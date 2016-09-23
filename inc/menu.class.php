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
 
class PluginAdditionalalertsMenu extends CommonGLPI {
   static $rightname = 'plugin_additionalalerts';

   static function getMenuName() {
      return _n('Other alert', 'Others alerts', 2, 'additionalalerts');
   }

   static function getMenuContent() {
      global $CFG_GLPI;

      $menu                                           = array();
      $menu['title']                                  = self::getMenuName();
      $menu['page']                                   = "/plugins/additionalalerts/front/additionalalert.form.php";
      $menu['links']['search']                        = PluginAdditionalalertsAdditionalalert::getFormURL(false);

      return $menu;
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['plugins']['types']['PluginAdditionalalertsMenu'])) {
         unset($_SESSION['glpimenu']['plugins']['types']['PluginAdditionalalertsMenu']); 
      }
      if (isset($_SESSION['glpimenu']['plugins']['content']['pluginadditionalalertsmenu'])) {
         unset($_SESSION['glpimenu']['plugins']['content']['pluginadditionalalertsmenu']); 
      }
   }
}