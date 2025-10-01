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

use Dropdown;
use Html;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class InkPrinterState
 */
class InkPrinterState extends NotificationState {
   static $rightname = "plugin_additionalalerts";

    function configState()
    {
        $target = PLUGIN_ADDITIONALALERTS_WEBDIR . "/front/inkalert.form.php";
        $state = new InkPrinterState();
        $states = $state->find();
        $used = [];
        foreach ($states as $data) {
            $used[] = $data['states_id'];
        }

        echo "<div class='center'>";
        echo "<form method='post' action=\"$target\">";
        echo "<table class='tab_cadre_fixe' cellpadding='5'>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Parameter', 'additionalalerts') . "</td>";
        echo "<td>" . __('Statutes used for the ink level', 'additionalalerts') . " : ";
        Dropdown::show('State', ['name' => "states_id",
            'used' => $used]);
        echo "&nbsp;";
        echo Html::submit(_sx('button', 'Update'), ['name' => 'add_state', 'class' => 'btn btn-primary']);
        echo "</div></td>";
        echo "</tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>";

        $rand = mt_rand();

        $data = $this->find([], ["states_id ASC"]);

        if (count($data) != 0) {
            Html::openMassiveActionsForm('mass' . "InkPrinterState" . $rand);
            $massiveactionparams = [
                'item' => __CLASS__,
                'container' => 'mass' . "InkPrinterState" . $rand
            ];
            Html::showMassiveActions($massiveactionparams);

            echo "<div class='center'>";
            echo "<form method='post' name='massiveaction_form$rand' id='massiveaction_form$rand'  action=\"$target\">";
            echo "<table class='tab_cadre_fixe' cellpadding='5'>";
            echo "<tr>";
            echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . "InkPrinterState" . $rand) . "</th>";
            echo "<th>" . _n('State', 'States', 2) . "</th>";
            echo "</tr>";
            foreach ($data as $ligne) {
                echo "<tr class='tab_bg_1'>";
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(__CLASS__, $ligne["id"]);
                echo "</td>";
                echo "<td>" . Dropdown::getDropdownName("glpi_states", $ligne["states_id"]) . "</td>";
                echo "</tr>";
            }

            $massiveactionparams['ontop'] = false;

            echo "</table>";
            Html::closeForm();
            echo "</div>";

            Html::showMassiveActions($massiveactionparams);
        }
    }
}
