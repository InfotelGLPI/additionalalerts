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

use GlpiPlugin\Additionalalerts\InfocomAlert;
use GlpiPlugin\Additionalalerts\InkAlert;
use GlpiPlugin\Additionalalerts\Menu;
use GlpiPlugin\Additionalalerts\Profile;
use GlpiPlugin\Additionalalerts\TicketUnresolved;

/**
 * @return bool
 */
function plugin_additionalalerts_install()
{
    global $DB;

    $update78 = false;

    //INSTALL
    if (!$DB->tableExists("glpi_plugin_additionalalerts_ticketunresolveds")
        && !$DB->tableExists("glpi_plugin_additionalalerts_configs")) {
        $install = true;
        $DB->runFile(PLUGIN_ADDITIONALALERTS_DIR . "/sql/empty-3.0.0.sql");
        install_notifications_additionalalerts();
    }

    if ($DB->tableExists("glpi_plugin_alerting_profiles")
        && $DB->fieldExists("glpi_plugin_alerting_profiles", "interface")) {
        $update78 = true;
        $DB->runFile(PLUGIN_ADDITIONALALERTS_DIR . "/sql/update-1.2.0.sql");
        $DB->runFile(PLUGIN_ADDITIONALALERTS_DIR . "/sql/update-1.3.0.sql");
    }

    if (!$DB->tableExists("glpi_plugin_additionalalerts_infocomalerts")) {
        $update78 = true;
        $DB->runFile(PLUGIN_ADDITIONALALERTS_DIR . "/sql/update-1.3.0.sql");
    }

    if (!$DB->tableExists("glpi_plugin_additionalalerts_inkalerts")) {
        $DB->runFile(PLUGIN_ADDITIONALALERTS_DIR . "/sql/update-1.7.1.sql");
    }

    //version 1.8.0
    if (!$DB->tableExists("glpi_plugin_additionalalerts_ticketunresolveds")) {
        $update90 = true;
        $DB->runFile(PLUGIN_ADDITIONALALERTS_DIR . "/sql/update-1.8.0.sql");
    }

    // version 3.0.0
    if ($DB->tableExists("glpi_plugin_additionalalerts_inkthresholds")
        && $DB->fieldExists("glpi_plugin_additionalalerts_inkthresholds", "cartridges_id")) {
        $DB->runFile(PLUGIN_ADDITIONALALERTS_DIR . "/sql/update-3.0.0.sql");
    }

    if ($update78) {
        //Do One time on 0.78
        $iterator = $DB->request([
            'SELECT' => [
                'id',
            ],
            'FROM' => 'glpi_plugin_additionalalerts_profiles',
        ]);
        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $query = "UPDATE `glpi_plugin_additionalalerts_profiles`
                  SET `profiles_id` = '" . $data["id"] . "'
                  WHERE `id` = '" . $data["id"] . "';";
                $DB->doQuery($query);
            }
        }

        $query = "ALTER TABLE `glpi_plugin_additionalalerts_profiles`
               DROP `name` ;";
        $DB->doQuery($query);
    }

    // To be called for each task the plugin manage
    CronTask::Register(InfocomAlert::class, 'AdditionalalertsNotInfocom', HOUR_TIMESTAMP);
    CronTask::Register(InkAlert::class, 'AdditionalalertsInk', DAY_TIMESTAMP);
    CronTask::Register(TicketUnresolved::class, 'AdditionalalertsTicketUnresolved', DAY_TIMESTAMP);

    Profile::initProfile();
    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

    return true;
}

/**
 * @return bool
 */
