<?php
declare(strict_types=1);

use ILIAS\UI\Renderer;
use ILIAS\UI\Factory;

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
 *
 * @ilCtrl_isCalledBy ilWhiteboardConfigGUI: ilObjComponentSettingsGUI
 * @ilCtrl_Calls ilWhiteboardConfigGUI: ilFormPropertyDispatchGUI
 *
 */
class ilWhiteboardConfigGUI extends ilPluginConfigGUI
{
    private ilWhiteboardConfig $object;
    private static Factory $factory;
    protected $control;
    protected ilGlobalTemplateInterface $tpl;
    protected $request;
    protected Renderer $renderer;

    /**
     * @throws ilCtrlException
     * @throws ilException
     */
    function performCommand($cmd): void
    {
        global $DIC;

        $this->object = new ilWhiteboardConfig();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->control = $DIC->ctrl();
        $this->request = $DIC->http()->request();
        $this->renderer = $DIC->ui()->renderer();

        switch ($cmd) {
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

    private function configure(): array
    {
        global $DIC;
        self::$factory = $DIC->ui()->factory();
        $this->control = $DIC->ctrl();

        try {
            $this->control->setParameterByClass('ilWhiteboardGUI', 'cmd', 'configure');
            $form_fields = [];

            $object = $this->object;

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

        } catch (Exception $e) {
            $section = self::$factory->messageBox()->failure($e->getMessage());
        }

        return ["config" => $section];
    }

    /**
     * @throws ilCtrlException
     */
    private function renderForm(string $form_action, array $sections): string
    {
        $form = self::$factory->input()->container()->form()->standard(
            $form_action,
            $sections
        );

        $saving_info = "";

        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $result = $form->getData();
            if ($result) {
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