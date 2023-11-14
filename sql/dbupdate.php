<#1>
<?php ?>
<#2>
<?php
GLOBAL $DIC;
$ilDB = $DIC->database();
$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'is_online' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => false
    ),
    'all_read' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => false
    )

);
if(!$ilDB->tableExists("rep_robj_xswb_data")) {
    $ilDB->createTable("rep_robj_xswb_data", $fields);
    $ilDB->addPrimaryKey("rep_robj_xswb_data", array("id"));
}

?>