function plugin_additionalalerts_uninstall()
{
    global $DB;

    $tables = [
        "glpi_plugin_additionalalerts_infocomalerts",
        "glpi_plugin_additionalalerts_inkalerts",
        "glpi_plugin_additionalalerts_notificationtypes",
        "glpi_plugin_additionalalerts_configs",
        "glpi_plugin_additionalalerts_inkthresholds",
        "glpi_plugin_additionalalerts_inkprinterstates",
        "glpi_plugin_additionalalerts_ticketunresolveds"
    ];

    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }

    //old versions
    $tables = [
        "glpi_plugin_additionalalerts_reminderalerts",
        "glpi_plugin_alerting_config",
        "glpi_plugin_alerting_state",
        "glpi_plugin_alerting_profiles",
        "glpi_plugin_alerting_mailing",
        "glpi_plugin_alerting_type",
        "glpi_plugin_additionalalerts_profiles",
        "glpi_plugin_alerting_cartridges",
        "glpi_plugin_alerting_cartridges_printer_state",
        "glpi_plugin_additionalalerts_profiles",
        "glpi_plugin_additionalalerts_ocsalerts",
        "glpi_plugin_additionalalerts_notificationstates"
    ];

    foreach ($tables as $table) {
        $DB->dropTable($table, true);
    }


    $notif = new Notification();
    $options = ['itemtype' => InkAlert::class];
    foreach (
        $DB->request([
            'FROM' => 'glpi_notifications',
            'WHERE' => $options
        ]) as $data
    ) {
        $notif->delete($data);
    }

    //templates
    $template = new NotificationTemplate();
    $translation = new NotificationTemplateTranslation();
    $notif_template = new Notification_NotificationTemplate();
    $options = ['itemtype' => InkAlert::class];
    foreach (
        $DB->request([
            'FROM' => 'glpi_notificationtemplates',
            'WHERE' => $options
        ]) as $data
    ) {
        $options_template = [
            'notificationtemplates_id' => $data['id'],
        ];

        foreach (
            $DB->request([
                'FROM' => 'glpi_notificationtemplatetranslations',
                'WHERE' => $options_template
            ]) as $data_template
        ) {
            $translation->delete($data_template);
        }
        $template->delete($data);

        foreach (
            $DB->request([
                'FROM' => 'glpi_notifications_notificationtemplates',
                'WHERE' => $options_template
            ]) as $data_template
        ) {
            $notif_template->delete($data_template);
        }
    }

    $notif = new Notification();
    $options = ['itemtype' => InfocomAlert::class];
    foreach (
        $DB->request([
            'FROM' => 'glpi_notifications',
            'WHERE' => $options
        ]) as $data
    ) {
        $notif->delete($data);
    }

    //templates
    $template = new NotificationTemplate();
    $translation = new NotificationTemplateTranslation();
    $notif_template = new Notification_NotificationTemplate();
    $options = ['itemtype' => InfocomAlert::class];
    foreach (
        $DB->request([
            'FROM' => 'glpi_notificationtemplates',
            'WHERE' => $options
        ]) as $data
    ) {
        $options_template = [
            'notificationtemplates_id' => $data['id'],
        ];

        foreach (
            $DB->request([
                'FROM' => 'glpi_notificationtemplatetranslations',
                'WHERE' => $options_template
            ]) as $data_template
        ) {
            $translation->delete($data_template);
        }
        $template->delete($data);

        foreach (
            $DB->request([
                'FROM' => 'glpi_notifications_notificationtemplates',
                'WHERE' => $options_template
            ]) as $data_template
        ) {
            $notif_template->delete($data_template);
        }
    }

    $notif = new Notification();
    $options = ['itemtype' => TicketUnresolved::class];
    foreach (
        $DB->request([
            'FROM' => 'glpi_notifications',
            'WHERE' => $options
        ]) as $data
    ) {
        $notif->delete($data);
    }

    //templates
    $template = new NotificationTemplate();
    $translation = new NotificationTemplateTranslation();
    $notif_template = new Notification_NotificationTemplate();
    $options = ['itemtype' => TicketUnresolved::class];
    foreach (
        $DB->request([
            'FROM' => 'glpi_notificationtemplates',
            'WHERE' => $options
        ]) as $data
    ) {
        $options_template = [
            'notificationtemplates_id' => $data['id'],
        ];

        foreach (
            $DB->request([
                'FROM' => 'glpi_notificationtemplatetranslations',
                'WHERE' => $options_template
            ]) as $data_template
        ) {
            $translation->delete($data_template);
        }
        $template->delete($data);

        foreach (
            $DB->request([
                'FROM' => 'glpi_notifications_notificationtemplates',
                'WHERE' => $options_template
            ]) as $data_template
        ) {
            $notif_template->delete($data_template);
        }
    }

    //Delete rights associated with the plugin
    $profileRight = new ProfileRight();
    foreach (Profile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }
    Profile::removeRightsFromSession();

    Menu::removeRightsFromSession();

    CronTask::Unregister('additionalalerts');

    return true;
}

// Define database relations
/**
 * @return array
 */
function plugin_additionalalerts_getDatabaseRelations()
{
    $links = [];
    if (Plugin::isPluginActive("additionalalerts")) {
        $links = [
//                     "glpi_states" => [
//                        "glpi_plugin_additionalalerts_notificationstates" => "states_id"
//                     ],
//                     "glpi_computertypes" => [
//                        "glpi_plugin_additionalalerts_notificationtypes" => "types_id"
//                     ],
//                    "glpi_printers" => [
//                        "glpi_plugin_additionalalerts_inkthresholds" => "printers_id"]
        ];
    }


    return $links;
}

