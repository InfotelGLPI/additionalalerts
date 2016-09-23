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

class PluginAdditionalalertsInkAlert extends CommonDBTM {
   
   static $rightname = "plugin_additionalalerts";
   
    static function getTypeName($nb = 0) {

        return __('Cartridges whose level is low', 'additionalalerts');
    }
   
    function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

        if ($item->getType()=='CronTask' && $item->getField('name')=="AdditionalalertsInk") {
            return __('Plugin setup', 'additionalalerts');
        } else if (get_class($item)=='CartridgeItem') {
            return PluginAdditionalalertsAdditionalalert::getTypeName(2);
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
        global $CFG_GLPI;

        if ($item->getType()=='CronTask') {
            $target = $CFG_GLPI["root_doc"]."/plugins/additionalalerts/front/inkalert.form.php";
            self::configCron($target,$item->getField('id'));
        } else if ($item->getType()=='CartridgeItem') {
            $PluginAdditionalalertsInkThreshold=new PluginAdditionalalertsInkThreshold();
            $PluginAdditionalalertsInkThreshold->showForm($CFG_GLPI["root_doc"]."/plugins/additionalalerts/front/inkalert.form.php",$item->getField('id'));
        }
        return true;
    }

    // Cron action
    static function cronInfo($name) {
       
        switch ($name) {
            case 'AdditionalalertsInk':
                return array (
                    'description' => __('Cartridges whose level is low', 'additionalalerts'));   // Optional
                break;
        }
        return array();
    }
   
    static function query($entities) {
        global $DB;

        $query = "SELECT DISTINCT(cartridges_id) FROM glpi_plugin_fusioninventory_printercartridges 
                  WHERE cartridges_id NOT IN (SELECT cartridges_id FROM glpi_plugin_additionalalerts_inkthresholds)";
        $cartridges = $DB->query($query);
        if ($DB->numrows($cartridges) > 0) {
            $PluginAdditionalalertsInkThreshold = new PluginAdditionalalertsInkThreshold();
            while ($cartridge = $DB->fetch_array($cartridges))
                $PluginAdditionalalertsInkThreshold->add($cartridge);
        } 
        
        $query = "SELECT c.id, p.name, p.entities_id
                  FROM glpi_plugin_fusioninventory_printercartridges AS c, 
                       glpi_plugin_additionalalerts_inkthresholds AS t,
                       glpi_cartridgeitems AS i,
                       glpi_printers as p
                  WHERE c.cartridges_id = t.cartridges_id
                    AND i.id = t.cartridges_id
                    AND c.printers_id = p.id
                    AND c.state <= t.threshold
                    AND p.entities_id IN ($entities)
                    AND p.states_id IN (SELECT states_id FROM glpi_plugin_additionalalerts_inkprinterstates)
                  ORDER BY p.name";
        
        return $query;    
    }
   
      
    static function displayBody($data) {
        global $CFG_GLPI;

        $body="";

        $snmp = new PluginFusioninventoryPrinterCartridge();
        $snmp->getFromDB($data["id"]);

        $cartridge = new CartridgeItem();
        $cartridge->getFromDB($snmp->fields["cartridges_id"]);

        $printer = new Printer();
        $printer->getFromDB($snmp->fields["printers_id"]);

        $body="<tr class='tab_bg_2'><td><a href=\"".$CFG_GLPI["root_doc"]."/front/printer.form.php?id=".$printer->fields["id"]."\">".$printer->fields["name"];

        if ($_SESSION["glpiis_ids_visible"] == 1 || empty($printer->fields["name"])) {
            $body.=" (";
            $body.=$printer->fields["id"].")";
        }
        $body.="</a></td>";
        if (Session::isMultiEntitiesMode())
            $body .= "<td align='center'>".Dropdown::getDropdownName("glpi_entities",$printer->fields["entities_id"])."</td>";

        $body .= "<td align='center'><a href=\"".$CFG_GLPI["root_doc"]."/front/cartridgeitem.form.php?id=".$cartridge->fields["id"]."\">".$cartridge->fields["name"]." (".$cartridge->fields["ref"].")</a></td>";

        $body .= "<td align='center'>".$snmp->fields["state"]."%</td>";
        $body .= "</tr>";

        return $body;
    }
   
   
    static function getEntitiesToNotify($field,$with_value=false) {
        global $DB,$CFG_GLPI;

        $query = "SELECT `entities_id` as `entity`,`$field`
                  FROM `glpi_plugin_additionalalerts_inkalerts`
                  ORDER BY `entities_id` ASC";

        $entities = array();
        $result = $DB->query($query);
 
         if ($DB->numrows($result) > 0) {
            foreach ($DB->request($query) as $entitydatas) {
                PluginAdditionalalertsInkAlert::getDefaultValueForNotification($field, $entities, $entitydatas);
            }
         } else {
         $config = new PluginAdditionalalertsConfig();
         $config->getFromDB(1);
         foreach (getAllDatasFromTable('glpi_entities') as $entity) {
            $entities[$entity['id']] = $config->fields[$field];
         }
      }

        return $entities;
    }

    static function getDefaultValueForNotification($field, &$entities, $entitydatas) {
        global $CFG_GLPI;
      
        $config = new PluginAdditionalalertsConfig();
        $config->getFromDB(1);
        //If there's a configuration for this entity & the value is not the one of the global config
        if (isset($entitydatas[$field]) && $entitydatas[$field] > 0) {
            $entities[$entitydatas['entity']] = $entitydatas[$field];
        }
        //No configuration for this entity : if global config allows notification then add the entity
        //to the array of entities to be notified
        else if ((!isset($entitydatas[$field]) || (isset($entitydatas[$field]) && $entitydatas[$field] == -1)) && $config->fields[$field]) {

         foreach (getAllDatasFromTable('glpi_entities') as $entity) {
            $entities[$entity['id']] = $config->fields[$field];
         }
      }
    }

