<?php
declare(strict_types=1);

use \ILIAS\UI\Component\Input\Container\Form\Standard;

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

/**
 * @ilCtrl_isCalledBy ilObjWhiteboardGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls      ilObjWhiteboardGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilExportGUI
 */
class ilObjWhiteboardGUI extends ilObjectPluginGUI
{
    protected ilCtrl $ctrl;
    protected ilTabsGUI $tabs;
    public ilGlobalTemplateInterface $tpl;
    protected ilWhiteboardConfig $config;

    protected function afterConstructor(): void
    {
        global $ilCtrl, $ilTabs, $tpl;
        $this->ctrl = $ilCtrl;
        $this->tabs = $ilTabs;
        $this->tpl = $tpl;
    }

    final public function getType(): string
    {
        return ilWhiteboardPlugin::ID;
    }

    public function performCommand(string $cmd): void
    {
        switch ($cmd) {
            case "editProperties":
            case "updateProperties":
            case "saveProperties":
                $this->checkPermission("write");
                $this->$cmd();
                break;
            case "showContent":
            default:
                $this->checkPermission("read");
                $this->$cmd();
                break;
        }
    }

    function getAfterCreationCmd(): string
    {
        return "editProperties";
    }

    function getStandardCmd(): string
    {
        return "showContent";
    }

    protected function setTabs(): void
    {
        global $ilCtrl, $ilAccess;

        if ($ilAccess->checkAccess("read", "", $this->object->getRefId())) {
            $this->tabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"));
        }

        $this->addInfoTab();

        if ($ilAccess->checkAccess("write", "", $this->object->getRefId())) {
            $this->tabs->addTab(
                "properties",
                $this->txt("properties"),
                $ilCtrl->getLinkTarget($this, "editProperties")
            );
        }

        $this->addPermissionTab();
        $this->activateTab();
    }

    /**
     * Edit Properties. This commands uses the form class to display an input form.
     * @throws ilCtrlException
     */
    protected function editProperties(): void
    {
        global $DIC;
        $this->tabs->activateTab("properties");
        $form = $this->initPropertiesForm();

        $renderer = $DIC->ui()->renderer();
        $this->tpl->setContent($renderer->render($form));
    }

    /**
     * @throws ilCtrlException
     */
    protected function initPropertiesForm(): Standard
    {
        global $DIC;
        $ui = $DIC->ui()->factory();
        $lng = $DIC->language();
        $ctrl = $DIC->ctrl();

        $title = $ui->input()->field()->text($lng->txt("title"))
            ->withRequired(true)
            ->withValue($this->object->getTitle());

        $description = $ui->input()->field()->textarea($lng->txt("description"))->withValue($this->object->getDescription());

        // Checkbox for online
        $online = $ui->input()->field()->checkbox($lng->txt("online"))->withValue($this->object->isOnline());


        // Input field for description
        $permission = $ui->input()->field()->radio($this->plugin->txt("default_permissions"))
            ->withRequired(true)
            ->withOption('all_read', $this->plugin->txt('all_read'), $this->plugin->txt('all_read_byline'))
            ->withOption('all_write', $this->plugin->txt('all_write'), $this->plugin->txt('all_write_byline'))
            ->withValue($this->object->isAllRead() ? 'all_read' : 'all_write');

        // Construct the form with form fields
        $form_action = $ctrl->getFormAction($this, "saveProperties");
        $form_fields = ['title' => $title, 'description' => $description, 'online' => $online, 'default_permissions'=>$permission];
        return $ui->input()->container()->form()->standard($form_action, $form_fields);
    }


    /**
     * @throws ilCtrlException
     */
    protected function saveProperties(): void
    {
        global $DIC;
        $request = $DIC->http()->request();
        $form = $this->initPropertiesForm();

        if ($request->getMethod() == "POST") {
            $form = $form->withRequest($request);
            $result = $form->getData();
            $this->fillObject($this->object, $result);
            $this->object->update();
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("update_successful"), true);
            $this->ctrl->redirect($this, "editProperties");
        }
    }

    private function fillObject(ilObject $object, array $form): void
    {
        $object->setTitle($form["title"]);
        $object->setDescription($form["description"]);
        $object->setOnline($form["online"]);
        $object->setAllRead($form["default_permissions"] == "all_read");
    }

    /**
     * We need this method if we can't access the tabs otherwise...
     */
    private function activateTab(): void
    {
        $next_class = $this->ctrl->getCmdClass();
    }

    protected function showContent(): void
    {
        global $DIC;
        $config = new ilWhiteboardConfig();
        $tpl = $DIC['tpl'];
        $this->tabs->activateTab("content");

        /** @var ilObjWhiteboard $object */
        $object = $this->object;
        $tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/Whiteboard/render/templates/default/index.js');

        $board = new ilTemplate('index.html', true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/Whiteboard/render");

        $idIlias = $this->getObject()->getId();
        $userName = $DIC->user()->getFullname();
        $allRead = $object->isAllRead() ? "true" : "false";

        $board->setVariable("ROOM", $idIlias);
        $board->setVariable("USERNAME", $userName);
        $board->setVariable("ALLREAD", $allRead);

        $role = $this->isAdmin() ? "admin" : "user";
        $board->setVariable("ROLE", $role);

        $board->setVariable("WEBSOCKETURL", "wss://" . $config->getWebsocket());
        $board->setVariable("ROOMFULL", $this->object->txt("room_full"));
        $board->setVariable("ALREADYACCESSED", $this->object->txt("open_other_tab"));
        $board->setVariable("WEBSOCKETERROR", $this->object->txt("websocket_error"));

        $disclaimer_text = $this->isAdmin() ? "" : $this->object->txt("disclaimer");
        $board->setVariable("DISCLAIMER", $disclaimer_text);


        $tpl->setContent($board->get());
    }

    protected function isAdmin(): bool
    {
        return ($this->checkPermissionBool("redact") || $this->checkPermissionBool("write"));
    }

}