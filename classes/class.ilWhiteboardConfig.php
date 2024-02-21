<?php

class ilWhiteboardConfig
{
    protected string $websocket_url;

    public function __construct()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $result = $ilDB->query("SELECT * FROM xswb_config WHERE config_key = 'websocket_url'");
        $record = $ilDB->fetchAssoc($result);
        $this->websocket_url = $record['value'];

    }

    /**
     * @param $value
     */
    public function setWebsocket($value): void
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $key = 'websocket_url';


        $ilDB->update(
                "xswb_config", array(
                'value' => array("text", $value)
        ), array(
                'config_key' => array("text", $key)
            )
        );

    }

    /**
     *
     * @return string
     */
    public function getWebsocket(): string
    {
        return $this->websocket_url;
    }


}