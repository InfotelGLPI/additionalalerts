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
use CommonDBTM;
use CommonITILActor;
use CronTask;
use DbUtils;
use Dropdown;
use Entity;
use Html;
use Notification;
use NotificationEvent;
use Plugin;
use Session;
use Ticket;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class TicketUnresolved
 */
class TicketUnresolved extends CommonDBTM
{

    static $rightname = "plugin_additionalalerts";

    /**
     * @param int $nb
     *
     * @return string
     */
    static function getTypeName($nb = 0)
    {
        return _n('Ticket unresolved', 'Tickets unresolved', $nb, 'additionalalerts');
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
            case 'AdditionalalertsTicketUnresolved':
                return [
                    'description' => TicketUnresolved::getTypeName(2)
                ];   // Optional
                break;
        }
        return [];
    }


    /**
     * @param $delay_ticket_alert
     * @param $entity
     *
     * @return array
     */
    static function query($delay_ticket_alert, $entity)
    {
        $delay_stamp = mktime(0, 0, 0, date("m"), date("d") - (int) $delay_ticket_alert, date("y"));
        $date = date("Y-m-d", $delay_stamp) . " 00:00:00";

        return [
            'SELECT'    => [
                'glpi_tickets.*',
                'glpi_tickets_users.users_id',
                'glpi_groups_users.users_id AS supervisor',
            ],
            'FROM'      => 'glpi_tickets',
            'LEFT JOIN' => [
                'glpi_tickets_users'  => [
                    'ON' => [
                        'glpi_tickets'       => 'id',
                        'glpi_tickets_users' => 'tickets_id',
                        ['AND' => ['glpi_tickets_users.type' => CommonITILActor::ASSIGN]],
                    ],
                ],
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_tickets'        => 'id',
                        'glpi_groups_tickets' => 'tickets_id',
                        ['AND' => ['glpi_groups_tickets.type' => CommonITILActor::ASSIGN]],
                    ],
                ],
                'glpi_groups_users'   => [
                    'ON' => [
                        'glpi_groups_users'   => 'groups_id',
                        'glpi_groups_tickets' => 'groups_id',
                        ['AND' => ['glpi_groups_users.is_manager' => 1]],
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_tickets.date'        => ['<=', $date],
                'glpi_tickets.status'      => [Ticket::INCOMING, Ticket::ASSIGNED, Ticket::PLANNED, Ticket::WAITING],
                'glpi_tickets.is_deleted'  => 0,
                'glpi_tickets.entities_id' => (int) $entity,
            ],
            'ORDERBY'   => 'glpi_tickets.id',
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

        $dbu = new DbUtils();

        $body = "<tr class='tab_bg_2'><td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . (int) $data["id"] . "\">"
            . htmlescape($data["name"]);
        $body .= "</a></td>";

        $body .= "<td class='center'>" . htmlescape(Dropdown::getDropdownName("glpi_entities", $data["entities_id"])) . "</td>";
        $body .= "<td>" . htmlescape(Ticket::getStatus($data["status"])) . "</td>";
        $body .= "<td>" . htmlescape(Html::convDateTime($data["date"])) . "</td>";
        $body .= "<td>" . htmlescape(Html::convDateTime($data["date_mod"])) . "</td>";
        $body .= "<td>";
        if (!empty($data["users_id"])) {
            $body .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/user.form.php?id=" . (int) $data["users_id"] . "\">" .
                htmlescape($dbu->getUserName($data["users_id"])) . "</a>";
        }
        $body .= "</td>";
        $body .= "<td>";
        if (!empty($data["supervisor"])) {
            $body .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/user.form.php?id=" . (int) $data["supervisor"] . "\">" .
                htmlescape($dbu->getUserName($data["supervisor"])) . "</a>";
        }
        if (!empty($data["contact"])) {
            $body .= " - " . htmlescape($data["contact"]);
        }

        $body .= "</td>";
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
     * @return int
     */
    static function cronAdditionalalertsTicketUnresolved($task = null)
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["notifications_mailing"]) {
            return 0;
        }

        $CronTask = new CronTask();
        if ($CronTask->getFromDBbyName(TicketUnresolved::class, "AdditionalalertsTicketUnresolved")) {
            if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
                return 0;
            }
        } else {
            return 0;
        }
        $entities = self::getEntitiesToNotify('delay_ticket_alert');
        $ticket = new TicketUnresolved();

        $cron_status = 0;
        foreach ($entities as $entity => $delay_ticket_alert) {
            $query = self::query($delay_ticket_alert, $entity);

            //Foreach notification
            $notifications = Notification::getNotificationsByEventAndType(
                'ticketunresolved',
                TicketUnresolved::class,
                $entity
            );

            $dbu = new DbUtils();

            foreach ($notifications as $notif) {
                $targets = $dbu->getAllDataFromTable(
                    'glpi_notificationtargets',
                    ["notifications_id" => $notif['id']]
                );

                // Tickets grouped by recipient: the assigned technician and/or the manager of the assigned group
                $list_ticket = [];
                foreach ($DB->request($query) as $tick) {
                    foreach ($targets as $target) {
                        if ($target['type'] != Notification::USER_TYPE) {
                            continue;
                        }
                        if ($target['items_id'] == Notification::ASSIGN_TECH
                            && !empty($tick['users_id'])) {
                            $list_ticket[(int) $tick['users_id']][$tick['id']] = $tick;
                        }
                        if ($target['items_id'] == Notification::SUPERVISOR_ASSIGN_GROUP
                            && !empty($tick['supervisor'])) {
                            $list_ticket[(int) $tick['supervisor']][$tick['id']] = $tick;
                        }
                    }
                }
                foreach ($list_ticket as $recipients_id => $tickets) {
                    Plugin::loadLang('additionalalerts');

                    if (NotificationEvent::raiseEvent(
                        'ticketunresolved',
                        $ticket,
                        [
                            'entities_id'   => $entity,
                            'items'         => $tickets,
                            // The user the list was built for, see NotificationTargetTicketUnresolved
                            'recipients_id' => $recipients_id,
                        ]
                    )) {
                        if ($task) {
                            $task->addVolume(1);
                        }
                        $cron_status = 1;
                    }
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
        $entitynotification = new TicketUnresolved();

        if (!$entitynotification->getFromDBByCrit(['entities_id' => $ID])) {
            $entitynotification->getEmpty();
        }
        if (!isset($entitynotification->fields["delay_ticket_alert"])) {
            $entitynotification->fields["delay_ticket_alert"] = AdditionalAlert::INHERIT_GLOBAL;
        }
        if ($canedit) {
            echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        }
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr class='tab_bg_1'><td>" . TicketUnresolved::getTypeName(2) . "</td><td>";
        // -1: the entity uses the global configuration, 0: never
        Alert::dropdownIntegerNever(
            'delay_ticket_alert',
            $entitynotification->fields["delay_ticket_alert"],
            [
                'max'   => 99,
                // Replaces the choices added by dropdownIntegerNever(): "Never" is given again
                'toadd' => [
                    AdditionalAlert::INHERIT_GLOBAL => __('Global configuration', 'additionalalerts'),
                    0                               => __('Never'),
                ],
            ]
        );

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
