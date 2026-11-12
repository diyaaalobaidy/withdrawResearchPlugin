/**
 * Author Research Withdrawal Plugin - Frontend script for OJS 3.5
 */

(function($) {
    'use strict';

    console.log('[WithdrawResearchPlugin] script loaded.');

    function getSubmissionId() {
        var cfg = window.withdrawResearchPluginConfig || {};
        if (cfg.submissionId && parseInt(cfg.submissionId, 10) > 0) {
            return parseInt(cfg.submissionId, 10);
        }

        // 1. Try URL regex: e.g. /workflow/index/123, /authorDashboard/submission/123, /submission/123
        var href = window.location.href;
        var match = href.match(/(?:workflow|authorDashboard|submission|submissions)(?:\/index|\/submission)?\/(\d+)/i);
        if (match && match[1]) {
            return parseInt(match[1], 10);
        }

        // 2. Query param: ?submissionId=123
        var paramMatch = href.match(/[?&]submissionId=(\d+)/i);
        if (paramMatch && paramMatch[1]) {
            return parseInt(paramMatch[1], 10);
        }

        // 3. Inspect global pkp Vue state if present
        if (window.pkp && window.pkp.context && window.pkp.context.submission) {
            return parseInt(window.pkp.context.submission.id, 10);
        }

        // 4. Inspect any DOM elements with data-submission-id or id in breadcrumb
        var $subEl = $('[data-submission-id]').first();
        if ($subEl.length && $subEl.data('submission-id')) {
            return parseInt($subEl.data('submission-id'), 10);
        }

        // 5. Look for identification number in .pkpWorkflow__identification
        var $idText = $('.pkpWorkflow__identificationId, .pkpWorkflow__identification').first().text();
        var idMatch = $idText ? $idText.match(/(\d+)/) : null;
        if (idMatch && idMatch[1]) {
            return parseInt(idMatch[1], 10);
        }

        return 0;
    }

    function buildModalUrl(subId) {
        var cfg = window.withdrawResearchPluginConfig || {};
        if (cfg.modalUrl && cfg.modalUrl.length > 5) {
            return cfg.modalUrl;
        }

        // Construct standard URL from location
        var base = window.location.pathname.split('/index.php/')[0] || '';
        // Extract journal path
        var journalPath = '';
        var parts = window.location.pathname.split('/');
        for (var i = 0; i < parts.length; i++) {
            if (parts[i] === 'index.php' && parts[i+1]) {
                journalPath = parts[i+1];
                break;
            }
        }

        if (journalPath) {
            return base + '/index.php/' + journalPath + '/authorWithdraw/modal?submissionId=' + subId;
        }

        return '/authorWithdraw/modal?submissionId=' + subId;
    }

    function injectButton() {
        if ($('#btnAuthorWithdrawResearch').length > 0) {
            return;
        }

        var subId = getSubmissionId();
        console.log('[WithdrawResearchPlugin] Detected submissionId:', subId);

        var modalUrl = buildModalUrl(subId);
        var btnText = (window.pkpWithdrawLocales && window.pkpWithdrawLocales.btnText) || 'Withdraw Research';

        var btnHtml = $(
            '<button type="button" id="btnAuthorWithdrawResearch" class="pkp_button pkp_button_danger withdraw_action_btn" ' +
            'style="background-color: #dc2626 !important; border: 1px solid #b91c1c !important; color: #fff !important; ' +
            'font-weight: 600; margin: 0 10px; padding: 6px 14px; border-radius: 4px; cursor: pointer; ' +
            'display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.15); z-index: 99999;">' +
            '<span class="fa fa-ban" aria-hidden="true"></span>' +
            '<span>' + btnText + '</span>' +
            '</button>'
        );

        btnHtml.on('click', function(e) {
            e.preventDefault();
            var currentSubId = getSubmissionId();
            var currentUrl = buildModalUrl(currentSubId);
            openWithdrawModal(currentUrl);
        });

        // Try candidate insertion locations
        var $target = $(
            '.pkpWorkflow__header .pkpHeader__actions, ' +
            '.pkpHeader__actions, ' +
            '.app__pageActions, ' +
            '.stage_actions, ' +
            '.pkp_controllers_tab_workflow .pkpHeader__actions'
        ).first();

        if ($target.length) {
            $target.prepend(btnHtml);
            console.log('[WithdrawResearchPlugin] Button injected into actions.');
            return;
        }

        var $idTarget = $('.pkpWorkflow__identification, h1.app__pageHeading').first();
        if ($idTarget.length) {
            var $wrapper = $('<div class="withdraw_action_btn_wrapper" style="display: inline-block; vertical-align: middle; margin-left: 15px;"></div>').append(btnHtml);
            $idTarget.after($wrapper);
            console.log('[WithdrawResearchPlugin] Button injected next to header heading.');
            return;
        }

        var $container = $('.pkpWorkflow, #workflow, .pkp_submission_workflow, .pkp_page_content, #main').first();
        if ($container.length) {
            var $wrapper2 = $('<div class="withdraw_action_btn_wrapper" style="padding: 10px 15px; margin: 10px 0; background: #fff; border-bottom: 1px solid #e2e8f0;"></div>').append(btnHtml);
            $container.prepend($wrapper2);
            console.log('[WithdrawResearchPlugin] Button prepended to container.');
        }
    }

    function openWithdrawModal(url) {
        var modalId = 'authorWithdrawModalDialog';
        var title = (window.pkpWithdrawLocales && window.pkpWithdrawLocales.modalTitle) || 'Withdraw Research Submission';

        if ($.pkp && $.pkp.controllers && $.pkp.controllers.modal && $.pkp.controllers.modal.AjaxModalHandler) {
            var $modal = $('<div id="' + modalId + '" class="pkp_modal pkpModal"></div>');
            $('body').append($modal);
            $modal.pkpHandler('$.pkp.controllers.modal.AjaxModalHandler', {
                title: title,
                url: url,
                canClose: true
            });
        } else {
            $.get(url, function(response) {
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
                } else {
                    alert((response && response.content) ? response.content : 'Failed to load withdrawal modal.');
                }
            }).fail(function(xhr) {
                alert('Error loading withdrawal modal: ' + xhr.statusText);
            });
        }
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
        injectButton();
    });

    // Run interval for asynchronous Vue rendering
    var count = 0;
    var timer = setInterval(function() {
        count++;
        injectButton();
        if ($('#btnAuthorWithdrawResearch').length > 0 || count > 25) {
            clearInterval(timer);
        }
    }, 400);

    $(document).ajaxComplete(function() {
        injectButton();
    });

})(jQuery);
