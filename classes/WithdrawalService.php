<?php

/**
 * @file plugins/generic/withdrawResearchPlugin/classes/WithdrawalService.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @class WithdrawalService
 * @ingroup plugins_generic_withdrawResearchPlugin
 *
 * @brief Core business logic service for submission withdrawal evaluation, execution,
 *        activity logging, and email dispatch in OJS 3.5.
 */

namespace APP\plugins\generic\withdrawResearchPlugin\classes;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\withdrawResearchPlugin\classes\mail\WithdrawalMailable;
use APP\plugins\generic\withdrawResearchPlugin\WithdrawResearchPlugin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PKP\context\Context;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignment;
use PKP\submission\PKPSubmission;
use PKP\user\User;

class WithdrawalService
{
    /** @var WithdrawResearchPlugin */
    protected WithdrawResearchPlugin $plugin;

    public function __construct(WithdrawResearchPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Get localized title safely across OJS 3.3/3.4/3.5 models.
     */
    public function getSubmissionTitle(PKPSubmission $submission): string
    {
        $publication = $submission->getCurrentPublication();
        if ($publication) {
            $title = (string) ($publication->getLocalizedTitle() ?: $publication->getLocalizedData('title'));
            if (!empty($title)) {
                return $title;
            }
        }

        if (method_exists($submission, 'getLocalizedTitle')) {
            return (string) $submission->getLocalizedTitle();
        }

        return (string) ($submission->getData('title') ?: ('#' . $submission->getId()));
    }

    /**
     * Check if a given user is allowed to request withdrawal for a submission.
     *
     * @param int $submissionId
     * @param int $userId
     * @param int $contextId
     * @return array [bool $allowed, string $reasonCode, string $reasonMessage]
     */
    public function canWithdraw(int $submissionId, int $userId, int $contextId): array
    {
        if (!(bool) $this->plugin->getSetting($contextId, 'enabled')) {
            return [false, 'plugin_disabled', __('plugins.generic.withdrawResearchPlugin.error.pluginDisabled')];
        }

        $submission = Repo::submission()->get($submissionId);
        if (!$submission || (int) $submission->getData('contextId') !== $contextId) {
            return [false, 'not_found', __('plugins.generic.withdrawResearchPlugin.error.submissionNotFound')];
        }

        // Status check: must not be already declined, withdrawn, or published
        $status = (int) $submission->getData('status');
        if ($status === PKPSubmission::STATUS_DECLINED) {
            return [false, 'already_declined_or_withdrawn', __('plugins.generic.withdrawResearchPlugin.error.alreadyDeclined')];
        }
        if ($status === PKPSubmission::STATUS_PUBLISHED) {
            return [false, 'already_published', __('plugins.generic.withdrawResearchPlugin.error.alreadyPublished')];
        }

        // Check if publication is scheduled or already published in current publication
        $currentPublication = $submission->getCurrentPublication();
        if ($currentPublication && (int) $currentPublication->getData('status') === PKPSubmission::STATUS_PUBLISHED) {
            return [false, 'already_published', __('plugins.generic.withdrawResearchPlugin.error.alreadyPublished')];
        }

        // Stage check
        $stageId = (int) $submission->getData('stageId');
        $allowedStages = (array) $this->plugin->getSetting($contextId, 'allowedStages');
        if (empty($allowedStages)) {
            $allowedStages = [1, 3, 4, 5];
        }
        if (!in_array($stageId, array_map('intval', $allowedStages), true)) {
            return [false, 'stage_not_allowed', __('plugins.generic.withdrawResearchPlugin.error.stageNotAllowed')];
        }

        // Authorship / Role check: allows assigned authors, submitter, and managers/admins
        if (!$this->isUserAuthorOfSubmission($submissionId, $userId)) {
            return [false, 'not_author', __('plugins.generic.withdrawResearchPlugin.error.notAuthor')];
        }

        return [true, 'ok', ''];
    }

    /**
     * Determine whether the given user is authorized to withdraw the submission
     * (assigned author, submitter, or journal manager/admin).
     */
    public function isUserAuthorOfSubmission(int $submissionId, int $userId): bool
    {
        $user = Repo::user()->get($userId);
        $request = Application::get()->getRequest();
        $context = $request ? $request->getContext() : null;

        // Managers and site admins can always test and initiate withdrawal
        if ($context && $user && ($user->hasRole([Role::ROLE_ID_MANAGER], $context->getId()) || $user->hasRole([Role::ROLE_ID_SITE_ADMIN], 0))) {
            return true;
        }

        // 1. Check via StageAssignments
        try {
            $stageAssignments = Repo::stageAssignment()->getCollector()
                ->filterBySubmissionIds([$submissionId])
                ->filterByRoleIds([Role::ROLE_ID_AUTHOR])
                ->filterByUserId($userId)
                ->getMany();

            if ($stageAssignments && $stageAssignments->count() > 0) {
                return true;
            }
        } catch (\Throwable $e) {
            // continue fallback checks
        }

        // 2. Check submission submitter ID (user_id in submissions table)
        $submission = Repo::submission()->get($submissionId);
        if ($submission && (int) $submission->getData('userId') === $userId) {
            return true;
        }

        // 3. Check authors metadata for matching email
        if ($user && $submission) {
            $userEmail = strtolower(trim((string) $user->getEmail()));
            $publication = $submission->getCurrentPublication();
            if ($publication) {
                $authors = $publication->getData('authors') ?? [];
                foreach ($authors as $author) {
                    $authorEmail = strtolower(trim((string) $author->getData('email')));
                    if (!empty($authorEmail) && $authorEmail === $userEmail) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Execute the withdrawal of a research submission.
     *
     * @param int $submissionId
     * @param User $authorUser
     * @param Context $context
     * @param string $withdrawalReason
     * @param string $withdrawalNote
     * @return array [bool $success, string $message]
     */
    public function executeWithdrawal(
        int $submissionId,
        User $authorUser,
        Context $context,
        string $withdrawalReason,
        string $withdrawalNote = ''
    ): array {
        $contextId = (int) $context->getId();
        [$allowed, $code, $msg] = $this->canWithdraw($submissionId, (int) $authorUser->getId(), $contextId);

        if (!$allowed) {
            return [false, $msg];
        }

        $submission = Repo::submission()->get($submissionId);
        $stageIdBefore = (int) $submission->getData('stageId');
        $statusBefore = (int) $submission->getData('status');

        $now = date('Y-m-d H:i:s');
        $reasonLabels = $this->getWithdrawalReasons();
        $localizedReason = $reasonLabels[$withdrawalReason] ?? $withdrawalReason;

        // 1. Update submission status to STATUS_DECLINED (4) in OJS
        $submission->setData('status', PKPSubmission::STATUS_DECLINED);
        $submission->setData('dateStatusModified', $now);

        // Store custom withdrawal metadata on the submission object
        $submission->setData('withdrawal:isWithdrawn', true);
        $submission->setData('withdrawal:dateWithdrawn', $now);
        $submission->setData('withdrawal:userId', (int) $authorUser->getId());
        $submission->setData('withdrawal:reasonKey', $withdrawalReason);
        $submission->setData('withdrawal:reasonLabel', $localizedReason);
        $submission->setData('withdrawal:notes', $withdrawalNote);
        $submission->setData('withdrawal:stageIdBefore', $stageIdBefore);

        Repo::submission()->edit($submission, []);

        // 2. Unpublish / ensure publication state is unpublished if scheduled
        $publication = $submission->getCurrentPublication();
        if ($publication && (int) $publication->getData('status') !== PKPSubmission::STATUS_DECLINED) {
            $publication->setData('status', PKPSubmission::STATUS_DECLINED);
            $publication->setData('dateWithdrawn', $now);
            Repo::publication()->edit($publication, []);
        }

        // 3. Record editorial event log entry
        $this->logWithdrawalEvent($submission, $authorUser, $localizedReason, $withdrawalNote);

        // 4. Send email notifications to assigned editors & journal contact
        if ((bool) $this->plugin->getSetting($contextId, 'notifyEditors')) {
            $this->sendEditorWithdrawalNotifications($submission, $authorUser, $context, $localizedReason, $withdrawalNote);
        }

        // 5. Send confirmation email to author
        if ((bool) $this->plugin->getSetting($contextId, 'notifyAuthor')) {
            $this->sendAuthorConfirmationNotification($submission, $authorUser, $context, $localizedReason, $withdrawalNote);
        }

        return [true, __('plugins.generic.withdrawResearchPlugin.withdrawSuccess')];
    }

    /**
     * Log the withdrawal action to OJS event/activity log.
     */
    protected function logWithdrawalEvent(
        PKPSubmission $submission,
        User $authorUser,
        string $localizedReason,
        string $withdrawalNote
    ): void {
        $logMessage = __('plugins.generic.withdrawResearchPlugin.log.entry', [
            'authorName' => $authorUser->getFullName(),
            'authorEmail' => $authorUser->getEmail(),
            'reason' => $localizedReason,
            'note' => !empty($withdrawalNote) ? $withdrawalNote : __('common.none'),
            'date' => date('Y-m-d H:i:s'),
        ]);

        if (class_exists('APP\log\event\SubmissionEventLogEntry') && class_exists('APP\facades\Repo')) {
            try {
                import('lib.pkp.classes.log.SubmissionLog');
                if (class_exists('\PKP\log\PKPSubmissionLog')) {
                    \PKP\log\PKPSubmissionLog::logEvent(
                        Application::get()->getRequest(),
                        $submission,
                        \PKP\log\PKPSubmissionEventLogEntry::SUBMISSION_LOG_LAST_REVISION,
                        $logMessage
                    );
                }
            } catch (\Throwable $e) {
                error_log('WithdrawResearchPlugin: Log event error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get list of standard withdrawal reason choices.
     */
    public function getWithdrawalReasons(): array
    {
        return [
            'duplicate_submission' => __('plugins.generic.withdrawResearchPlugin.reason.duplicateSubmission'),
            'serious_errors' => __('plugins.generic.withdrawResearchPlugin.reason.seriousErrors'),
            'authorship_dispute' => __('plugins.generic.withdrawResearchPlugin.reason.authorshipDispute'),
            'publishing_elsewhere' => __('plugins.generic.withdrawResearchPlugin.reason.publishingElsewhere'),
            'institutional_request' => __('plugins.generic.withdrawResearchPlugin.reason.institutionalRequest'),
            'delay_in_review' => __('plugins.generic.withdrawResearchPlugin.reason.delayInReview'),
            'other' => __('plugins.generic.withdrawResearchPlugin.reason.other'),
        ];
    }

    /**
     * Retrieve recipient editors assigned to this submission or journal contact.
     */
    public function getEditorRecipients(PKPSubmission $submission, Context $context): array
    {
        $recipients = [];

        try {
            $stageAssignments = Repo::stageAssignment()->getCollector()
                ->filterBySubmissionIds([(int) $submission->getId()])
                ->filterByRoleIds([Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR])
                ->getMany();

            if ($stageAssignments) {
                foreach ($stageAssignments as $stageAssignment) {
                    $user = Repo::user()->get((int) $stageAssignment->getUserId());
                    if ($user && !empty($user->getEmail())) {
                        $recipients[$user->getEmail()] = $user->getFullName();
                    }
                }
            }
        } catch (\Throwable $e) {
            // continue
        }

        // If no direct stage assignments found, fall back to Journal Primary Contact
        if (empty($recipients)) {
            $contactEmail = (string) $context->getData('contactEmail');
            $contactName = (string) $context->getData('contactName') ?: $context->getLocalizedName();
            if (!empty($contactEmail)) {
                $recipients[$contactEmail] = $contactName;
            }
        }

        return $recipients;
    }

    /**
     * Dispatch notification emails to editors.
     */
    public function sendEditorWithdrawalNotifications(
        PKPSubmission $submission,
        User $authorUser,
        Context $context,
        string $reasonLabel,
        string $withdrawalNote
    ): void {
        $recipients = $this->getEditorRecipients($submission, $context);
        if (empty($recipients)) {
            return;
        }

        $journalName = $context->getLocalizedName();
        $articleTitle = $this->getSubmissionTitle($submission);
        $submissionId = $submission->getId();

        $subject = __('plugins.generic.withdrawResearchPlugin.email.editorSubject', [
            'submissionId' => $submissionId,
            'journalName' => $journalName,
        ]);

        $body = __('plugins.generic.withdrawResearchPlugin.email.editorBody', [
            'journalName' => $journalName,
            'submissionId' => $submissionId,
            'submissionTitle' => $articleTitle,
            'authorName' => $authorUser->getFullName(),
            'authorEmail' => $authorUser->getEmail(),
            'reason' => $reasonLabel,
            'notes' => !empty($withdrawalNote) ? $withdrawalNote : __('common.none'),
            'date' => date('Y-m-d H:i:s'),
        ]);

        $fromEmail = (string) ($context->getData('contactEmail') ?: 'noreply@' . parse_url($context->getUrl(), PHP_URL_HOST));
        $fromName = (string) ($context->getData('contactName') ?: $journalName);

        foreach ($recipients as $email => $name) {
            $this->dispatchEmail($fromEmail, $fromName, $email, $name, $subject, $body);
        }
    }

    /**
     * Dispatch confirmation email to withdrawing author.
     */
    public function sendAuthorConfirmationNotification(
        PKPSubmission $submission,
        User $authorUser,
        Context $context,
        string $reasonLabel,
        string $withdrawalNote
    ): void {
        $journalName = $context->getLocalizedName();
        $articleTitle = $this->getSubmissionTitle($submission);
        $submissionId = $submission->getId();

        $subject = __('plugins.generic.withdrawResearchPlugin.email.authorSubject', [
            'submissionId' => $submissionId,
            'journalName' => $journalName,
        ]);

        $body = __('plugins.generic.withdrawResearchPlugin.email.authorBody', [
            'authorName' => $authorUser->getFullName(),
            'journalName' => $journalName,
            'submissionId' => $submissionId,
            'submissionTitle' => $articleTitle,
            'reason' => $reasonLabel,
            'notes' => !empty($withdrawalNote) ? $withdrawalNote : __('common.none'),
            'date' => date('Y-m-d H:i:s'),
        ]);

        $fromEmail = (string) ($context->getData('contactEmail') ?: 'noreply@' . parse_url($context->getUrl(), PHP_URL_HOST));
        $fromName = (string) ($context->getData('contactName') ?: $journalName);

        $this->dispatchEmail($fromEmail, $fromName, $authorUser->getEmail(), $authorUser->getFullName(), $subject, $body);
    }

    /**
     * Send email via Laravel Mail facade (OJS 3.4/3.5) or fallback to PKP legacy mail.
     */
    public function dispatchEmail(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $body
    ): bool {
        try {
            if (class_exists('PKP\mail\Mailable') && class_exists('Illuminate\Support\Facades\Mail')) {
                $mailable = new WithdrawalMailable();
                $mailable->from($fromEmail, $fromName)
                    ->to($toEmail, $toName)
                    ->subject($subject)
                    ->body(nl2br(htmlspecialchars($body)));
                $mailable->replyTo($fromEmail, $fromName);

                Mail::send($mailable);
                return true;
            }

            if (class_exists('\Mail')) {
                $mail = new \Mail();
                $mail->setFrom($fromEmail, $fromName);
                $mail->setReplyTo($fromEmail, $fromName);
                $mail->addRecipient($toEmail, $toName);
                $mail->setSubject($subject);
                $mail->setBody($body);
                return (bool) $mail->send();
            }
        } catch (\Throwable $e) {
            error_log('WithdrawResearchPlugin::dispatchEmail error: ' . $e->getMessage());
        }

        return false;
    }
}
