<?php

/**
 * @file plugins/generic/withdrawResearchPlugin/classes/handlers/AuthorWithdrawHandler.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class AuthorWithdrawHandler
 * @ingroup plugins_generic_withdrawResearchPlugin
 *
 * @brief Author controller for displaying the withdrawal modal and processing withdrawal requests in OJS 3.5.
 */

namespace APP\plugins\generic\withdrawResearchPlugin\classes\handlers;

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\plugins\generic\withdrawResearchPlugin\classes\WithdrawalService;
use APP\plugins\generic\withdrawResearchPlugin\WithdrawResearchPlugin;
use PKP\core\JSONMessage;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;
use PKP\template\PKPTemplateManager;

class AuthorWithdrawHandler extends Handler
{
    /** @var WithdrawResearchPlugin|null */
    protected ?WithdrawResearchPlugin $plugin;

    /** @var WithdrawalService */
    protected WithdrawalService $service;

    public function __construct(?WithdrawResearchPlugin $plugin = null)
    {
        parent::__construct();
        $this->plugin = $plugin ?: PluginRegistry::getPlugin('generic', 'withdrawResearchPlugin');
        $this->service = new WithdrawalService($this->plugin);

        // Require logged-in users with author, manager, or site admin role
        $this->addRoleAssignment(
            [Role::ROLE_ID_AUTHOR, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            ['modal', 'submitWithdrawal']
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
     * Show withdrawal confirmation & details modal to author
     */
    public function modal($args, $request): JSONMessage
    {
        $context = $request->getContext();
        $user = $request->getUser();

        if (!$context || !$user) {
            return new JSONMessage(false, __('common.permissionDenied'));
        }

        $submissionId = (int) $request->getUserVar('submissionId');
        $contextId = (int) $context->getId();
        $userId = (int) $user->getId();

        [$allowed, $code, $errorMsg] = $this->service->canWithdraw($submissionId, $userId, $contextId);
        if (!$allowed) {
            return new JSONMessage(false, $errorMsg);
        }

        $submission = Repo::submission()->get($submissionId);
        $submissionTitle = '';
        if ($submission) {
            $publication = $submission->getCurrentPublication();
            if ($publication) {
                $submissionTitle = (string) ($publication->getLocalizedTitle() ?: $publication->getLocalizedData('title'));
            }
            if (empty($submissionTitle) && method_exists($submission, 'getLocalizedTitle')) {
                $submissionTitle = (string) $submission->getLocalizedTitle();
            }
        }

        $reasons = $this->service->getWithdrawalReasons();
        $policy = (string) $this->plugin->getSetting($contextId, 'withdrawalPolicy');
        $requireReason = (bool) $this->plugin->getSetting($contextId, 'requireReason');

        $templateMgr = PKPTemplateManager::getManager($request);
        $templateMgr->assign([
            'submissionId' => $submissionId,
            'submissionTitle' => $submissionTitle,
            'reasons' => $reasons,
            'withdrawalPolicy' => $policy,
            'requireReason' => $requireReason,
            'submitUrl' => $request->getDispatcher()->url(
                $request,
                \PKP\core\PKPApplication::ROUTE_PAGE,
                null,
                'authorWithdraw',
                'submitWithdrawal'
            ),
        ]);

        return new JSONMessage(true, $templateMgr->fetch($this->plugin->getTemplateResource('author/withdrawModal.tpl')));
    }

    /**
     * Process author withdrawal request submission
     */
    public function submitWithdrawal($args, $request): JSONMessage
    {
        $context = $request->getContext();
        $user = $request->getUser();

        if (!$context || !$user) {
            return new JSONMessage(false, __('common.permissionDenied'));
        }

        $submissionId = (int) $request->getUserVar('submissionId');
        $withdrawalReason = (string) $request->getUserVar('withdrawalReason');
        $withdrawalNote = (string) $request->getUserVar('withdrawalNote');
        $confirmed = (bool) $request->getUserVar('confirmWithdrawal');

        if (!$confirmed) {
            return new JSONMessage(false, __('plugins.generic.withdrawResearchPlugin.error.confirmRequired'));
        }

        $contextId = (int) $context->getId();
        $requireReason = (bool) $this->plugin->getSetting($contextId, 'requireReason');

        if ($requireReason && empty($withdrawalReason)) {
            return new JSONMessage(false, __('plugins.generic.withdrawResearchPlugin.error.reasonRequired'));
        }

        [$success, $msg] = $this->service->executeWithdrawal(
            $submissionId,
            $user,
            $context,
            $withdrawalReason,
            $withdrawalNote
        );

        if (!$success) {
            return new JSONMessage(false, $msg);
        }

        return new JSONMessage(true, [
            'message' => $msg,
            'submissionId' => $submissionId,
        ]);
    }
}
