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

class ilWhiteboardConfig
{
    protected string $websocket_url;
    protected string $auth_secret;

    public function __construct()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $this->websocket_url = $this->getConfigValue('websocket_url');
        $this->auth_secret = $this->getConfigValue('whiteboard_auth_secret');
    }

    protected function getConfigValue(string $key): string
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $result = $ilDB->queryF(
            "SELECT value FROM xswb_config WHERE config_key = %s",
            array("text"),
            array($key)
        );
        $record = $ilDB->fetchAssoc($result);

        return $record['value'] ?? '';
    }

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

    public function getWebsocket(): string
    {
        return $this->websocket_url;
    }

    public function setAuthSecret($value): void
    {
        global $DIC;
        $ilDB = $DIC['ilDB'];

        $key = 'whiteboard_auth_secret';

        $ilDB->update(
            "xswb_config", array(
            'value' => array("text", trim((string) $value))
        ), array(
                'config_key' => array("text", $key)
            )
        );
    }

    public function getAuthSecret(): string
    {
        return $this->auth_secret;
    }

    public function createAccessToken(string $roomId, string $username, array $permissions, int $ttlSeconds = 600): string
    {
        $secret = trim($this->auth_secret);
        if ($secret === '') {
            return '';
        }

        $payload = array(
            'roomId' => $roomId,
            'username' => $username,
            'permissions' => $permissions,
            'exp' => time() + $ttlSeconds
        );

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $secret, true));

        return $encodedPayload . '.' . $signature;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

}
