<?php

/**
 * @file plugins/generic/withdrawResearchPlugin/WithdrawResearchPlugin.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class WithdrawResearchPlugin
 * @ingroup plugins_generic_withdrawResearchPlugin
 *
 * @brief OJS 3.5 generic plugin enabling authors to securely withdraw their research/submissions
 *        from the journal workflow with customizable reasons, policies, and editor alerts.
 */

namespace APP\plugins\generic\withdrawResearchPlugin;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\withdrawResearchPlugin\classes\handlers\AuthorWithdrawHandler;
use APP\plugins\generic\withdrawResearchPlugin\classes\handlers\WithdrawSettingsHandler;
use APP\plugins\generic\withdrawResearchPlugin\classes\WithdrawalService;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class WithdrawResearchPlugin extends GenericPlugin
{
    /** @var WithdrawalService|null */
    protected ?WithdrawalService $_service = null;

    /**
     * Get WithdrawalService instance
     */
    public function getService(): WithdrawalService
    {
        if ($this->_service === null) {
            $this->_service = new WithdrawalService($this);
        }
        return $this->_service;
    }

    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success) {
            // Frontend and backend routing hook
            Hook::add('LoadHandler', [$this, 'callbackLoadHandler']);

            // Asset and variables injection hook
            Hook::add('TemplateManager::display', [$this, 'callbackTemplateDisplay']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.withdrawResearchPlugin.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.withdrawResearchPlugin.description');
    }

    /**
     * @copydoc Plugin::getCanEnable()
     */
    public function getCanEnable(): bool
    {
        return true;
    }

    /**
     * @copydoc Plugin::getCanDisable()
     */
    public function getCanDisable(): bool
    {
        return true;
    }

    /**
     * Provide management action links in the plugin list.
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();
        $settingsAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($actions, $settingsAction);
        return $actions;
    }

    /**
     * Handle manager plugin settings actions
     */
    public function manage($args, $request)
    {
        $verb = $request->getUserVar('verb');
        $handler = new WithdrawSettingsHandler($this);

        if ($verb === 'settings') {
            return $handler->settings($args, $request);
        } elseif ($verb === 'save') {
            return $handler->save($args, $request);
        }

        return parent::manage($args, $request);
    }

    /**
     * Route hook callback for 'authorWithdraw' page handler
     */
    public function callbackLoadHandler(string $hookName, array $args): bool
    {
        $page = $args[0];

        if ($page === 'authorWithdraw') {
            $args[3] = new AuthorWithdrawHandler($this);
            return true;
        }

        return false;
    }

    /**
     * Hook into TemplateManager to inject JS/CSS and workflow withdrawal parameters
     */
    public function callbackTemplateDisplay(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        $context = $request ? $request->getContext() : null;

        if (!$context) {
            return false;
        }

        $contextId = (int) $context->getId();

        // Check if plugin is enabled in the journal
        if (!$this->getEnabled($contextId)) {
            return false;
        }

        $user = $request->getUser();
        $userId = $user ? (int) $user->getId() : 0;

        // Add custom CSS and JS to backend context
        $baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();
        $templateMgr->addStyleSheet(
            'withdrawResearchPluginCss',
            $baseUrl . '/css/withdrawResearch.css',
            ['contexts' => ['backend', 'frontend']]
        );

        $templateMgr->addJavaScript(
            'withdrawResearchPluginJs',
            $baseUrl . '/js/withdrawResearch.js',
            ['contexts' => ['backend', 'frontend']]
        );

        // 1. Check all OJS 3.5 request and query params for submission ID
        $submissionId = 0;
        if ($request->getUserVar('workflowSubmissionId')) {
            $submissionId = (int) $request->getUserVar('workflowSubmissionId');
        } elseif ($request->getUserVar('submissionId')) {
            $submissionId = (int) $request->getUserVar('submissionId');
        }

        // 2. Check template variables
        $submission = null;
        if ($submissionId === 0) {
            $submission = $templateMgr->getTemplateVars('submission');
            if ($submission && is_object($submission) && method_exists($submission, 'getId')) {
                $submissionId = (int) $submission->getId();
            }
        }

        if ($submissionId === 0) {
            $workflowSubmission = $templateMgr->getTemplateVars('workflowSubmission');
            if ($workflowSubmission && is_object($workflowSubmission) && method_exists($workflowSubmission, 'getId')) {
                $submissionId = (int) $workflowSubmission->getId();
                $submission = $workflowSubmission;
            }
        }

        // 3. Check requested URL args (e.g. /workflow/index/65952)
        if ($submissionId === 0) {
            $requestedArgs = (array) $request->getRequestedArgs();
            foreach ($requestedArgs as $arg) {
                if (is_numeric($arg) && (int) $arg > 0) {
                    $submissionId = (int) $arg;
                    break;
                }
            }
        }

        // 4. Check $_SERVER URI path and query string
        if ($submissionId === 0) {
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
            if (preg_match('#[?&](?:workflowSubmissionId|submissionId)=(\d+)#i', $uri, $matches)) {
                $submissionId = (int) $matches[1];
            } elseif (preg_match('#/(?:workflow|authorDashboard|submission|submissions)/(?:index/|submission/)?(\d+)#i', $uri, $matches)) {
                $submissionId = (int) $matches[1];
            }
        }

        $canWithdraw = false;
        $reasonCode = 'init';
        $reasonMessage = '';
        $isWithdrawn = false;
        $withdrawalReason = '';
        $withdrawalDate = '';
        $modalUrl = '';
        $submitUrl = '';

        if ($submissionId > 0) {
            if (!$submission || !method_exists($submission, 'getId')) {
                $submission = Repo::submission()->get($submissionId);
            }

            if ($submission && $userId > 0) {
                [$canWithdraw, $reasonCode, $reasonMessage] = $this->getService()->canWithdraw($submissionId, $userId, $contextId);

                $modalUrl = $request->getDispatcher()->url(
                    $request,
                    PKPApplication::ROUTE_PAGE,
                    null,
                    'authorWithdraw',
                    'modal',
                    null,
                    ['submissionId' => $submissionId]
                );

                $submitUrl = $request->getDispatcher()->url(
                    $request,
                    PKPApplication::ROUTE_PAGE,
                    null,
                    'authorWithdraw',
                    'submitWithdrawal'
                );

                $isWithdrawn = (bool) $submission->getData('withdrawal:isWithdrawn');
                $withdrawalReason = (string) $submission->getData('withdrawal:reasonLabel');
                $withdrawalDate = (string) $submission->getData('withdrawal:dateWithdrawn');
            }
        }

        $clientConfig = [
            'submissionId' => $submissionId,
            'canWithdraw' => $canWithdraw,
            'reasonCode' => $reasonCode,
            'reasonMessage' => $reasonMessage,
            'isWithdrawn' => $isWithdrawn,
            'reason' => $withdrawalReason,
            'date' => $withdrawalDate,
            'modalUrl' => $modalUrl,
            'submitUrl' => $submitUrl,
            'btnText' => __('plugins.generic.withdrawResearchPlugin.author.button'),
            'modalTitle' => __('plugins.generic.withdrawResearchPlugin.author.modalTitle'),
            'debug' => true,
        ];

        $scriptTag = '<script type="text/javascript">
            window.withdrawResearchPluginConfig = ' . json_encode($clientConfig) . ';
            window.canWithdrawSubmission = ' . ($canWithdraw ? 'true' : 'false') . ';
            window.withdrawModalUrl = ' . json_encode($modalUrl) . ';
            window.withdrawSubmitUrl = ' . json_encode($submitUrl) . ';
            window.withdrawSubmissionId = ' . $submissionId . ';
            window.isSubmissionWithdrawn = ' . ($isWithdrawn ? 'true' : 'false') . ';
            window.submissionWithdrawalReason = ' . json_encode($withdrawalReason) . ';
            window.submissionWithdrawalDate = ' . json_encode($withdrawalDate) . ';
            window.pkpWithdrawLocales = {
                btnText: ' . json_encode(__('plugins.generic.withdrawResearchPlugin.author.button')) . ',
                modalTitle: ' . json_encode(__('plugins.generic.withdrawResearchPlugin.author.modalTitle')) . '
            };
        </script>';

        $templateMgr->addHeader('withdrawResearchPluginVars', $scriptTag, ['contexts' => ['backend', 'frontend']]);

        return false;
    }

    /**
     * @copydoc Plugin::getSetting()
     *
     * Inactive (disabled) by default until explicitly enabled by the Journal Manager.
     */
    public function getSetting($contextId, $name, $default = null)
    {
        $val = parent::getSetting($contextId, $name);
        if ($val === null) {
            $defaults = [
                'enabled' => false,
                'requireReason' => true,
                'notifyEditors' => true,
                'notifyAuthor' => true,
                'withdrawalPolicy' => '',
                'allowedStages' => [1, 3, 4, 5],
            ];

            if ($default !== null) {
                return $default;
            }

            if (array_key_exists($name, $defaults)) {
                return $defaults[$name];
            }
        }

        return $val;
    }
}