    /**
     * Cron action
     *
     * @param $task for log, if NULL display
     *
     **/
    static function cronAdditionalalertsInk($task=NULL) {
        global $DB,$CFG_GLPI;

        if (!$CFG_GLPI["use_mailing"] || !TableExists("glpi_plugin_fusioninventory_printercartridges")) {
            return 0;
        }

        $CronTask=new CronTask();
        if ($CronTask->getFromDBbyName("PluginAdditionalalertsInkAlert","AdditionalalertsInk")) {
            if ($CronTask->fields["state"]==CronTask::STATE_DISABLE) {
                return 0;
            }
        } else {
            return 0;
        }

        $message=array();
        $cron_status = 0;
      
        foreach (PluginAdditionalalertsInkAlert::getEntitiesToNotify('use_ink_alert') as $entity => $repeat) {
            $query_ink = PluginAdditionalalertsInkAlert::query($entity);

            $ink_infos = array();
            $ink_messages = array();
         
            $type = Alert::END;
            $ink_infos[$type] = array();
            foreach ($DB->request($query_ink) as $data) {
                $entity = $data['entities_id'];
                $message = $data["name"];
                $ink_infos[$type][$entity][] = $data;

                if (!isset($ink_messages[$type][$entity])) {
                    $ink_messages[$type][$entity] = __('Cartridges whose level is low', 'additionalalerts')."<br/>";
                }
                $ink_messages[$type][$entity] .= $message."</br>";
            }
         
            foreach ($ink_infos[$type] as $entity => $ink) {
                Plugin::loadLang('additionalalerts');
            
                if (NotificationEvent::raiseEvent("ink",
                                                  new PluginAdditionalalertsInkAlert(),
                                                  array('entities_id'=>$entity,
                                                        'ink'=>$ink))) {
                    $message = $ink_messages[$type][$entity];
                    $cron_status = 1;
                    if ($task) {
                        $task->log(Dropdown::getDropdownName("glpi_entities",
                                                             $entity).":  $message\n");
                        $task->addVolume(1);
                    } else {
                        addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                          $entity).":  $message");
                    }

                } else {
                    if ($task) {
                        $task->log(Dropdown::getDropdownName("glpi_entities",$entity).
                                   ":  Send ink alert failed\n");
                    } else {
                        addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",$entity).
                                                ":  Send ink alert failed",false,ERROR);
                    }
                }
            }
        }
      
        return $cron_status;
    }
     
    static function configCron($target,$ID) {

        echo "<div align='center'>";
        echo "<form method='post' action=\"$target\">";
        echo "<table class='tab_cadre_fixe' cellpadding='5'>";
        $colspan=2;
        echo "<tr class='tab_bg_1'>";
        echo "<td>".__('Parameter')."</td>";
        echo "<td>".__('Statutes used for the ink level', 'additionalalerts')." : ";
        Dropdown::show('State', array('name' => "states_id"));
        echo "&nbsp;<input type='submit' name='add_state' value=\"".__('Update')."\" class='submit' ></div></td>";
        echo "</tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>";
         
        $state = new PluginAdditionalalertsInkPrinterState();
        $state->showForm($target);

    }
   
    function getFromDBbyEntity($entities_id) {
        global $DB;

        $query = "SELECT *
                  FROM `".$this->getTable()."`
                  WHERE `entities_id` = '$entities_id'";

        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) != 1) {
                return false;
            }
            $this->fields = $DB->fetch_assoc($result);
            if (is_array($this->fields) && count($this->fields)) {
                return true;
            }
            return false;
        }
        return false;
    }
   
    static function showNotificationOptions(Entity $entity) {
        global $DB, $CFG_GLPI;

        $con_spotted = false;

        $ID = $entity->getField('id');
        if (!$entity->can($ID,READ)) {
            return false;
        }

        // Notification right applied
        $canedit = Session::haveRight('notification', UPDATE) && Session::haveAccessToEntity($ID);

        // Get data
        $entitynotification=new PluginAdditionalalertsInkAlert();
        if (!$entitynotification->getFromDBbyEntity($ID)) {
            $entitynotification->getEmpty();
        }

        if ($canedit) {
            echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
        }
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr class='tab_bg_1'><td>" . __('Cartridges whose level is low', 'additionalalerts') . "</td><td>";
        if (TableExists("glpi_plugin_fusioninventory_printercartridges")) {
            $default_value = $entitynotification->fields['use_ink_alert'];
            Alert::dropdownYesNo(array('name'           => "use_ink_alert",
                                       'value'          => $default_value,
                                       'inherit_global' => 1));
        } else {
            echo "<div align='center'><b>".__('Fusioninventory plugin is not installed', 'additionalalerts')."</b></div>";
        }
        echo "</td></tr>";

        if ($canedit) {
            echo "<tr>";
            echo "<td class='tab_bg_2 center' colspan='4'>";
            echo "<input type='hidden' name='entities_id' value='$ID'>";
            if ($entitynotification->fields["id"]) {
                echo "<input type='hidden' name='id' value=\"".$entitynotification->fields["id"]."\">";
                echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit' >";
            } else {
                echo "<input type='submit' name='add' value=\""._sx('button','Save')."\" class='submit' >";
            }
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
        } else {
            echo "</table>";
        }
    }
}

?>