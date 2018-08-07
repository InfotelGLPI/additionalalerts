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

// Class NotificationTarget
/**
 * Class PluginAdditionalalertsNotificationTargetInfocomAlert
 */
class PluginAdditionalalertsNotificationTargetInfocomAlert extends NotificationTarget
{

   static $rightname = "plugin_additionalalerts";

   /**
    * @return array
    */
   function getEvents() {
      return ['notinfocom' => PluginAdditionalalertsInfocomAlert::getTypeName(2)];
   }

   /**
    * @param $event
    * @param array $options
    */
   function addDataForTemplate($event, $options = []) {
      global $CFG_GLPI;

      $this->data['##notinfocom.entity##'] =
         Dropdown::getDropdownName('glpi_entities',
            $options['entities_id']);
      $this->data['##lang.notinfocom.entity##'] = __('Entity');

      $events = $this->getAllEvents();

      $this->data['##lang.notinfocom.title##'] = $events[$event];

      $this->data['##lang.notinfocom.name##'] = __('Name');
      $this->data['##lang.notinfocom.urlname##'] = __('URL');
      $this->data['##lang.notinfocom.computertype##'] = __('Type');
      $this->data['##lang.notinfocom.operatingsystem##'] = __('Operating system');
      $this->data['##lang.notinfocom.state##'] = __('Status');
      $this->data['##lang.notinfocom.location##'] = __('Location');
      $this->data['##lang.notinfocom.urluser##'] = __('URL');
      $this->data['##lang.notinfocom.urlgroup##'] = __('URL');
      $this->data['##lang.notinfocom.user##'] = __('User');
      $this->data['##lang.notinfocom.group##'] = __('Group');

      foreach ($options['notinfocoms'] as $id => $notinfocom) {
         $tmp = [];

         $tmp['##notinfocom.urlname##'] = urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=computer_" .
            $notinfocom['id']);
         $tmp['##notinfocom.name##'] = $notinfocom['name'];
         $tmp['##notinfocom.computertype##'] = Dropdown::getDropdownName("glpi_computertypes", $notinfocom['computertypes_id']);
         $tmp['##notinfocom.operatingsystem##'] = Dropdown::getDropdownName("glpi_operatingsystems", $notinfocom['operatingsystems_id']);
         $tmp['##notinfocom.state##'] = Dropdown::getDropdownName("glpi_states", $notinfocom['states_id']);
         $tmp['##notinfocom.location##'] = Dropdown::getDropdownName("glpi_locations", $notinfocom['locations_id']);

         $tmp['##notinfocom.urluser##'] = urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=user_" .
            $notinfocom['users_id']);

         $tmp['##notinfocom.urlgroup##'] = urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=group_" .
            $notinfocom['groups_id']);
         $dbu = new DbUtils();
         $tmp['##notinfocom.user##'] = $dbu->getUserName($notinfocom['users_id']);
         $tmp['##notinfocom.group##'] = Dropdown::getDropdownName("glpi_groups", $notinfocom['groups_id']);
         $tmp['##notinfocom.contact##'] = $notinfocom['contact'];

         $this->data['notinfocoms'][] = $tmp;
      }
   }

   /**
    *
    */
   function getTags() {

      $tags = ['notinfocom.name' => __('Name'),
         'notinfocom.urlname' => __('URL') . " " . __('Name'),
         'notinfocom.computertype' => __('Type'),
         'notinfocom.operatingsystem' => __('Operating system'),
         'notinfocom.state' => __('Status'),
         'notinfocom.location' => __('Location'),
         'notinfocom.user' => __('User'),
         'notinfocom.urluser' => __('URL') . " " . __('User'),
         'notinfocom.group' => __('Group'),
         'notinfocom.urlgroup' => __('URL') . " " . __('Group'),
         'notinfocom.contact' => __('Alternate username')];
      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag' => $tag, 'label' => $label,
            'value' => true]);
      }

      $this->addTagToList(['tag' => 'additionalalerts',
         'label' => PluginAdditionalalertsInfocomAlert::getTypeName(2),
         'value' => false,
         'foreach' => true,
         'events' => ['notinfocom']]);

      asort($this->tag_descriptions);
   }
}
