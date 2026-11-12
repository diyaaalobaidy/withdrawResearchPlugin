<?php

/**
 * @file plugins/generic/withdrawResearchPlugin/classes/handlers/WithdrawSettingsHandler.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class WithdrawSettingsHandler
 * @ingroup plugins_generic_withdrawResearchPlugin
 *
 * @brief Controller for managing journal manager settings for the withdrawal plugin in OJS 3.5.
 */

namespace APP\plugins\generic\withdrawResearchPlugin\classes\handlers;

use APP\core\Application;
use APP\handler\Handler;
use APP\plugins\generic\withdrawResearchPlugin\WithdrawResearchPlugin;
use PKP\core\JSONMessage;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;
use PKP\template\PKPTemplateManager;

class WithdrawSettingsHandler extends Handler
{
    /** @var WithdrawResearchPlugin|null */
    protected ?WithdrawResearchPlugin $plugin;

    public function __construct(?WithdrawResearchPlugin $plugin = null)
    {
        parent::__construct();
        $this->plugin = $plugin ?: PluginRegistry::getPlugin('generic', 'withdrawResearchPlugin');

        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            ['index', 'settings', 'save']
        );
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Display settings modal
     */
    public function settings($args, $request): JSONMessage
    {
        $context = $request->getContext();
        $contextId = (int) $context->getId();

        $templateMgr = PKPTemplateManager::getManager($request);

        $allowedStages = (array) $this->plugin->getSetting($contextId, 'allowedStages');
        if (empty($allowedStages)) {
            $allowedStages = [1, 3, 4, 5];
        }

        $stagesList = [
            1 => __('submission.submission'),
            3 => __('workflow.review.externalReview'),
            4 => __('submission.editorial'),
            5 => __('submission.production'),
        ];

        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'enabled' => (bool) $this->plugin->getSetting($contextId, 'enabled'),
            'requireReason' => (bool) $this->plugin->getSetting($contextId, 'requireReason'),
            'notifyEditors' => (bool) $this->plugin->getSetting($contextId, 'notifyEditors'),
            'notifyAuthor' => (bool) $this->plugin->getSetting($contextId, 'notifyAuthor'),
            'withdrawalPolicy' => (string) $this->plugin->getSetting($contextId, 'withdrawalPolicy'),
            'allowedStages' => array_map('intval', $allowedStages),
            'availableStages' => $stagesList,
        ]);

        return new JSONMessage(true, $templateMgr->fetch($this->plugin->getTemplateResource('settings/settings.tpl')));
    }

    /**
     * Save settings form
     */
    public function save($args, $request): JSONMessage
    {
        $context = $request->getContext();
        $contextId = (int) $context->getId();

        $enabled = (bool) $request->getUserVar('enabled');
        $requireReason = (bool) $request->getUserVar('requireReason');
        $notifyEditors = (bool) $request->getUserVar('notifyEditors');
        $notifyAuthor = (bool) $request->getUserVar('notifyAuthor');
        $withdrawalPolicy = (string) $request->getUserVar('withdrawalPolicy');
        $rawStages = $request->getUserVar('allowedStages');
        $allowedStages = is_array($rawStages) ? array_map('intval', $rawStages) : [1, 3, 4, 5];

        $this->plugin->updateSetting($contextId, 'enabled', $enabled, 'bool');
        $this->plugin->updateSetting($contextId, 'requireReason', $requireReason, 'bool');
        $this->plugin->updateSetting($contextId, 'notifyEditors', $notifyEditors, 'bool');
        $this->plugin->updateSetting($contextId, 'notifyAuthor', $notifyAuthor, 'bool');
        $this->plugin->updateSetting($contextId, 'withdrawalPolicy', $withdrawalPolicy, 'string');
        $this->plugin->updateSetting($contextId, 'allowedStages', $allowedStages, 'object');

        return new JSONMessage(true, ['saved' => true]);
    }
}
