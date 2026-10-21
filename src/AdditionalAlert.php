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

use CommonDBTM;
use Computer;
use CronTask;
use Dropdown;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Plugin;
use Printer;
use Session;
use Ticket;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class AdditionalAlert
 */
class AdditionalAlert extends CommonDBTM
{

    static $rightname = "plugin_additionalalerts";

   /**
    * @param int $nb
    *
    * @return translated
    */
    static function getTypeName($nb = 0)
    {

        return _n('Other alert', 'Others alerts', $nb, 'additionalalerts');
    }

    /**
     * Check that the current user may write the per-entity setting posted to a front controller,
     * with the same conditions as the Entity tab form (plugin and notification UPDATE, entity access)
     *
     * @param CommonDBTM $item  InfocomAlert, InkAlert or TicketUnresolved
     * @param array      $input posted values
     *
     * @return array the input restricted to the target entity
     */
    public static function checkEntitySettingWrite(CommonDBTM $item, array $input)
    {
        if (!$item::canUpdate() || !Session::haveRight('notification', UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        if (isset($input['id']) && (int) $input['id'] > 0) {
            // Update: the entity is the one of the stored row, not the posted one
            if (!$item->getFromDB((int) $input['id'])) {
                throw new BadRequestHttpException();
            }
            $entities_id = (int) $item->fields['entities_id'];
        } else {
            if (!isset($input['entities_id'])) {
                throw new BadRequestHttpException();
            }
            $entities_id = (int) $input['entities_id'];
        }

        if (!Session::haveAccessToEntity($entities_id)) {
            throw new AccessDeniedHttpException();
        }
        $input['entities_id'] = $entities_id;

        return $input;
    }

    /**
     * Delete the rows checked in an exclusion list (NotificationType, InkPrinterState)
     *
     * @param CommonDBTM $item
     * @param array      $input posted values
     */
    public static function deleteCheckedItems(CommonDBTM $item, array $input)
    {
        if (!isset($input['item']) || !is_array($input['item'])) {
            return;
        }
        foreach ($input['item'] as $key => $val) {
            if ($val == 1) {
                $item->delete(['id' => (int) $key]);
            }
        }
    }

    static function displayAlerts()
    {
        global $DB;

        $CronTask = new CronTask();

        $config = Config::getConfig();

        $infocom = new InfocomAlert();
        $infocom->getFromDBByCrit(['entities_id' => $_SESSION["glpiactive_entity"]]);
        if (isset($infocom->fields["use_infocom_alert"])
          && $infocom->fields["use_infocom_alert"] > 0) {
            $use_infocom_alert = $infocom->fields["use_infocom_alert"];
        } else {
            $use_infocom_alert = $config->useInfocomAlert();
        }

        $ticketunresolved = new TicketUnresolved();
        $ticketunresolved->getFromDBByCrit(['entities_id' => $_SESSION["glpiactive_entity"]]);
        if (isset($ticketunresolved->fields["delay_ticket_alert"])
          && $ticketunresolved->fields["delay_ticket_alert"] > 0) {
            $delay_ticket_alert = $ticketunresolved->fields["delay_ticket_alert"];
        } else {
            $delay_ticket_alert = $config->getDelayTicketAlert();
        }

        $inkalert = new InkAlert();
        $inkalert->getFromDBByCrit(['entities_id' => $_SESSION["glpiactive_entity"]]);
        if (isset($inkalert->fields["use_ink_alert"])
          && $inkalert->fields["use_ink_alert"] > 0) {
            $use_ink_alert = $inkalert->fields["use_ink_alert"];
        } else {
            $use_ink_alert = $config->useInkAlert();
        }

        $additionalalerts_ink = 0;
        if ($CronTask->getFromDBbyName(InkAlert::class, "AdditionalalertsInk")) {
            if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $use_ink_alert > 0) {
                $additionalalerts_ink = 1;
            }
        }

        $additionalalerts_not_infocom = 0;
        if ($CronTask->getFromDBbyName(InfocomAlert::class, "AdditionalalertsNotInfocom")) {
            if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $use_infocom_alert > 0) {
                $additionalalerts_not_infocom = 1;
            }
        }

        $additionalalerts_ticket_unresolved = 0;
        if ($CronTask->getFromDBbyName(TicketUnresolved::class, "AdditionalalertsTicketUnresolved")) {
            if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $delay_ticket_alert > 0) {
                $additionalalerts_ticket_unresolved = 1;
            }
        }

