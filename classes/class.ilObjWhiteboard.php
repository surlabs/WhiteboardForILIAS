<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

class ilObjWhiteboard extends ilObjectPlugin implements ilLPStatusPluginInterface
{
    protected bool $online = false;

    protected bool $all_read = false;

    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
    }

    final protected function initType() : void
    {
        $this->setType(ilWhiteboardPlugin::ID);
    }

    protected function doCreate(bool $clone_mode = false) : void
    {
        global $ilDB;

        $ilDB->manipulate(
            "INSERT INTO rep_robj_xswb_data " .
            "(id, is_online, all_read) VALUES (" .
            $ilDB->quote($this->getId(), "integer") . "," .
            $ilDB->quote(0, "integer") . "," .
            $ilDB->quote(0, "integer") .

            ")"
        );
    }

    protected function doRead() : void
    {
        global $ilDB;

        $set = $ilDB->query(
            "SELECT * FROM rep_robj_xswb_data " .
            " WHERE id = " . $ilDB->quote($this->getId(), "integer")
        );
        while ($rec = $ilDB->fetchAssoc($set)) {
            $this->setOnline($rec["is_online"]);
            $this->setAllRead($rec["all_read"]);
        }
    }

    protected function doUpdate() : void
    {
        global $ilDB;
        $ilDB->manipulate(
            $up = "UPDATE rep_robj_xswb_data SET " .
                " is_online = " . $ilDB->quote($this->isOnline(), "integer") .
                ", all_read = " . $ilDB->quote($this->isAllRead(), "integer") .
                " WHERE id = " . $ilDB->quote($this->getId(), "integer")
        );
    }

    protected function doDelete() : void
    {
        global $ilDB;

        $ilDB->manipulate(
            "DELETE FROM rep_robj_xswb_data WHERE " .
            " id = " . $ilDB->quote($this->getId(), "integer")
        );
    }

    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null) : void
    {
        //$new_obj->setOnline($this->isOnline());
        $new_obj->update();
    }

    public function setOnline(bool $a_val) : void
    {
        $this->online = $a_val;
    }

    public function isOnline() : bool
    {
        return $this->online;
    }

    public function setAllRead(bool $p_val) : void
    {
        $this->all_read = $p_val;
    }

    public function isAllRead() : bool
    {
        return $this->all_read;
    }

    public function getLPCompleted() : array
    {
        return array();
    }

    public function getLPNotAttempted() : array
    {
        return array();
    }

    public function getLPFailed() : array
    {
        return array(6);
    }

    public function getLPInProgress() : array
    {
        return array();
    }

    public function getLPStatusForUser(int $a_user_id) : int
    {
        global $ilUser;
        if ($ilUser->getId() == $a_user_id) {
            return $_SESSION[ilObjWhiteboardGUI::LP_SESSION_ID] ?? 0;
        } else {
            return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
        }
    }

}