<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Additionalalerts plugin for GLPI
 Copyright (C) 2003-2011 by the Additionalalerts Development Team.

 https://forge.indepnet.net/projects/additionalalerts
 -------------------------------------------------------------------------

 LICENSE
      
 This file is part of Additionalalerts.

 Additionalalerts is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Additionalalerts is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with additionalalerts. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class PluginAdditionalalertsNotificationTargetTicketUnresolved extends NotificationTarget {
   
   static $rightname = "plugin_additionalalerts";
   
   function getEvents() {
      return array ('ticketunresolved' => PluginAdditionalalertsTicketUnresolved::getTypeName(2));
   }

   /**
    * Get tags
    */
   function getTags() {

      // Get ticket tags
      $notificationTargetTicket = NotificationTarget::getInstance(new Ticket(),'alertnotclosed',array());
      $notificationTargetTicket->getTags();
      $this->tag_descriptions = $notificationTargetTicket->tag_descriptions;
      
           
      asort($this->tag_descriptions);
   }
   
   /**
    * Get datas for template
    * 
    * @param type $event
    * @param type $options
    */
   function getDatasForTemplate($event, $options = array()) {
 
      // Add ticket translation
      $ticket = new Ticket();
      $ticket->getEmpty();
      
      $notificationTargetTicket = NotificationTarget::getInstance($ticket,'ticketunresolved',$options);
      $notificationTargetTicket->obj->fields['id'] = 0;
      $notificationTargetTicket->getDatasForTemplate('alertnotclosed', $options);
      
      $this->datas = $notificationTargetTicket->datas;

   }
   
      /**
    * Add linked group supervisor to the notified user list
    *
    * @param $type type of linked groups
   **/
   function getLinkedGroupSupervisorByType($tickets_id, $type) {
      global $DB;

      $grouplinktable = "glpi_groups_tickets";
      $fkfield        = "tickets_id";

      $query = "SELECT `groups_id`
                FROM `$grouplinktable`
                WHERE `$grouplinktable`.`$fkfield` = '".$tickets_id."'
                      AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->getAddressesByGroup(1, $data['groups_id']);
      }
   }
   
    /**
    * Add linked users to the notified users list
    *
    * @param $type type of linked users
   **/
   function getLinkedUserByType($tickets_id, $type) {
      global $DB, $CFG_GLPI;

      $userlinktable = "glpi_tickets_users";
      $fkfield       = "tickets_id";

      //Look for the user by his id
      $query =        $this->getDistinctUserSql().",
                      `$userlinktable`.`use_notification` AS notif,
                      `$userlinktable`.`alternative_email` AS altemail
               FROM `$userlinktable`
               LEFT JOIN `glpi_users` ON (`$userlinktable`.`users_id` = `glpi_users`.`id`)".
               $this->getProfileJoinSql()."
               WHERE `$userlinktable`.`$fkfield` = '".$tickets_id."'
                     AND `$userlinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the user email and language in the notified users list
         if ($data['notif']) {
            $author_email = UserEmail::getDefaultForUser($data['users_id']);
            $author_lang  = $data["language"];
            $author_id    = $data['users_id'];

            if (!empty($data['altemail'])
                && ($data['altemail'] != $author_email)
                && NotificationMail::isUserAddressValid($data['altemail'])) {
               $author_email = $data['altemail'];
            }
            if (empty($author_lang)) {
               $author_lang = $CFG_GLPI["language"];
            }
            if (empty($author_id)) {
               $author_id = -1;
            }
            $this->addToAddressesList(array('email'    => $author_email,
                                            'language' => $author_lang,
                                            'users_id' => $author_id));
         }
      }

      // Anonymous user
      $query = "SELECT `alternative_email`
                FROM `$userlinktable`
                WHERE `$userlinktable`.`$fkfield` = '".$tickets_id."'
                      AND `$userlinktable`.`users_id` = 0
                      AND `$userlinktable`.`use_notification` = 1
                      AND `$userlinktable`.`type` = '$type'";
      foreach ($DB->request($query) as $data) {
         if (NotificationMail::isUserAddressValid($data['alternative_email'])) {
            $this->addToAddressesList(array('email'    => $data['alternative_email'],
                                            'language' => $CFG_GLPI["language"],
                                            'users_id' => -1));
         }
      }
   }

   
   /**
    * Get specifics targets for ITIL objects
    *
    * @param $data      array
    * @param $options   array
   **/
   function getAddressesByTarget($data, $options=array()) {
      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :

            switch ($data['items_id']) {
               case Notification::ASSIGN_TECH :
                  foreach($options['items'] as $item){
                     $this->getLinkedUserByType($item['id'], CommonITILActor::ASSIGN);
                  }
                  break;

               //Send to the supervisor of group in charge of the ITIL object
               case Notification::SUPERVISOR_ASSIGN_GROUP :
                  foreach($options['items'] as $item){
                     $this->getLinkedGroupSupervisorByType($item['id'], CommonITILActor::ASSIGN);
                  }
                  break;
            }
         }

   }
   
      /**
    * Get additionnals targets for ITIL objects
    *
    * @param $event  (default '')
   **/
   function getAdditionalTargets($event='') {

         $this->addTarget(Notification::SUPERVISOR_ASSIGN_GROUP,
                          __('Manager of the group in charge of the ticket'));

         $this->addTarget(Notification::ASSIGN_TECH, __('Technician in charge of the ticket'));
   }
   
     /**
    * Raise a notification event event
    *
    * @param $event           the event raised for the itemtype
    * @param $item            the object which raised the event
    * @param $options array   of options used
    * @param $label           used for debugEvent() (default '')
   **/
   static function raiseEventTicket($event, $item, $options=array(), $label='') {
      global $CFG_GLPI;

      //If notifications are enabled in GLPI's configuration
      if ($CFG_GLPI["use_mailing"]) {
         $email_processed    = array();
         $email_notprocessed = array();
         //Get template's information
         $template           = new NotificationTemplate();
         
         $notificationtarget = NotificationTarget::getInstance($item,$event,$options);

         if (!$notificationtarget) {
            return false;
         }
         $entity = $options["entities_id"];

         //Foreach notification
         foreach (Notification::getNotificationsByEventAndType($event, $item->getType(), $entity)
                  as $data) {
            $targets = getAllDatasFromTable('glpi_notificationtargets',
                                            'notifications_id = '.$data['id']);
           
            $notificationtarget->clearAddressesList();

            //Process more infos (for example for tickets)
            $notificationtarget->addAdditionnalInfosForTarget();

            $template->getFromDB($data['notificationtemplates_id']);
            $template->resetComputedTemplates();

            //Set notification's signature (the one which corresponds to the entity)
            $template->setSignature(Notification::getMailingSignature($entity));

            $notify_me = false;
            if (Session::isCron()) {
               // Cron notify me
               $notify_me = true;
            } else {
               // Not cron see my pref
               $notify_me = $_SESSION['glpinotification_to_myself'];
            }

            //Foreach notification targets
            foreach ($targets as $target) {
               if($options['notifType'] == "TECH"
                  && $target['items_id'] == Notification::SUPERVISOR_ASSIGN_GROUP 
                  && $target['type'] == Notification::USER_TYPE){
                  continue;
                  
               } else if($options['notifType'] == "SUPERVISOR" 
                  && $target['items_id'] == Notification::ASSIGN_TECH 
                  && $target['type'] == Notification::USER_TYPE){
                  continue;
               }
               //Get all users affected by this notification
               $notificationtarget->getAddressesByTarget($target,$options);

               foreach ($notificationtarget->getTargets() as $user_email => $users_infos) {
                  if ($label
                      || $notificationtarget->validateSendTo($event, $users_infos, $notify_me)) {
                     //If the user have not yet been notified
                     if (!isset($email_processed[$users_infos['language']][$users_infos['email']])) {
                        //If ther user's language is the same as the template's one
                        if (isset($email_notprocessed[$users_infos['language']]
                                                     [$users_infos['email']])) {
                           unset($email_notprocessed[$users_infos['language']]
                                                    [$users_infos['email']]);
                        }
                        $options['item'] = $item;
                        if ($tid = $template->getTemplateByLanguage($notificationtarget,
                                                                    $users_infos, $event,
                                                                    $options)) {
                           //Send notification to the user
                           if ($label == '') {
                              $datas = $template->getDataToSend($notificationtarget, $tid,
                                                                $users_infos, $options);
                              $datas['_notificationtemplates_id'] = $data['notificationtemplates_id'];
                              $datas['_itemtype']                 = $item->getType();
                              $datas['_items_id']                 = $item->getID();
                              $datas['_entities_id']              = $entity;

                              Notification::send($datas);
                           } else {
                              $notificationtarget->getFromDB($target['id']);
                              echo "<tr class='tab_bg_2'><td>".$label."</td>";
                              echo "<td>".$notificationtarget->getNameID()."</td>";
                              echo "<td>".sprintf(__('%1$s (%2$s)'), $template->getName(),
                                                  $users_infos['language'])."</td>";
                              echo "<td>".$users_infos['email']."</td>";
                              echo "</tr>";
                           }
                           $email_processed[$users_infos['language']][$users_infos['email']]
                                                                     = $users_infos;

                        } else {
                           $email_notprocessed[$users_infos['language']][$users_infos['email']]
                                                                        = $users_infos;
                        }
                     }
                  }
               }
            }
         }
      }
      unset($email_processed);
      unset($email_notprocessed);
      $template = null;
      return true;
   }

}

?>