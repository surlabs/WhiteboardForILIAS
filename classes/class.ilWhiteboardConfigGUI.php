<?php
use ILIAS\UI\Renderer;
use ILIAS\UI\Factory;

/**
 * @ilCtrl_IsCalledBy  ilWhiteboardConfigGUI: ilObjComponentSettingsGUI
 */
class ilWhiteboardConfigGUI extends ilPluginConfigGUI
{

    private ilWhiteboardConfig $object;
    private static Factory $factory;
    protected ilCtrlInterface $control;
    protected ilGlobalTemplateInterface $tpl;
    protected $request;
    protected Renderer $renderer;


    /**
     * Handles all commands, default is "configure"
     * @throws ilException
     */
    function performCommand(string $cmd): void
    {
        global $DIC;

        $this->object = new ilWhiteboardConfig();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->control = $DIC->ctrl();
        $this->request = $DIC->http()->request();
        $this->renderer = $DIC->ui()->renderer();

        switch($cmd)
        {
            case "configure":
                $sections = $this->configure();
                $form_action = $this->control->getLinkTargetByClass("ilWhiteboardConfigGUI", "configure");
                $rendered = $this->renderForm($form_action, $sections);
                break;
            default:
                throw new ilException("command not defined");

        }

        $this->tpl->setContent($rendered);
    }

    /**
     * Configure screen
     */
    private function configure(): array
    {
        global $DIC;

        self::$factory = $DIC->ui()->factory();
        $this->control = $DIC->ctrl();

        try {

            $this->control->setParameterByClass('ilWhiteboardGUI', 'cmd', 'configure');
            $form_fields = [];

            $object = $this->object;


            //Model
            $field = self::$factory->input()->field()->text(
                $this->plugin_object->txt('websocket_url'),
                $this->plugin_object->txt('info_websocket_url'))
                ->withValue($object->getWebsocket())
                ->withRequired(true)
                ->withAdditionalTransformation($DIC->refinery()->custom()->transformation(
                    function ($v) use ($object) {
                        $object->setWebsocket($v);
                    }
                ));

            $form_fields["websocket_url"] = $field;

            $section = self::$factory->input()->field()->section($form_fields, $this->plugin_object->txt("settings"), "");

        } catch(Exception $e){
            $section = self::$factory->messageBox()->failure($e->getMessage());
        }

        return ["config" => $section];

    }

    /**
     * @throws ilCtrlException
     */
    private function renderForm(string $form_action, array $sections): string
    {
        //Create the form
        $form = self::$factory->input()->container()->form()->standard(
            $form_action,
            $sections
        );

        $saving_info = "";

        //Check if the form has been submitted
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $result = $form->getData();
            if($result){
                $saving_info = $this->save();
            }
        }

        return $saving_info . $this->renderer->render($form);
    }

    public function save(): string
    {
        return $this->renderer->render(self::$factory->messageBox()->success($this->plugin_object->txt('info_config_saved')));
    }

}