function install_notifications_additionalalerts()
{

    global $DB;

    $migration = new Migration(1.0);

    // Notification
    // Request
    $options_notif        = ['itemtype' => InkAlert::class,
        'name' => 'Alert ink level'];
    $DB->insert(
        "glpi_notificationtemplates",
        $options_notif
    );

    foreach ($DB->request([
        'FROM' => 'glpi_notificationtemplates',
        'WHERE' => $options_notif]) as $data) {
        $templates_id = $data['id'];

        if ($templates_id) {
            $DB->insert(
                "glpi_notificationtemplatetranslations",
                [
                    'notificationtemplates_id' => $templates_id,
                    'subject' => '##lang.ink.title## : ##ink.entity##',
                    'content_text' => '##lang.ink.title## :
          ##FOREACHinks##
          - ##ink.printer## - ##ink.cartridge## - ##ink.state##%
          ##ENDFOREACHinks##',
                    'content_html' => '&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
          &lt;tbody&gt;
          &lt;tr&gt;
          &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.printer##&lt;/span&gt;&lt;/td&gt;
          &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.cartridge##&lt;/span&gt;&lt;/td&gt;
          &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ink.state##&lt;/span&gt;&lt;/td&gt;
          &lt;/tr&gt;
          ##FOREACHinks##
          &lt;tr&gt;
          &lt;td&gt;&lt;a href=\"##ink.urlprinter##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.printer##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
          &lt;td&gt;&lt;a href=\"##ink.urlcartridge##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.cartridge##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
          &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ink.state##%&lt;/span&gt;&lt;/td&gt;
          &lt;/tr&gt;
          ##ENDFOREACHinks##
          &lt;/tbody&gt;
          &lt;/table&gt;',
                ]
            );

            $DB->insert(
                "glpi_notifications",
                [
                    'name' => 'Alert ink level',
                    'entities_id' => 0,
                    'itemtype' => InkAlert::class,
                    'event' => 'ink',
                    'is_recursive' => 1,
                ]
            );

            $options_notif        = ['itemtype' => InkAlert::class,
                'name' => 'Alert ink level',
                'event' => 'ink'];

            foreach ($DB->request([
                'FROM' => 'glpi_notifications',
                'WHERE' => $options_notif]) as $data_notif) {
                $notification = $data_notif['id'];
                if ($notification) {
                    $DB->insert(
                        "glpi_notifications_notificationtemplates",
                        [
                            'notifications_id' => $notification,
                            'mode' => 'mailing',
                            'notificationtemplates_id' => $templates_id,
                        ]
                    );
                }
            }
        }
    }

    // Alert infocoms
    $options_notif        = ['itemtype' => InfocomAlert::class,
        'name' => 'Alert infocoms'];

    $DB->insert(
        "glpi_notificationtemplates",
        $options_notif
    );

    foreach ($DB->request([
        'FROM' => 'glpi_notificationtemplates',
        'WHERE' => $options_notif]) as $data) {
        $templates_id = $data['id'];

        if ($templates_id) {
            $DB->insert(
                "glpi_notificationtemplatetranslations",
                [
                    'notificationtemplates_id' => $templates_id,
                    'subject' => '##lang.notinfocom.title## : ##notinfocom.entity##',
                    'content_text' => '##FOREACHnotinfocoms##
       ##lang.notinfocom.name## : ##notinfocom.name##
       ##lang.notinfocom.computertype## : ##notinfocom.computertype##
       ##lang.notinfocom.operatingsystem## : ##notinfocom.operatingsystem##
       ##lang.notinfocom.state## : ##notinfocom.state##
       ##lang.notinfocom.location## : ##notinfocom.location##
       ##lang.notinfocom.user## : ##notinfocom.user## / ##notinfocom.group## / ##notinfocom.contact##
       ##ENDFOREACHnotinfocoms##',
                    'content_html' => '&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
       &lt;tbody&gt;
       &lt;tr&gt;
       &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.name##&lt;/span&gt;&lt;/td&gt;
       &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.computertype##&lt;/span&gt;&lt;/td&gt;
       &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.operatingsystem##&lt;/span&gt;&lt;/td&gt;
       &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.state##&lt;/span&gt;&lt;/td&gt;
       &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.location##&lt;/span&gt;&lt;/td&gt;
       &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.notinfocom.user##&lt;/span&gt;&lt;/td&gt;
       &lt;/tr&gt;
       ##FOREACHnotinfocoms##
       &lt;tr&gt;
       &lt;td&gt;&lt;a href=\"##notinfocom.urlname##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.name##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
       &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.computertype##&lt;/span&gt;&lt;/td&gt;
       &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.operatingsystem##&lt;/span&gt;&lt;/td&gt;
       &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.state##&lt;/span&gt;&lt;/td&gt;
       &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.location##&lt;/span&gt;&lt;/td&gt;
       &lt;td&gt;&lt;a href=\"##notinfocom.urluser##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.user##&lt;/span&gt;&lt;/a&gt; / &lt;a href=\"##notinfocom.urlgroup##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.group##&lt;/span&gt;&lt;/a&gt; / &lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##notinfocom.contact##&lt;/span&gt;&lt;/td&gt;
       &lt;/tr&gt;
       ##ENDFOREACHnotinfocoms##
       &lt;/tbody&gt;
       &lt;/table&gt;',
                ]
            );

            $DB->insert(
                "glpi_notifications",
                [
                    'name' => 'Alert infocoms',
                    'entities_id' => 0,
                    'itemtype' => InfocomAlert::class,
                    'event' => 'notinfocom',
                    'is_recursive' => 1,
                ]
            );

            $options_notif = [
                'itemtype' => InfocomAlert::class,
                'name' => 'Alert infocoms',
                'event' => 'notinfocom'
            ];

            foreach (
                $DB->request([
                    'FROM' => 'glpi_notifications',
                    'WHERE' => $options_notif
                ]) as $data_notif
            ) {
                $notification = $data_notif['id'];
                if ($notification) {
                    $DB->insert(
                        "glpi_notifications_notificationtemplates",
                        [
                            'notifications_id' => $notification,
                            'mode' => 'mailing',
                            'notificationtemplates_id' => $templates_id,
                        ]
                    );
                }
            }
        }

        // Alert Ticket Unresolved
        $options_notif = [
            'itemtype' => TicketUnresolved::class,
            'name' => 'Alert Ticket Unresolved'
        ];

        $DB->insert(
            "glpi_notificationtemplates",
            $options_notif
        );

        foreach (
            $DB->request([
                'FROM' => 'glpi_notificationtemplates',
                'WHERE' => $options_notif
            ]) as $data
        ) {
            $templates_id = $data['id'];

            if ($templates_id) {
                $DB->insert(
                    "glpi_notificationtemplatetranslations",
                    [
                        'notificationtemplates_id' => $templates_id,
                        'subject' => '##ticket.action## ##ticket.entity##',
                        'content_text' => '##ticket.action## ##ticket.entity##
         ##FOREACHtickets##

          ##lang.ticket.title## : ##ticket.title##
           ##lang.ticket.status## : ##ticket.status##

           ##ticket.url##
           ##ENDFOREACHtickets##',
                        'content_html' => '&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
    &lt;tbody&gt;
    &lt;tr&gt;
    &lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.authors##&lt;/span&gt;&lt;/td&gt;
    &lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.title##&lt;/span&gt;&lt;/td&gt;
    &lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.priority##&lt;/span&gt;&lt;/td&gt;
    &lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.status##&lt;/span&gt;&lt;/td&gt;
    &lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.attribution##&lt;/span&gt;&lt;/td&gt;
    &lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
    &lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#95bde4\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ticket.content##&lt;/span&gt;&lt;/td&gt;
    &lt;/tr&gt;
    ##FOREACHtickets##
    &lt;tr&gt;
    &lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.authors##&lt;/span&gt;&lt;/td&gt;
    &lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;&lt;a href=\"##ticket.url##\"&gt;##ticket.title##&lt;/a&gt;&lt;/span&gt;&lt;/td&gt;
    &lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.priority##&lt;/span&gt;&lt;/td&gt;
    &lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.status##&lt;/span&gt;&lt;/td&gt;
    &lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##IFticket.assigntousers####ticket.assigntousers##&lt;br /&gt;##ENDIFticket.assigntousers####IFticket.assigntogroups##&lt;br /&gt;##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##&lt;br /&gt;##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##&lt;/span&gt;&lt;/td&gt;
    &lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
    &lt;td width=\"auto\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ticket.content##&lt;/span&gt;&lt;/td&gt;
    &lt;/tr&gt;
    ##ENDFOREACHtickets##
    &lt;/tbody&gt;
    &lt;/table&gt;',
                    ]
                );

                $DB->insert(
                    "glpi_notifications",
                    [
                        'name' => 'Alert Ticket Unresolved',
                        'entities_id' => 0,
                        'itemtype' => TicketUnresolved::class,
                        'event' => 'ticketunresolved',
                        'is_recursive' => 1,
                    ]
                );

                $options_notif = [
                    'itemtype' => TicketUnresolved::class,
                    'name' => 'Alert Ticket Unresolved',
                    'event' => 'ticketunresolved'
                ];

                foreach (
                    $DB->request([
                        'FROM' => 'glpi_notifications',
                        'WHERE' => $options_notif
                    ]) as $data_notif
                ) {
                    $notification = $data_notif['id'];
                    if ($notification) {
                        $DB->insert(
                            "glpi_notifications_notificationtemplates",
                            [
                                'notifications_id' => $notification,
                                'mode' => 'mailing',
                                'notificationtemplates_id' => $templates_id,
                            ]
                        );
                    }
                }
            }
        }

        $migration->executeMigration();
        return true;
    }
}

