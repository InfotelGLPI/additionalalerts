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
use Printer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class InkThreshold
 */
class InkThreshold extends CommonDBTM
{
    public const DEFAULT_THRESHOLD = 10;

    public static $rightname = "plugin_additionalalerts";

   /**
    * Save the threshold of a printer, creating its row when it does not exist yet
    *
    * @param int $printers_id
    * @param int $value
    *
    * @return bool
    */
    public static function saveForPrinter($printers_id, $value)
    {
        $threshold = new self();
        $value     = max(0, min(100, (int) $value));
        if ($threshold->getFromDBByCrit(['printers_id' => (int) $printers_id])) {
            return $threshold->update(['id' => $threshold->getID(), 'threshold' => $value]);
        }
        return (bool) $threshold->add(['printers_id' => (int) $printers_id, 'threshold' => $value]);
    }

   /**
    * @param $target
    * @param $id
    */
    function showSetupForm($target, $id)
    {
        $printer = new Printer();
        $canedit = self::canUpdate() && $printer->can($id, UPDATE);

        // Nothing is written while displaying: the row is created on the first save
        $threshold = new InkThreshold();
        $value     = self::DEFAULT_THRESHOLD;
        if ($threshold->getFromDBByCrit(["printers_id" => $id])) {
            $value = $threshold->fields["threshold"];
        }

        if ($canedit) {
            echo "<form action='" . htmlescape($target) . "' method='post'>";
        }
        echo "<table class='tab_cadre' cellpadding='5' width='950'>";
        echo "<tr><th colspan='2'>" . __s('Ink level alerts', 'additionalalerts') . "</th></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __s('Ink level alerts', 'additionalalerts') . "</td>";
        echo "<td>";
        if ($canedit) {
            echo Html::input('threshold', ['value' => $value, 'size' => 3]);
        } else {
            echo htmlescape($value);
        }
        echo " %";
        echo "</td>";
        echo "</tr>";

        if ($canedit) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='2' class='center'>";
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update_threshold', 'class' => 'btn btn-primary']);
            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";
        if ($canedit) {
            echo Html::hidden('printers_id', ['value' => (int) $id]);
            Html::closeForm();
        }
    }
}
