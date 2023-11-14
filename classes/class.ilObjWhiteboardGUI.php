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
            case "showContent":   // list all commands that need read permission here
            case "setStatusToCompleted":
            case "setStatusToFailed":
            case "setStatusToInProgress":
            case "setStatusToNotAttempted":
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

        // standard export tab
        $this->addExportTab();

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

        // Input field for description
        $description = $ui->input()->field()->textarea($lng->txt("description"))->withValue($this->object->getDescription());
        $permission = $ui->input()->field()->radio($this->plugin->txt("default_permissions"))
            ->withRequired(true)
            ->withOption('all_read', $this->plugin->txt('all_read'))
            ->withOption('all_write', $this->plugin->txt('all_write'))
            ->withValue($this->object->isAllRead() ? 'all_read' : 'all_write');

        // Checkbox for online
        $online = $ui->input()->field()->checkbox($lng->txt("online"))->withValue($this->object->isOnline());

        // Construct the form with form fields
        $form_action = $ctrl->getFormAction($this, "saveProperties");
        $form_fields = ['title' => $title, 'description' => $description, 'default_permissions'=>$permission, 'online' => $online];
        $form = $ui->input()->container()->form()->standard($form_action, $form_fields);

        // Command button
        //$form = $form->withAdditionalTransformation($ui->input()->container()->form()->transformation()->commandButton($form_action, $lng->txt("update")));
        return $form;
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
     /*   var_dump($result);
        exit;


        $form->setValuesByPost();
        if ($form->checkInput()) {


        }
        $this->tpl->setContent($form->getHTML());*/
    }

    protected function showContent() : void
    {
        global $ilToolbar, $ilCtrl;

        /** @var ilToolbarGUI $ilToolbar */
        $ilToolbar->addButton("Add News", $ilCtrl->getLinkTarget($this, "addNews"));
        $ilToolbar->addButton("Add News (Lang Var)", $ilCtrl->getLinkTarget($this, "addNewsLangVar"));
        $ilToolbar->addButton("Delete One", $ilCtrl->getLinkTarget($this, "deleteOneNews"));
        $ilToolbar->addButton("Update One", $ilCtrl->getLinkTarget($this, "updateOneNews"));
        $ilToolbar->addButton("Open WhiteBoard", $ilCtrl->getLinkTarget($this, "openWhiteboard"));

        $this->tabs->activateTab("content");

        /** @var ilObjWhiteboard $object */
        $object = $this->object;

        $form = new ilPropertyFormGUI();
        $form->setTitle($object->getTitle());

        $i = new ilNonEditableValueGUI($this->plugin->txt("title"));
        $i->setInfo($object->getTitle());
        $form->addItem($i);

        $i = new ilNonEditableValueGUI($this->plugin->txt("description"));
        $i->setInfo($object->getDescription());
        $form->addItem($i);

        $i = new ilNonEditableValueGUI($this->plugin->txt("online_status"));
        $i->setInfo($object->isOnline() ? "Online" : "Offline");
        $form->addItem($i);

        global $ilUser;
        $progress = new ilLPStatusPlugin($this->object->getId());
        $status = $progress->determineStatus($this->object->getId(), $ilUser->getId());
        $i = new ilNonEditableValueGUI($this->plugin->txt("lp_status"));
        $i->setInfo($this->plugin->txt("lp_status_" . $status));
        $form->addItem($i);

        $i = new ilNonEditableValueGUI();
        $i->setInfo(
            "<a href='" . $this->ctrl->getLinkTarget($this, "setStatusToCompleted") . "'> " . $this->plugin->txt(
                "set_completed"
            )
        );
        $form->addItem($i);

        $i = new ilNonEditableValueGUI();
        $i->setInfo(
            "<a href='" . $this->ctrl->getLinkTarget($this, "setStatusToNotAttempted") . "'> " . $this->plugin->txt(
                "set_not_attempted"
            )
        );
        $form->addItem($i);

        $i = new ilNonEditableValueGUI();
        $i->setInfo(
            "<a href='" . $this->ctrl->getLinkTarget($this, "setStatusToFailed") . "'> " . $this->plugin->txt(
                "set_failed"
            )
        );
        $form->addItem($i);

        $i = new ilNonEditableValueGUI();
        $i->setInfo(
            "<a href='" . $this->ctrl->getLinkTarget($this, "setStatusToInProgress") . "'> " . $this->plugin->txt(
                "set_in_progress"
            )
        );
        $form->addItem($i);

        $i = new ilNonEditableValueGUI($this->plugin->txt("important"));
        $i->setInfo($this->plugin->txt("lp_status_info"));
        $form->addItem($i);



    }

    private function fillObject(ilObject $object, Array $form) : void
    {
        $object->setTitle($form["title"]);
        $object->setDescription($form["description"]);
        $object->setOnline($form["online"]);
    }

    /**
     * We need this method if we can't access the tabs otherwise...
     */
    private function activateTab() : void
    {
        $next_class = $this->ctrl->getCmdClass();

        switch ($next_class) {
            case 'ilexportgui':
                $this->tabs->activateTab("export");
                break;
        }
    }

    private function setStatusToCompleted() : void
    {
        $this->setStatusAndRedirect(ilLPStatus::LP_STATUS_COMPLETED_NUM);
    }

    private function setStatusAndRedirect(int $status) : void
    {
        global $ilUser;
        $_SESSION[self::LP_SESSION_ID] = $status;
        ilLPStatusWrapper::_updateStatus($this->object->getId(), $ilUser->getId());
        $this->ctrl->redirect($this, $this->getStandardCmd());
    }

    protected function setStatusToFailed() : void
    {
        $this->setStatusAndRedirect(ilLPStatus::LP_STATUS_FAILED_NUM);
    }

    protected function setStatusToInProgress() : void
    {
        $this->setStatusAndRedirect(ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
    }

    protected function setStatusToNotAttempted() : void
    {
        $this->setStatusAndRedirect(ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
    }


    //
    // News for plugin
    //

    protected function addNews() : void
    {
        global $ilCtrl, $DIC;

        $ns = $DIC->news();

        $context = $ns->contextForRefId($this->object->getRefId());
        $item = $ns->item($context);
        $item->setTitle("Hello World");
        $item->setContent("This is the news.");
        $ns->data()->save($item);

        $this->tpl->setOnScreenMessage("info", "News created", true);
        $ilCtrl->redirect($this, "showContent");
    }

    protected function addNewsLangVar() : void
    {
        global $ilCtrl, $DIC;

        $ns = $DIC->news();

        $context = $ns->contextForRefId($this->object->getRefId());
        $item = $ns->item($context);
        $item->setTitle("news_title");
        $item->setContentTextIsLangVar(true);
        $item->setContentIsLangVar(true);
        $item->setContent("news_content");
        $ns->data()->save($item);

        $this->tpl->setOnScreenMessage("info", "News created", true);
        $ilCtrl->redirect($this, "showContent");
    }

    protected function deleteOneNews() : void
    {
        global $ilCtrl, $DIC;

        $ns = $DIC->news();

        $context = $ns->contextForRefId($this->object->getRefId());
        $items = $ns->data()->getNewsOfContext($context);
        if ($n = current($items)) {
            $ns->data()->delete($n);
            $this->tpl->setOnScreenMessage("info", "News deleted", true);
        }

        $ilCtrl->redirect($this, "showContent");
    }

    protected function updateOneNews() : void
    {
        global $ilCtrl, $DIC;

        $ns = $DIC->news();

        $context = $ns->contextForRefId($this->object->getRefId());
        $items = $ns->data()->getNewsOfContext($context);
        if ($n = current($items)) {
            $n->setContent("News content changed at " . date("d.m.Y H:m:s"));
            $n->setContentTextIsLangVar(false);
            $ns->data()->save($n);
            $this->tpl->setOnScreenMessage("info", "News updated", true);
        }

        $ilCtrl->redirect($this, "showContent");
    }

    protected function openWhiteboard() : void
    {
        global $DIC;
        $tpl = $DIC['tpl'];

        $tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/Whiteboard/render/templates/default/whiteboard.js');
        $tpl->addCss('Customizing/global/plugins/Services/Repository/RepositoryObject/Whiteboard/render/templates/default/index.css');

        $board = new ilTemplate('index.html', true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/Whiteboard/render");

        $idIlias = $this->getObject()->getId();
        $userName = $DIC->user()->getFullname();

        $board->setVariable("ROOM", $idIlias);
        $board->setVariable("USERNAME", $userName);

        $role = $this->isAdmin() ? "admin" : "user";
        $board->setVariable("ROLE", $role);

        $tpl->setContent($board->get());
    }

    protected function isAdmin(): bool{
        return ($this->checkPermissionBool("redact") ||$this->checkPermissionBool("write"));
    }


}