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
            // Register page handler hook for authorWithdraw endpoints
            Hook::add('LoadHandler', [$this, 'callbackLoadHandler']);

            // Register TemplateManager hook for injecting assets and UI variables (supported in OJS 3.5)
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
     * Add settings link to the plugin entry in the Plugins list
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
        if (!$this->getEnabled($contextId)) {
            return false;
        }

        $user = $request->getUser();
        if (!$user) {
            return false;
        }

        // Add custom CSS and JS
        $baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();
        $templateMgr->addStyleSheet('withdrawResearchPluginCss', $baseUrl . '/css/withdrawResearch.css');
        $templateMgr->addJavaScript('withdrawResearchPluginJs', $baseUrl . '/js/withdrawResearch.js');

        // Extract submission ID from template, request, or URI path
        $submissionId = 0;
        $submission = $templateMgr->getTemplateVars('submission');

        if ($submission && method_exists($submission, 'getId')) {
            $submissionId = (int) $submission->getId();
        } elseif ($request->getUserVar('submissionId')) {
            $submissionId = (int) $request->getUserVar('submissionId');
            $submission = Repo::submission()->get($submissionId);
        } else {
            // Check requested args /workflow/index/{id} or /authorDashboard/submission/{id}
            $requestedArgs = (array) $request->getRequestedArgs();
            foreach ($requestedArgs as $arg) {
                if (is_numeric($arg) && (int) $arg > 0) {
                    $submissionId = (int) $arg;
                    $submission = Repo::submission()->get($submissionId);
                    break;
                }
            }

            // Fallback: parse submission ID from request path URI
            if ($submissionId === 0) {
                $uri = (string) $request->getRequestPath();
                if (preg_match('#/(?:workflow|authorDashboard)/(?:index|submission)/(\d+)#', $uri, $matches)) {
                    $submissionId = (int) $matches[1];
                    $submission = Repo::submission()->get($submissionId);
                }
            }
        }

        $canWithdraw = false;
        $isWithdrawn = false;
        $withdrawalReason = '';
        $withdrawalDate = '';
        $modalUrl = '';
        $submitUrl = '';

        if ($submissionId > 0) {
            if (!$submission) {
                $submission = Repo::submission()->get($submissionId);
            }

            [$canWithdraw, $code, $msg] = $this->getService()->canWithdraw($submissionId, (int) $user->getId(), $contextId);

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

            if ($submission) {
                $isWithdrawn = (bool) $submission->getData('withdrawal:isWithdrawn');
                $withdrawalReason = (string) $submission->getData('withdrawal:reasonLabel');
                $withdrawalDate = (string) $submission->getData('withdrawal:dateWithdrawn');
            }
        }

        // Pass global configuration object
        $clientConfig = [
            'submissionId' => $submissionId,
            'canWithdraw' => $canWithdraw,
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

        $templateMgr->addHeader('withdrawResearchPluginVars', $scriptTag);

        return false;
    }
}
