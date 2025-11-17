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

    /**
     * Clone object functionality - duplicates whiteboard content from source to destination room
     *
     * This method handles the complete cloning process including:
     * - Creating the new ILIAS object record
     * - Communicating with the websocket server to clone visual content
     * - Comprehensive error handling and logging
     *
     * @param ilObjWhiteboard $new_obj The newly created whiteboard object
     * @param int $a_target_id Target container ID where the object is being copied
     * @param string|null $a_copy_id Copy operation identifier (optional)
     * @return void
     */
    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null): void
    {
        global $DIC;

        // Get source and destination room IDs
        $prevId = $this->getId();
        $new_obj->update();
        $newId = $new_obj->getId();

        // Initialize configuration and logging
        $config = new ilWhiteboardConfig();
        $logger = $DIC->logger()->root();

        // Log the start of cloning operation
        $logger->info("Whiteboard cloning: Starting clone operation from room {$prevId} to room {$newId}");

        // Validate websocket configuration before proceeding
        $websocketHost = $config->getWebsocket();
        if (empty($websocketHost) || trim($websocketHost) === '') {
            $logger->error("Whiteboard cloning: CONFIGURATION ERROR - Websocket URL is not configured. Please configure the websocket URL in the plugin settings. Object {$newId} created but content not cloned from {$prevId}.");
            return;
        }

        // Construct websocket URL and validate it
        $websocketUrl = 'https://' . trim($websocketHost) . '/clone-room';

        // Additional URL validation to catch malformed URLs
        if (!filter_var($websocketUrl, FILTER_VALIDATE_URL)) {
            $logger->error("Whiteboard cloning: CONFIGURATION ERROR - Invalid websocket URL format: '{$websocketUrl}'. Please check the websocket configuration in plugin settings. Expected format: 'domain.com:port' or 'ip:port'. Object {$newId} created but content not cloned from {$prevId}.");
            return;
        }

        // Prepare payload for websocket server API call
        $payload = array("from" => $prevId, "to" => $newId);

        // Log the websocket URL being used (for debugging configuration issues)
        $logger->info("Whiteboard cloning: Attempting to connect to websocket server at: {$websocketUrl}");

        try {
            // Initialize Guzzle HTTP client with appropriate configuration
            $client = new \GuzzleHttp\Client();

            // Execute POST request to websocket server clone endpoint
            $response = $client->post($websocketUrl, [
                'json' => $payload,                    // Send payload as JSON
                'timeout' => 30,                       // 30 second timeout for the request
                'connect_timeout' => 10,               // 10 second timeout for connection
                'verify' => false                      // Disable SSL verification for self-signed certificates
            ]);

            // Extract response details for processing
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            // Process successful response (HTTP 200 OK)
            if ($statusCode === 200) {
                $logger->info("Whiteboard cloning: Successfully cloned room content from {$prevId} to {$newId}. HTTP Status: {$statusCode}");

                // Attempt to decode and log JSON response if available
                if (!empty($responseBody)) {
                    $decodedResponse = json_decode($responseBody, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $logger->info("Whiteboard cloning: Websocket server response: " . json_encode($decodedResponse));
                    } else {
                        $logger->info("Whiteboard cloning: Websocket server response (raw): {$responseBody}");
                    }
                }
            } else {
                // Handle unexpected HTTP status codes
                $logger->error("Whiteboard cloning: Websocket server returned unexpected status code {$statusCode} when cloning from room {$prevId} to room {$newId}. Response: {$responseBody}. Object created but content may not be cloned.");
            }

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // Handle network connectivity issues (server unreachable, DNS issues, etc.)
            $logger->error("Whiteboard cloning: CONNECTION ERROR - Unable to connect to websocket server at {$websocketUrl} when cloning from room {$prevId} to room {$newId}. Please verify: 1) Websocket server is running, 2) URL is correct in plugin settings, 3) Network connectivity. Error: " . $e->getMessage() . ". Object created but content not cloned.");

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Handle HTTP request exceptions (4xx, 5xx errors, malformed requests, etc.)
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'N/A';
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response available';
            $logger->error("Whiteboard cloning: HTTP REQUEST ERROR - Request to websocket server failed when cloning from room {$prevId} to room {$newId}. HTTP Status: {$statusCode}, Error: " . $e->getMessage() . ", Server Response: {$responseBody}. Object created but content not cloned.");

        } catch (\Exception $e) {
            // Handle any other unexpected exceptions
            $logger->error("Whiteboard cloning: UNEXPECTED ERROR - An unexpected error occurred when cloning from room {$prevId} to room {$newId}. Error: " . $e->getMessage() . ". Object created but content may not be cloned. Please check system logs and websocket server status.");
        }
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