        if ($additionalalerts_not_infocom == 0
          && $additionalalerts_ink == 0
          && $additionalalerts_ticket_unresolved == 0) {
            echo "<div class='center'><b>" . __('No used alerts', 'additionalalerts') . "</b></div>";
        }
        if ($additionalalerts_not_infocom != 0) {
            if (Session::haveRight("infocom", READ) && Session::haveRight(Computer::$rightname, READ)) {
                $iterator = $DB->request(InfocomAlert::query($_SESSION["glpiactive_entity"]));

                if (count($iterator) > 0) {
                    if (Session::isMultiEntitiesMode()) {
                        $nbcol = 7;
                    } else {
                        $nbcol = 6;
                    }
                    echo "<div class='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'><tr><th colspan='$nbcol'>";
                    echo InfocomAlert::getTypeName(2) . "</th></tr>";
                    echo "<tr><th>" . __('Name') . "</th>";
                    if (Session::isMultiEntitiesMode()) {
                         echo "<th>" . __('Entity') . "</th>";
                    }
                    echo "<th>" . __('Type') . "</th>";
                    echo "<th>" . __('Operating system') . "</th>";
                    echo "<th>" . __('Status') . "</th>";
                    echo "<th>" . __('Location') . "</th>";
                    echo "<th>" . __('User') . " / " . __('Group') . " / " . __('Alternate username') . "</th></tr>";
                    foreach ($iterator as $data) {
                        echo InfocomAlert::displayBody($data);
                    }
                    echo "</table></div>";
                } else {
                    echo "<br><div class='center'><b>" . __('No computers with no buy date', 'additionalalerts') . "</b></div>";
                }
                echo "<br>";
            }
        }

        if ($additionalalerts_ink != 0) {
            if (Session::haveRight("cartridge", READ) && Session::haveRight(Printer::$rightname, READ)) {
                $iterator = $DB->request(InkAlert::query($_SESSION["glpiactive_entity"]));

                if (count($iterator) > 0) {
                    if (Session::isMultiEntitiesMode()) {
                        $nbcol = 4;
                    } else {
                        $nbcol = 3;
                    }
                    echo "<div class='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'>";
                    echo "<tr><th colspan='$nbcol'>" . __('Cartridges whose level is low', 'additionalalerts') . "</th></tr>";
                    echo "<tr>";
                    echo "<th>" . __('Printer') . "</th>";
                    if (Session::isMultiEntitiesMode()) {
                        echo "<th>" . __('Entity') . "</th>";
                    }
                    echo "<th>" . __('Cartridge') . "</th>";
                    echo "<th>" . __('Ink level', 'additionalalerts') . "</th></tr>";

                    foreach ($iterator as $data) {
                        echo InkAlert::displayBody($data);
                    }
                    echo "</table></div>";
                } else {
                    echo "<br><div class='center'><b>" . __('No cartridge is below the threshold', 'additionalalerts') . "</b></div>";
                }
            }
        }

        if ($additionalalerts_ticket_unresolved != 0
            && Session::haveRight(Ticket::$rightname, Ticket::READALL)) {
            $entities = TicketUnresolved::getEntitiesToNotify('delay_ticket_alert');

            foreach ($entities as $entity => $delay_ticket_alert) {
                // The cron sees every entity, the page only the ones of the current user
                if (!in_array($entity, $_SESSION['glpiactiveentities'] ?? [])) {
                    continue;
                }
                $iterator = $DB->request(TicketUnresolved::query($delay_ticket_alert, $entity));
                $nbcol  = 7;


                if (count($iterator) > 0) {
                    echo "<div class='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'><tr><th colspan='$nbcol'>";
                    echo __s('Tickets unresolved since more', 'additionalalerts') . " " . (int) $delay_ticket_alert . " " . _sn('Day', 'Days', 2) . ", " . __s('Entity') . " : " . htmlescape(Dropdown::getDropdownName("glpi_entities", $entity)) . "</th></tr>";
                    echo "<tr><th>" . __('Title') . "</th>";
                    echo "<th>" . __('Entity') . "</th>";
                    echo "<th>" . __('Status') . "</th>";
                    echo "<th>" . __('Opening date') . "</th>";
                    echo "<th>" . __('Last update') . "</th>";
                    echo "<th>" . __('Technician') . "</th>";
                    echo "<th>" . __('Manager') . "</th>";

                    foreach ($iterator as $data) {
                        echo TicketUnresolved::displayBody($data);
                    }


                    echo "</table></div>";
                } else {
                    echo "<br><div class='center'><b>" . __s('No tickets unresolved since more', 'additionalalerts') . " " .
                    (int) $delay_ticket_alert . " " . _sn('Day', 'Days', 2) . ", " . __s('Entity') . " : " . htmlescape(Dropdown::getDropdownName("glpi_entities", $entity)) . "</b></div>";
                }

                echo "<br>";
            }
        }
    }
}
