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
 * Class PluginAdditionalalertsNotificationTargetTicketUnresolved
 */
class PluginAdditionalalertsNotificationTargetTicketUnresolved extends NotificationTarget {

   static $rightname = "plugin_additionalalerts";

   /**
    * @return array
    */
   function getEvents() {
      return ['ticketunresolved' => PluginAdditionalalertsTicketUnresolved::getTypeName(2)];
   }

   /**
    * Get tags
    */
   function getTags() {

      // Get ticket tags
      $notificationTargetTicket = NotificationTarget::getInstance(new Ticket(), 'alertnotclosed', []);
      $notificationTargetTicket->getTags();
      $this->tag_descriptions = $notificationTargetTicket->tag_descriptions;

      asort($this->tag_descriptions);
   }

   /**
    * Get datas for template
    *
    * @param type       $event
    * @param array|type $options
    */
   function addDataForTemplate($event, $options = []) {

      // Add ticket translation
      $ticket = new Ticket();
      $ticket->getEmpty();

      $notificationTargetTicket                    = NotificationTarget::getInstance($ticket, 'ticketunresolved', $options);
      $notificationTargetTicket->obj->fields['id'] = 0;
      $notificationTargetTicket->addDataForTemplate('alertnotclosed', $options);

      $this->data = $notificationTargetTicket->data;

   }

   /**
    * Get additionnals targets for ITIL objects
    *
    * @param $event (default '')
    **/
   function addAdditionalTargets($event = '') {
      $this->notification_targets        = [];
      $this->notification_targets_labels = [];

      $this->addTarget(Notification::SUPERVISOR_ASSIGN_GROUP,
                       __('Manager of the group in charge of the ticket'));

      $this->addTarget(Notification::ASSIGN_TECH, __('Technician in charge of the ticket'));
   }

   /**
    * @param $data
    * @param $options
    */
   public function addSpecificTargets($data, $options) {

      $items = reset($options['items']);
      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['items_id']) {

         case Notification::SUPERVISOR_ASSIGN_GROUP :
         case Notification::ASSIGN_TECH :
            return $this->addUserByID("users_id", $items);
      }
   }

   public function addUserByID($field, $items = []) {
      global $DB;

      if (!empty($items[$field])) {
         //Look for the user by his id
         $criteria = $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
         $criteria['FROM'] = User::getTable();
         $criteria['WHERE'][User::getTable() . '.id'] = $items[$field];
         $iterator = $DB->request($criteria);

         while ($data = $iterator->next()) {
            //Add the user email and language in the notified users list
            $this->addToRecipientsList($data);
         }
      }
   }


}
