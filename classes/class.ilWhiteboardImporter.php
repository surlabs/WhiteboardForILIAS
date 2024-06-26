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

class ilWhiteboardImporter extends ilXmlImporter
{

    public function importXmlRepresentation(
        string          $a_entity,
        string          $a_id,
        string          $a_xml,
        ilImportMapping $a_mapping
    ): void
    {
        $xml = simplexml_load_string($a_xml);
        $pl = new ilWhiteboardPlugin();
        $entity = new ilObjWhiteboard();

        $entity->setTitle((string)$xml->title . " " . $pl->txt("copy"));
        $entity->setDescription((string)$xml->description);
        $entity->setOnline((bool)$xml->online);
        $entity->setImportId($a_id);
        $entity->create();
        $new_id = $entity->getId();

        $a_mapping->addMapping("Plugins/TestObjectRepository", "xswb", $a_id, (string)$new_id);
    }
}