<?php
use \ILIAS\UI\Component\Input\Container\Form\Standard;
/**
 * @ilCtrl_isCalledBy ilObjWhiteboardGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls      ilObjWhiteboardGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilExportGUI
 */
class ilObjWhiteboardGUI extends ilObjectPluginGUI
{
    public const LP_SESSION_ID = 'xswb_lp_session_state';
    protected ilCtrl $ctrl;
    protected ilTabsGUI $tabs;
    public ilGlobalTemplateInterface $tpl;
    protected ilWhiteboardConfig $config;

    protected function afterConstructor() : void
    {
        global $ilCtrl, $ilTabs, $tpl;
        $this->ctrl = $ilCtrl;
        $this->tabs = $ilTabs;
        $this->tpl = $tpl;
    }

    final public function getType() : string
    {
        return ilWhiteboardPlugin::ID;
    }

    /**
     * Handles all commmands of this class, centralizes permission checks
     */
    public function performCommand(string $cmd) : void
    {
        switch ($cmd) {
            case "editProperties":   // list all commands that need write permission here
            case "updateProperties":
            case "saveProperties":
            default:
                $this->checkPermission("read");
                $this->$cmd();
                break;
        }
    }

    /**
     * After object has been created -> jump to this command
     */
    function getAfterCreationCmd() : string
    {
        return "editProperties";
    }

    /**
     * Get standard command
     */
    function getStandardCmd() : string
    {
        return "showContent";
    }

//
// DISPLAY TABS
//

    /**
     * Set tabs
     */
    protected function setTabs() : void
    {
        global $ilCtrl, $ilAccess;

        // tab for the "show content" command
        if ($ilAccess->checkAccess("read", "", $this->object->getRefId())) {
            $this->tabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"));
        }

        // standard info screen tab
        $this->addInfoTab();

        // a "properties" tab
        if ($ilAccess->checkAccess("write", "", $this->object->getRefId())) {
            $this->tabs->addTab(
                "properties",
                $this->txt("properties"),
                $ilCtrl->getLinkTarget($this, "editProperties")
            );
        }

        // standard permission tab
        $this->addPermissionTab();
        $this->activateTab();
    }

    /**
     * Edit Properties. This commands uses the form class to display an input form.
     */
    protected function editProperties() : void
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
    protected function initPropertiesForm() : Standard
    {
        global $DIC;
        $ui = $DIC->ui()->factory();
        $lng = $DIC->language();
        $ctrl = $DIC->ctrl();


        // Input field for title
        $title = $ui->input()->field()->text($lng->txt("title"))
            ->withRequired(true)
            ->withValue($this->object->getTitle());

        $description = $ui->input()->field()->textarea($lng->txt("description"))->withValue($this->object->getDescription());

        // Checkbox for online
        $online = $ui->input()->field()->checkbox($lng->txt("online"))->withValue($this->object->isOnline());


        // Input field for description
        $permission = $ui->input()->field()->radio($this->plugin->txt("default_permissions"))
            ->withRequired(true)
            ->withOption('all_read', $this->plugin->txt('all_read'))
            ->withOption('all_write', $this->plugin->txt('all_write'))
            ->withValue($this->object->isAllRead() ? 'all_read' : 'all_write');

        // Construct the form with form fields
        $form_action = $ctrl->getFormAction($this, "saveProperties");
        $form_fields = ['title' => $title, 'description' => $description, 'online' => $online, 'default_permissions'=>$permission];
        return $ui->input()->container()->form()->standard($form_action, $form_fields);
    }


    /**
     * @throws ilCtrlException
     */
    protected function saveProperties() : void
    {
        GLOBAL $DIC;
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

    private function fillObject(ilObject $object, Array $form) : void
    {
        $object->setTitle($form["title"]);
        $object->setDescription($form["description"]);
        $object->setOnline($form["online"]);
        $object->setAllRead($form["default_permissions"] == "all_read");
    }

    /**
     * We need this method if we can't access the tabs otherwise...
     */
    private function activateTab() : void
    {
        $next_class = $this->ctrl->getCmdClass();

    }

    protected function showContent() : void
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

        $board->setVariable("WEBSOCKETURL", "wss://".$config->getWebsocket());
        $board->setVariable("ROOMFULL", $this->object->txt("room_full"));
        $board->setVariable("ALREADYACCESSED", $this->object->txt("open_other_tab"));
        $board->setVariable("WEBSOCKETERROR", $this->object->txt("websocket_error"));

        $tpl->setContent($board->get());

    }

    protected function isAdmin(): bool{
        return ($this->checkPermissionBool("redact") ||$this->checkPermissionBool("write"));
    }


}