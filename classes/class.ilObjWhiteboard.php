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

class ilObjWhiteboard extends ilObjectPlugin
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
        $prevId = $this->getId();
        $new_obj->update();
        $newId = $new_obj->getId();

        $config = new ilWhiteboardConfig();

        $payload = json_encode(array("from" => $prevId, "to" => $newId));
        $ch = curl_init('https://'.$config->getWebsocket().'/clone-room');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($payload)));
        curl_close($ch);


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
    

}