/**
 * Author Research Withdrawal Plugin - Frontend script for OJS 3.5
 *
 * Handles client-side button injection into submission workflow,
 * status badge rendering, and AJAX modal submission.
 */

(function($) {
    'use strict';

    function getCfg() {
        return window.withdrawResearchPluginConfig || {};
    }

    /**
     * Detect submission ID from current browser URL if server-side resolution was 0
     */
    function detectClientSubmissionId() {
        var cfg = getCfg();
        if (cfg.submissionId && parseInt(cfg.submissionId, 10) > 0) {
            return parseInt(cfg.submissionId, 10);
        }

        // Try parsing from window.location.pathname / hash / search
        var path = window.location.pathname + window.location.search;
        var match = path.match(/(?:workflow|authorDashboard|submission|submissions)(?:\/index|\/submission)?\/(\d+)/i);
        if (match && match[1]) {
            return parseInt(match[1], 10);
        }

        var paramMatch = window.location.search.match(/[?&]submissionId=(\d+)/i);
        if (paramMatch && paramMatch[1]) {
            return parseInt(paramMatch[1], 10);
        }

        // Check if pkp Vue global has current submission ID
        if (window.pkp && window.pkp.context && window.pkp.context.submission) {
            return parseInt(window.pkp.context.submission.id, 10);
        }

        return 0;
    }

    function initAuthorWithdrawalUI() {
        var cfg = getCfg();

        // 1. If submission is already withdrawn, display banner
        if (cfg.isWithdrawn || window.isSubmissionWithdrawn) {
            renderWithdrawnBanner();
            return;
        }

        var subId = detectClientSubmissionId();
        if (!subId) {
            return;
        }

        // If client knows the submission ID, construct modal URL if empty
        var modalUrl = cfg.modalUrl || window.withdrawModalUrl;
        if (!modalUrl && cfg.checkUrl) {
            modalUrl = cfg.checkUrl + (cfg.checkUrl.indexOf('?') >= 0 ? '&' : '?') + 'submissionId=' + subId;
            cfg.modalUrl = modalUrl;
            window.withdrawModalUrl = modalUrl;
        }

        if (!modalUrl) {
            return;
        }

        injectWithdrawButton(modalUrl);
    }

    /**
     * Inject Withdraw Research button into the workflow actions or header
     */
    function injectWithdrawButton(modalUrl) {
        if ($('#btnAuthorWithdrawResearch').length > 0) {
            return;
        }

        var cfg = getCfg();
        var btnText = cfg.btnText || (window.pkpWithdrawLocales && window.pkpWithdrawLocales.btnText) || 'Withdraw Research';

        var btnHtml = $(
            '<button type="button" id="btnAuthorWithdrawResearch" class="pkp_button pkp_button_danger withdraw_action_btn" style="background-color: #dc2626 !important; border: 1px solid #b91c1c !important; color: #fff !important; font-weight: 600; margin: 0 10px; padding: 6px 14px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.15); z-index: 9999;">' +
            '<span class="fa fa-ban" aria-hidden="true"></span>' +
            '<span>' + btnText + '</span>' +
            '</button>'
        );

        btnHtml.on('click', function(e) {
            e.preventDefault();
            openWithdrawModal(modalUrl);
        });

        // 1. Target: workflow header actions
        var $actionsTarget = $(
            '.pkpWorkflow__header .pkpHeader__actions, ' +
            '.pkpHeader__actions, ' +
            '.app__pageActions, ' +
            '.stage_actions, ' +
            '.pkp_controllers_tab_workflow .pkpHeader__actions'
        ).first();

        if ($actionsTarget.length) {
            $actionsTarget.prepend(btnHtml);
            return;
        }

        // 2. Target: next to or after the workflow title/status
        var $idTarget = $('.pkpWorkflow__identification, h1.app__pageHeading').first();
        if ($idTarget.length) {
            var $wrapper = $('<div class="withdraw_action_btn_wrapper" style="display: inline-block; vertical-align: middle; margin-left: 15px;"></div>').append(btnHtml);
            $idTarget.after($wrapper);
            return;
        }

        // 3. Fallback: prepend to workflow container
        var $workflowContainer = $('.pkpWorkflow, #workflow, .pkp_submission_workflow, .pkp_page_content').first();
        if ($workflowContainer.length) {
            var $wrapper2 = $('<div class="withdraw_action_btn_wrapper" style="margin: 12px 0;"></div>').append(btnHtml);
            $workflowContainer.prepend($wrapper2);
        }
    }

    /**
     * Open AJAX modal for manuscript withdrawal
     */
    window.openWithdrawModal = function(urlOverride) {
        var cfg = getCfg();
        var modalId = 'authorWithdrawModalDialog';
        var title = cfg.modalTitle || (window.pkpWithdrawLocales && window.pkpWithdrawLocales.modalTitle) || 'Withdraw Research Submission';
        var modalUrl = urlOverride || cfg.modalUrl || window.withdrawModalUrl;

        if (!modalUrl) {
            var subId = detectClientSubmissionId();
            if (subId && cfg.checkUrl) {
                modalUrl = cfg.checkUrl + (cfg.checkUrl.indexOf('?') >= 0 ? '&' : '?') + 'submissionId=' + subId;
            }
        }

        if ($.pkp && $.pkp.controllers && $.pkp.controllers.modal && $.pkp.controllers.modal.AjaxModalHandler) {
            var $modal = $('<div id="' + modalId + '" class="pkp_modal pkpModal"></div>');
            $('body').append($modal);
            $modal.pkpHandler('$.pkp.controllers.modal.AjaxModalHandler', {
                title: title,
                url: modalUrl,
                canClose: true
            });
        } else {
            // Fallback modal container
            $.get(modalUrl, function(response) {
                if (response && response.status === true && response.content) {
                    var $dialog = $(
                        '<div id="' + modalId + '" class="withdraw_native_modal_overlay">' +
                        '<div class="withdraw_native_modal_dialog">' +
                        '<button type="button" class="withdraw_modal_close">&times;</button>' +
                        '<h3 style="margin-top: 0; color: #1e293b; font-size: 1.15rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">' + title + '</h3>' +
                        '<div class="withdraw_native_modal_body">' + response.content + '</div>' +
                        '</div></div>'
                    );
                    $('body').append($dialog);

                    $dialog.find('.withdraw_modal_close, .withdraw_btn_cancel').on('click', function() {
                        $dialog.remove();
                    });
                }
            });
        }
    };

    /**
     * Render notice banner when viewing a withdrawn manuscript
     */
    function renderWithdrawnBanner() {
        if ($('.withdraw_withdrawn_status_banner').length) {
            return;
        }

        var cfg = getCfg();
        var reason = cfg.reason || window.submissionWithdrawalReason || '';
        var date = cfg.date || window.submissionWithdrawalDate || '';
        var bannerText = 'This research was withdrawn by the author' + (date ? ' on ' + date : '') + (reason ? '. Reason: ' + reason : '');

        var banner = $(
            '<div class="withdraw_withdrawn_status_banner" style="margin: 15px 0; padding: 12px 18px; background: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #e11d48; border-radius: 4px; color: #9f1239; font-size: 14px;">' +
            '<span class="fa fa-info-circle" aria-hidden="true" style="margin-right: 8px;"></span>' +
            '<strong>' + bannerText + '</strong>' +
            '</div>'
        );

        $('.pkpWorkflow, .pkp_submission_workflow, #workflow, .pkp_page_content').first().prepend(banner);
    }

    // Delegated submission handler for the withdrawal form
    $(document).on('submit', '#authorWithdrawForm', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('#submitWithdrawBtn');
        var formData = $form.serialize();

        $btn.prop('disabled', true).addClass('pkp_button_disabled');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res && res.status === true) {
                    var msg = (res.content && res.content.message) ? res.content.message : 'Research submission successfully withdrawn.';
                    alert(msg);
                    window.location.reload();
                } else {
                    var errMsg = (res && res.content) ? res.content : 'An error occurred while processing the withdrawal request.';
                    alert(errMsg);
                    $btn.prop('disabled', false).removeClass('pkp_button_disabled');
                }
            },
            error: function(xhr, status, error) {
                alert('Server error: ' + (error || 'Unable to complete withdrawal request.'));
                $btn.prop('disabled', false).removeClass('pkp_button_disabled');
            }
        });
    });

    $(document).ready(function() {
        initAuthorWithdrawalUI();
    });

    // Observer loop for Vue components mounting
    var attempts = 0;
    var checkInterval = setInterval(function() {
        attempts++;
        if ($('#btnAuthorWithdrawResearch').length > 0 || window.isSubmissionWithdrawn) {
            clearInterval(checkInterval);
            return;
        }
        initAuthorWithdrawalUI();
        if (attempts > 30) {
            clearInterval(checkInterval);
        }
    }, 350);

    $(document).ajaxComplete(function() {
        if ($('#btnAuthorWithdrawResearch').length === 0) {
            initAuthorWithdrawalUI();
        }
    });

})(jQuery);
