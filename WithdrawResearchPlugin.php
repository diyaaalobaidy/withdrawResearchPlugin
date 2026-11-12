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
        if ($success && $this->getEnabled($mainContextId)) {
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
        $templateMgr->addStyleSheet(
            'withdrawResearchPluginCss',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/withdrawResearch.css',
            ['contexts' => ['backend', 'frontend']]
        );

        $templateMgr->addJavaScript(
            'withdrawResearchPluginJs',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/withdrawResearch.js',
            ['contexts' => ['backend', 'frontend']]
        );

        // Detect submission object from template variables or query parameters
        $submission = $templateMgr->getTemplateVars('submission');
        $submissionId = 0;

        if ($submission && method_exists($submission, 'getId')) {
            $submissionId = (int) $submission->getId();
        } elseif ($request->getUserVar('submissionId')) {
            $submissionId = (int) $request->getUserVar('submissionId');
            $submission = Repo::submission()->get($submissionId);
        } else {
            // Check router args for workflow / submission ID: /workflow/index/{submissionId}
            $router = $request->getRouter();
            if ($router) {
                $requestedArgs = $request->getRequestedArgs();
                if (!empty($requestedArgs) && is_numeric($requestedArgs[0])) {
                    $submissionId = (int) $requestedArgs[0];
                    $submission = Repo::submission()->get($submissionId);
                }
            }
        }

        if ($submission && $submissionId > 0) {
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

            $isWithdrawn = (bool) $submission->getData('withdrawal:isWithdrawn');
            $withdrawalReason = (string) $submission->getData('withdrawal:reasonLabel');
            $withdrawalDate = (string) $submission->getData('withdrawal:dateWithdrawn');

            $templateMgr->assign([
                'canWithdrawSubmission' => $canWithdraw,
                'withdrawModalUrl' => $modalUrl,
                'withdrawSubmitUrl' => $submitUrl,
                'withdrawSubmissionId' => $submissionId,
                'isSubmissionWithdrawn' => $isWithdrawn,
                'submissionWithdrawalReason' => $withdrawalReason,
                'submissionWithdrawalDate' => $withdrawalDate,
            ]);

            // Embed configuration directly in the page HTML
            $jsVars = sprintf(
                '<script type="text/javascript">
                    window.canWithdrawSubmission = %s;
                    window.withdrawModalUrl = %s;
                    window.withdrawSubmitUrl = %s;
                    window.withdrawSubmissionId = %d;
                    window.isSubmissionWithdrawn = %s;
                    window.submissionWithdrawalReason = %s;
                    window.submissionWithdrawalDate = %s;
                    window.pkpWithdrawLocales = {
                        btnText: %s,
                        modalTitle: %s
                    };
                </script>',
                $canWithdraw ? 'true' : 'false',
                json_encode($modalUrl),
                json_encode($submitUrl),
                $submissionId,
                $isWithdrawn ? 'true' : 'false',
                json_encode($withdrawalReason),
                json_encode($withdrawalDate),
                json_encode(__('plugins.generic.withdrawResearchPlugin.author.button')),
                json_encode(__('plugins.generic.withdrawResearchPlugin.author.modalTitle'))
            );

            $templateMgr->addHeader('withdrawResearchPluginVars', $jsVars, ['contexts' => ['backend', 'frontend']]);
        }

        return false;
    }

    /**
     * @copydoc Plugin::getSetting()
     *
     * Provides intelligent defaults for unset plugin configuration options
     */
    public function getSetting($contextId, $name, $default = null)
    {
        $val = parent::getSetting($contextId, $name);
        if ($val === null) {
            $defaults = [
                'enabled' => true,
                'requireReason' => true,
                'notifyEditors' => true,
                'notifyAuthor' => true,
                'withdrawalPolicy' => '',
                'allowedStages' => [1, 3, 4, 5], // Submission, External Review, Editorial, Production
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
