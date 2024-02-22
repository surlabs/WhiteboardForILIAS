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
class ilObjWhiteboard extends ilObjectPlugin
{

    protected bool $online = false;
    protected bool $all_read = false;

    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
    }

    final protected function initType(): void
    {
        $this->setType(ilWhiteboardPlugin::ID);
    }

    protected function doCreate(bool $clone_mode = false): void
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

    protected function doRead(): void
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

    protected function doUpdate(): void
    {
        global $ilDB;
        $ilDB->manipulate(
            $up = "UPDATE rep_robj_xswb_data SET " .
                " is_online = " . $ilDB->quote($this->isOnline(), "integer") .
                ", all_read = " . $ilDB->quote($this->isAllRead(), "integer") .
                " WHERE id = " . $ilDB->quote($this->getId(), "integer")
        );
    }

    protected function doDelete(): void
    {
        global $ilDB;
        $ilDB->manipulate(
            "DELETE FROM rep_robj_xswb_data WHERE " .
            " id = " . $ilDB->quote($this->getId(), "integer")
        );
    }

    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null): void
    {
        $prevId = $this->getId();
        $new_obj->update();
        $newId = $new_obj->getId();

        $config = new ilWhiteboardConfig();

        $payload = json_encode(array("from" => $prevId, "to" => $newId));
        $ch = curl_init('https://' . $config->getWebsocket() . '/clone-room');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($payload)));
        curl_close($ch);


    }

    //Typification undone at the en of the alpha
    public function setOnline($a_val): void
    {
        $this->online = (bool)$a_val;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    //Typification undone at the en of the alpha
    public function setAllRead($p_val): void
    {
        $this->all_read = (bool)$p_val;
    }

    public function isAllRead(): bool
    {
        return $this->all_read;
    }

}