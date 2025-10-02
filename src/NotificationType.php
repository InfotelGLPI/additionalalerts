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
use Dropdown;
use Html;
use MassiveAction;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class NotificationType
 */
class NotificationType extends CommonDBTM {

   static $rightname = "plugin_additionalalerts";


    function configType() {

        $target = PLUGIN_ADDITIONALALERTS_WEBDIR . "/front/infocomalert.form.php";
        $type = new NotificationType();
        $types = $type->find();
        $used = [];
        foreach ($types as $data) {
            $used[] = $data['types_id'];
        }

        echo "<div class='center'>";
        echo "<form method='post' action=\"$target\">";
        echo "<table class='tab_cadre_fixe' cellpadding='5'>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Parameter', 'additionalalerts') . "</td>";
        echo "<td>" . __('Type not used for check of buy date', 'additionalalerts');
        Dropdown::show('ComputerType', ['name' => "types_id",
            'used' => $used]);
        echo "&nbsp;";
        echo Html::submit(_sx('button', 'Add'), ['name' => 'add_type', 'class' => 'btn btn-primary']);
        echo "</div></td>";
        echo "</tr>";

        echo "</table>";
        Html::closeForm();

        echo "</div>";

        $rand = mt_rand();

        $data = $this->find([], ["types_id ASC"]);

        if (count($data) != 0) {
            Html::openMassiveActionsForm('mass' . "NotificationType" . $rand);
            $massiveactionparams = ['item' => __CLASS__, 'container' => 'mass' . "NotificationType" . $rand];
            Html::showMassiveActions($massiveactionparams);

            echo "<div class ='center'>";
            echo "<form method='post' name='massiveaction_form$rand' id='massiveaction_form$rand'  action=\"$target\">";
            echo "<table class='tab_cadre_fixe' cellpadding='5'>";
            echo "<tr>";
            echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . "NotificationType" . $rand) . "</th>";
            echo "<th>" . __('Type') . "</th>";
            echo "</tr>";
            foreach ($data as $ligne) {
                echo "<tr class='tab_bg_1'>";
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(__CLASS__, $ligne["id"]);
                echo "</td>";
                echo "<td>" . Dropdown::getDropdownName("glpi_computertypes", $ligne["types_id"]) . "</td>";
                echo "</tr>";
            }

            $massiveactionparams['ontop'] = false;

            echo "</table>";
            Html::closeForm();
            echo "</div>";

            Html::showMassiveActions($massiveactionparams);
        }
    }

    /**
     * Get the standard massive actions which are forbidden
     *
     * @since version 0.84
     *
     * @return array of massive actions
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }



    /**
     * Get the specific massive actions
     *
     * @since version 0.84
     *
     * @param $checkitem link item to check right   (default NULL)
     *
     * @return array $array of massive actions
     */
    public function getSpecificMassiveActions($checkitem = null)
    {


        $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'purge'] = __('Delete');

        return $actions;
    }

    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     */
    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'purge':
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     *
     * @param MassiveAction $ma
     * @param CommonDBTM    $item
     * @param array         $ids
     *
     * @return void
     */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        $type = new self();

        switch ($ma->getAction()) {
            case "purge":

                foreach ($ids as $key) {
                    if ($item->can($key, UPDATE)) {
                        if ($type->delete(['id' => $key])) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                break;
        }
    }
}
