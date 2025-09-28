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
use Html;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class InkThreshold
 */
class InkThreshold extends CommonDBTM
{
   /**
    * @param $target
    * @param $id
    */
    function showSetupForm($target, $id)
    {

        $threshold = new InkThreshold();
        $inkthresholds = $threshold->find(["printers_id" => $id]);

        if (count($inkthresholds) == 0) {
            $this->add(["printers_id" => $id]);
        }
        $threshold = new InkThreshold();
        $threshold->getFromDBByCrit(["printers_id" => $id]);

        echo "<form action='" . $target . "' method='post'>";
        echo "<table class='tab_cadre' cellpadding='5' width='950'>";
        echo "<tr><th colspan='2'>" . __('Ink level alerts', 'additionalalerts') . "</th></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Ink level alerts', 'additionalalerts') . "</td>";
        echo "<td>";
        echo Html::input('threshold', ['value' => $threshold->fields["threshold"], 'size' => 3]);
        echo " %";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' align='center'>";
        echo Html::submit(_sx('button', 'Save'), ['name' => 'update_threshold', 'class' => 'btn btn-primary']);
        echo "</td/>";
        echo "</tr>";

        echo "</table>";
        echo Html::hidden('id', ['value' => $threshold->fields["id"]]);
        Html::closeForm();
    }
}
