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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class PluginAdditionalalertsNotificationTargetInkAlert extends NotificationTarget {

   function getEvents() {
      return array ('ink' => __('Cartridges whose level is low', 'additionalalerts'));
   }

   function getDatasForTemplate($event,$options=array()) {
      global $CFG_GLPI;

      $this->datas['##ink.entity##'] = Dropdown::getDropdownName('glpi_entities', $options['entities_id']);
      $this->datas['##lang.ink.entity##'] =__('Entity');
  
      $events = $this->getAllEvents();

      $this->datas['##lang.ink.title##'] = $events[$event];

      $this->datas['##lang.ink.printer##'] = __('Printers'); 
      $this->datas['##lang.ink.cartridge##'] = _n('Cartridge', 'Cartridges', 2);
      $this->datas['##lang.ink.state##'] = __('State');

      foreach($options['ink'] as $id => $ink) {
         $snmp = new PluginFusioninventoryPrinterCartridge();
         $snmp->getFromDB($ink["id"]);

         $cartridge = new CartridgeItem();
         $cartridge->getFromDB($snmp->fields["cartridges_id"]);

         $printer = new Printer();
         $printer->getFromDB($snmp->fields["printers_id"]);
 
         $tmp = array();
 
         $tmp['##ink.urlprinter##'] = urldecode($CFG_GLPI["url_base"]."/index.php?redirect=printer_".$printer->fields['id']);
         $tmp['##ink.printer##'] = $printer->fields['name'];
         $tmp['##ink.urlcartridge##'] = urldecode($CFG_GLPI["url_base"]."/index.php?redirect=cartridgeitem_".$cartridge->fields['id']);
         $tmp['##ink.cartridge##'] = $cartridge->fields['name']." (".$cartridge->fields['ref'].")";
         $tmp['##ink.state##'] = $snmp->fields['state'];
  
         $this->datas['inks'][] = $tmp;
      }
   }
  
   function getTags() {

      $tags = array('ink.printer' => __('Printers'),
                    'ink.printerurl' => 'URL '.__('Printers'),
                    'ink.cartridge' => _n('Cartridge', 'Cartridges', 2),
                    'ink.cartridgeurl' => 'URL '._n('Cartridge', 'Cartridges', 2),
                    'ink.state' => __('State'));
      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'=>$tag,'label'=>$label,
                                   'value'=>true));
      }
  
      $this->addTagToList(array('tag'=>'additionalalerts',
                                'label'=>__('Cartridges whose level is low', 'additionalalerts'),
                                'value'=>false,
                                'foreach'=>true,
                                'events'=>array('ink')));
  
      asort($this->tag_descriptions);
   }
}

?>