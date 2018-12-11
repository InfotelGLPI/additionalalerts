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

define('PLUGIN_ADDITIONALALERTS_VERSION', '2.2.0');

// Init the hooks of the plugins -Needed
function plugin_init_additionalalerts() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['additionalalerts'] = true;
   $PLUGIN_HOOKS['change_profile']['additionalalerts'] = ['PluginAdditionalalertsProfile', 'initProfile'];

   Plugin::registerClass('PluginAdditionalalertsInfocomAlert', [
      'notificationtemplates_types' => true,
      'addtabon'                    => 'CronTask'
   ]);

   Plugin::registerClass('PluginAdditionalalertsTicketUnresolved', [
      'notificationtemplates_types' => true
   ]);

   Plugin::registerClass('PluginAdditionalalertsInkAlert', [
      'notificationtemplates_types' => true,
      'addtabon'                    => ['CartridgeItem', 'CronTask']
   ]);

   Plugin::registerClass('PluginAdditionalalertsProfile',
                         ['addtabon' => 'Profile']);

   Plugin::registerClass('PluginAdditionalalertsConfig',
                         ['addtabon' => ['NotificationMailSetting', 'Entity']]);

   if (Session::getLoginUserID()) {
      // Display a menu entry ?
      if (Session::haveRight("plugin_additionalalerts", READ)) {
         $PLUGIN_HOOKS['config_page']['additionalalerts']           = 'front/config.form.php';
         $PLUGIN_HOOKS["menu_toadd"]['additionalalerts']['admin'] = 'PluginAdditionalalertsMenu';
      }
   }

}

// Get the name and the version of the plugin - Needed
/**
 * @return array
 */
function plugin_version_additionalalerts() {

   return [
      'name'           => _n('Other alert', 'Others alerts', 2, 'additionalalerts'),
      'version'        => PLUGIN_ADDITIONALALERTS_VERSION,
      'license'        => 'GPLv2+',
      'oldname'        => 'alerting',
      'author'         => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a> / Konstantin Kabassanov",
      'oldname'        => 'alerting',
      'homepage'       => 'https://github.com/InfotelGLPI/additionalalerts',
      'requirements'   => [
         'glpi' => [
            'min' => '9.4',
            'dev' => false
         ]
      ]
   ];
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
/**
 * @return bool
 */
function plugin_additionalalerts_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.4', 'lt')
       || version_compare(GLPI_VERSION, '9.5', 'ge')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.4');
      }
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
/**
 * @return bool
 */
function plugin_additionalalerts_check_config() {
   return true;
}
