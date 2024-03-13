<?php
declare(strict_types=1);

/*
 *  This file is part of the Whiteboard Repository Object plugin for ILIAS, a collaborative online whiteboard tool,
 *  developed by SURLABS with funding from the University of Freiburg.
 *
 *  This plugin is freely distributed under the terms of the GNU General Public License version 3 (GPL-3.0),
 *  a copy of which is available at https://www.gnu.org/licenses/gpl-3.0.en.html. This license allows for the free use,
 *  modification, and distribution of this software, ensuring it remains open-source and accessible to the community.
 *
 *  The Whiteboard plugin uses a version the tldraw library, which is also open-source and distributed under its specific
 *  terms and conditions. For details on the tldraw license, please refer to https://github.com/tldraw/tldraw/blob/main/LICENSE.md.
 *
 *  DISCLAIMER: The developers, contributors, and funding entities associated with the Whiteboard plugin or the tldraw library
 *  assume no responsibility for any damages or losses incurred from the use of this software. Users are encouraged to review
 *  the license agreements and comply with the terms and conditions set forth.
 *
 *  Community involvement is welcome. To report bugs, suggest improvements, or participate in discussions,
 *  please visit the Mantis system and search for ILIAS Plugins under the "Whiteboard" category at https://mantis.ilias.de.
 *
 *  For further information, documentation, and the source code, visit our GitHub repository at
 *  https://github.com/surlabs/Whiteboard.
 */
class ilObjWhiteboardAccess extends ilObjectPluginAccess implements ilConditionHandling
{

    public function _checkAccess($cmd, $permission, $ref_id, $obj_id, $user_id = null): bool
    {
        global $ilUser, $ilAccess;

        if ($user_id === 0) {
            $user_id = $ilUser->getId();
        }

        switch ($permission) {
            case "read":
                if (!self::checkOnline($obj_id) &&
                    !$ilAccess->checkAccessOfUser($user_id, "write", "", $ref_id)) {
                    return false;
                }
                break;
        }

        return true;
    }

    public static function checkOnline($a_id): bool
    {
        global $ilDB;
        $set = $ilDB->query(
            "SELECT is_online FROM rep_robj_xswb_data " .
            " WHERE id = " . $ilDB->quote($a_id, "integer")
        );
        $rec = $ilDB->fetchAssoc($set);
        if (isset($rec) && isset($rec["is_online"])) {
            return (boolean)$rec["is_online"];
        } else {
            return false;
        }
    }

    public static function getConditionOperators(): array
    {
        include_once './Services/Conditions/classes/class.ilConditionHandler.php'; //bugfix mantis 24891
        return array(
            ilConditionHandler::OPERATOR_FAILED,
            ilConditionHandler::OPERATOR_PASSED
        );
    }

    public static function checkCondition(
        $a_trigger_obj_id,
        $a_operator,
        $a_value,
        $a_usr_id
    ): bool
    {
        $ref_ids = ilObject::_getAllReferences($a_trigger_obj_id);
        $ref_id = array_shift($ref_ids);
        $object = new ilObjWhiteboard($ref_id);
        switch ($a_operator) {
            case ilConditionHandler::OPERATOR_PASSED:
                return $object->getLPStatusForUser($a_usr_id) === ilLPStatus::LP_STATUS_COMPLETED_NUM;
            case ilConditionHandler::OPERATOR_FAILED:
                return $object->getLPStatusForUser($a_usr_id) === ilLPStatus::LP_STATUS_FAILED_NUM;
        }
        return false;
    }
}