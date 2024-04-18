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

class ilWhiteboardExporter extends ilXmlExporter
{

    public function getXmlRepresentation($a_entity, $a_schema_version, $a_id)
    {
        $ref_ids = ilObject::_getAllReferences((int)$a_id);
        $ref_id = array_shift($ref_ids);
        $entity = new ilObjWhiteboard($ref_id);

        $writer = new ilXmlWriter();
        $writer->xmlStartTag("xswb");
        $writer->xmlElement("title", null, $entity->getTitle());
        $writer->xmlElement("description", null, $entity->getDescription());
        $writer->xmlElement("online", null, $entity->isOnline());
        $writer->xmlEndTag("xswb");

        return $writer->xmlDumpMem(false);
    }

    public function init(): void
    {

    }

    public function getValidSchemaVersions($a_entity): array
    {
        return array(
            "8.0.0" => array(
                "namespace" => "http://www.ilias.de/Plugins/Whiteboard/md/8_0",
                "xsd_file" => "ilias_md_8_0.xsd",
                "min" => "8.0.0",
                "max" => ""
            )
        );
    }
}
