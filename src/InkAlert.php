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

namespace GlpiPlugin\Additionalalerts;

use Alert;
use CartridgeItem;
use CommonDBTM;
use CommonGLPI;
use CronTask;
use DBmysql;
use DbUtils;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Dropdown;
use Entity;
use Html;
use NotificationEvent;
use Plugin;
use Printer;
use Printer_CartridgeInfo;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class InkAlert
 */
class InkAlert extends CommonDBTM
{

    static $rightname = "plugin_additionalalerts";

   /**
    * @param int $nb
    *
    * @return string|translated
    */
    static function getTypeName($nb = 0)
    {

        return __('Cartridges whose level is low', 'additionalalerts');
    }

    static function getIcon()
    {
        return "ti ti-bell-ringing";
    }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == 'CronTask' && $item->getField('name') == "AdditionalalertsInk") {
            return self::createTabEntry(__('Plugin setup', 'additionalalerts'));
        } elseif (get_class($item) == 'Printer') {
            return self::createTabEntry(AdditionalAlert::getTypeName(2));
        }
        return '';
    }

   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'CronTask') {
            $notif = new InkPrinterState();
            $notif->configState();
        } elseif ($item->getType() == 'Printer') {
            $InkThreshold = new InkThreshold();
            $InkThreshold->showSetupForm(PLUGIN_ADDITIONALALERTS_WEBDIR . "/front/inkalert.form.php", $item->getField('id'));
        }
        return true;
    }

   // Cron action
   /**
    * @param $name
    *
    * @return array
    */
    static function cronInfo($name)
    {

        switch ($name) {
            case 'AdditionalalertsInk':
                return [
               'description' => __('Cartridges whose level is low', 'additionalalerts')];   // Optional
            break;
        }
        return [];
    }

   /**
    * @param $entities
    *
    * @return array
    */
    static function query($entities)
    {
        $entities = array_map('intval', (array) $entities);

        // Each printer is compared to its own threshold, or to the default one when none is set
        $threshold = new QueryExpression(
            'COALESCE(' . DBmysql::quoteName('glpi_plugin_additionalalerts_inkthresholds.threshold')
            . ', ' . InkThreshold::DEFAULT_THRESHOLD . ')'
        );

        return [
            'SELECT'    => [
                'glpi_printers_cartridgeinfos.id',
                'glpi_printers_cartridgeinfos.property',
                'glpi_printers.entities_id',
                'glpi_printers.name',
            ],
            'FROM'      => 'glpi_printers_cartridgeinfos',
            'INNER JOIN' => [
                'glpi_printers' => [
                    'ON' => [
                        'glpi_printers_cartridgeinfos' => 'printers_id',
                        'glpi_printers'                => 'id',
                    ],
                ],
            ],
            'LEFT JOIN' => [
                'glpi_plugin_additionalalerts_inkthresholds' => [
                    'ON' => [
                        'glpi_plugin_additionalalerts_inkthresholds' => 'printers_id',
                        'glpi_printers'                              => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_printers_cartridgeinfos.property' => ['LIKE', 'toner%'],
                'glpi_printers_cartridgeinfos.value'    => ['<=', $threshold],
                'glpi_printers.entities_id'             => $entities,
                'glpi_printers.states_id'               => new QuerySubQuery([
                    'SELECT' => 'states_id',
                    'FROM'   => 'glpi_plugin_additionalalerts_inkprinterstates',
                ]),
            ],
            'ORDERBY'   => 'glpi_printers.name',
        ];
    }


   /**
    * @param $data
    *
    * @return string
    */
    static function displayBody($data)
    {
        global $CFG_GLPI;

        $snmp = new Printer_CartridgeInfo();
        $snmp->getFromDB($data["id"]);

        $printer = new Printer();
        $printer->getFromDB($snmp->fields["printers_id"]);

        $body = "<tr class='tab_bg_2'><td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/printer.form.php?id=" . (int) $printer->fields["id"] . "\">"
            . htmlescape($printer->fields["name"]);

        if ($_SESSION["glpiis_ids_visible"] == 1 || empty($printer->fields["name"])) {
            $body .= " (";
            $body .= (int) $printer->fields["id"] . ")";
        }
        $body .= "</a></td>";
        if (Session::isMultiEntitiesMode()) {
            $body .= "<td class='center'>" . htmlescape(Dropdown::getDropdownName("glpi_entities", $printer->fields["entities_id"])) . "</td>";
        }

        $color_translated = "";

        $color_translations = [
            'black'         => __('Black'),
            'cyan'          => __('Cyan'),
            'magenta'       => __('Magenta'),
            'yellow'        => __('Yellow'),
        ];
        if (isset($snmp->fields['property'], $snmp->fields['value'])
            && str_starts_with($snmp->fields['property'], 'toner')) {
            $color = str_replace('toner', '', $snmp->fields['property']);
            $color_translated = $color_translations[$color] ?? ucwords($color);
        }
        $body .= "<td class='center'>" . __s('Toner') . " " . htmlescape($color_translated) . "</td>";

        $body .= "<td class='center'>" . htmlescape($snmp->fields["value"]) . "%</td>";
        $body .= "</tr>";

        return $body;
    }


    /**
     * Entities where the alert is enabled, with the value of the setting
     *
     * @param string $field
     *
     * @return array entities_id => value
     */
    public static function getEntitiesToNotify($field)
    {
        return AdditionalAlert::getEntitiesToNotify(self::getTable(), $field);
    }

   /**
    * Cron action
    *
    * @param $task for log, if NULL display
    *
    *
    * @return int
    */
    static function cronAdditionalalertsInk($task = null)
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["notifications_mailing"]) {
            return 0;
        }

        // The global option is only the default of the entities: getEntitiesToNotify() applies it
        $CronTask = new CronTask();
        if (!$CronTask->getFromDBbyName(InkAlert::class, "AdditionalalertsInk")
            || $CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
        }

        $message     = [];
        $cron_status = 0;

        foreach (InkAlert::getEntitiesToNotify('use_ink_alert') as $entity => $repeat) {
            $query_ink = InkAlert::query($entity);

            $ink_infos    = [];
            $ink_messages = [];

            $type             = Alert::END;
            $ink_infos[$type] = [];
            foreach ($DB->request($query_ink) as $data) {
                $entity                      = $data['entities_id'];
                $ink_infos[$type][$entity][] = $data;

                // Plain text: the message only goes to the log of the task
                if (!isset($ink_messages[$type][$entity])) {
                    $ink_messages[$type][$entity] = __('Cartridges whose level is low', 'additionalalerts') . " :";
                }
                $ink_messages[$type][$entity] .= " " . $data["name"];
            }

            foreach ($ink_infos[$type] as $entity => $ink) {
                Plugin::loadLang('additionalalerts');

                if (NotificationEvent::raiseEvent(
                    "ink",
                    new InkAlert(),
                    ['entities_id' => $entity,
                    'ink'         => $ink]
                )) {
                    $message     = $ink_messages[$type][$entity];
                    $cron_status = 1;
                    // Messages go to the task log only, never to the HTML of a session
                    if ($task) {
                        $task->log(Dropdown::getDropdownName(
                            "glpi_entities",
                            $entity
                        ) . ":  $message\n");
                        $task->addVolume(1);
                    }
                } elseif ($task) {
                    $task->log(Dropdown::getDropdownName("glpi_entities", $entity) .
                         ":  Send ink alert failed\n");
                }
            }
        }

        return $cron_status;
    }


   /**
    * @param Entity $entity
    *
    * @return bool
    */
    static function showNotificationOptions(Entity $entity)
    {

        $ID = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
            return false;
        }

       // Notification right applied
        // Same conditions as the front controller (AdditionalAlert::checkEntitySettingWrite())
        $canedit = self::canUpdate() && Session::haveRight('notification', UPDATE) && Session::haveAccessToEntity($ID);

       // Get data
        $entitynotification = new InkAlert();
        if (!$entitynotification->getFromDBByCrit(['entities_id' => $ID])) {
            $entitynotification->getEmpty();
        }

        if ($canedit) {
            echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        }
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr class='tab_bg_1'><td>" . __('Cartridges whose level is low', 'additionalalerts') . "</td><td>";
        // -1: the entity uses the global configuration
        Dropdown::showFromArray('use_ink_alert', AdditionalAlert::getYesNoChoices(), [
            'value' => $entitynotification->fields['use_ink_alert'] ?? AdditionalAlert::INHERIT_GLOBAL,
        ]);
        echo "</td></tr>";

        if ($canedit) {
            echo "<tr>";
            echo "<td class='tab_bg_2 center' colspan='4'>";
            echo Html::hidden('entities_id', ['value' => $ID]);
            if ($entitynotification->fields["id"]) {
                echo Html::hidden('id', ['value' => $entitynotification->fields["id"]]);
                echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            } else {
                echo Html::submit(_sx('button', 'Save'), ['name' => 'add', 'class' => 'btn btn-primary']);
            }
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
        } else {
            echo "</table>";
        }
    }
}
