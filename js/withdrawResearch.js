/**
 * Author Research Withdrawal Plugin - Frontend script for OJS 3.5
 */

(function($) {
    'use strict';

    console.log('[WithdrawResearchPlugin] initialized with config:', window.withdrawResearchPluginConfig);

    function getSubmissionId() {
        var cfg = window.withdrawResearchPluginConfig || {};
        if (cfg.submissionId && parseInt(cfg.submissionId, 10) > 0) {
            return parseInt(cfg.submissionId, 10);
        }

        var href = window.location.href;
        var matchWorkflowParam = href.match(/[?&]workflowSubmissionId=(\d+)/i);
        if (matchWorkflowParam && matchWorkflowParam[1]) {
            return parseInt(matchWorkflowParam[1], 10);
        }

        var paramMatch = href.match(/[?&]submissionId=(\d+)/i);
        if (paramMatch && paramMatch[1]) {
            return parseInt(paramMatch[1], 10);
        }

        return 0;
    }

    function buildModalUrl(subId) {
        var cfg = window.withdrawResearchPluginConfig || {};
        if (cfg.modalUrl && cfg.modalUrl.length > 5 && parseInt(cfg.submissionId, 10) === subId) {
            return cfg.modalUrl;
        }

        var base = window.location.pathname.split('/index.php/')[0] || '';
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

    function createWithdrawButton(subId) {
        var cfg = window.withdrawResearchPluginConfig || {};
        var modalUrl = buildModalUrl(subId);
        var btnText = cfg.btnText || (window.pkpWithdrawLocales && window.pkpWithdrawLocales.btnText) || 'Withdraw Research';

        var $btn = $(
            '<button type="button" id="btnAuthorWithdrawResearch" class="pkp_button pkp_button_danger withdraw_action_btn" ' +
            'style="background-color: #dc2626 !important; border: 1px solid #b91c1c !important; color: #fff !important; ' +
            'font-weight: 600; padding: 7px 16px; border-radius: 4px; cursor: pointer; margin: 0 10px; ' +
            'display: inline-flex; align-items: center; gap: 7px; box-shadow: 0 1px 3px rgba(0,0,0,0.15); z-index: 99999;">' +
            '<span class="fa fa-ban" aria-hidden="true"></span>' +
            '<span>' + btnText + '</span>' +
            '</button>'
        );

        $btn.on('click', function(e) {
            e.preventDefault();
            var currentSubId = getSubmissionId();
            var currentUrl = buildModalUrl(currentSubId);
            openWithdrawModal(currentUrl);
        });

        return $btn;
    }

    function injectButton() {
        if ($('#btnAuthorWithdrawResearch').length > 0) {
            return true;
        }

        var subId = getSubmissionId();
        if (!subId) {
            return false;
        }

        var $btn = createWithdrawButton(subId);

        // 1. OJS 3.5 Unified Dashboard Drawer / Workflow Header Actions
        var $target = $(
            '.pkpWorkflow__header .pkpHeader__actions, ' +
            '.sideModal__actions, ' +
            '.sideModal__header .pkpHeader__actions, ' +
            '.pkpHeader__actions, ' +
            '.app__pageActions, ' +
            '.stage_actions, ' +
            '.pkp_controllers_tab_workflow .pkpHeader__actions'
        ).filter(':visible').first();

        if (!$target.length) {
            $target = $('.pkpHeader__actions').first();
        }

        if ($target.length) {
            $target.prepend($btn);
            console.log('[WithdrawResearchPlugin] Injected button into .pkpHeader__actions');
            return true;
        }

        // 2. Next to identification heading
        var $idTarget = $('.pkpWorkflow__identification, .pkpWorkflow__header, h1.app__pageHeading').filter(':visible').first();
        if ($idTarget.length) {
            var $wrapper = $('<div class="withdraw_action_btn_wrapper" style="display: inline-block; vertical-align: middle; margin-left: 15px;"></div>').append($btn);
            $idTarget.after($wrapper);
            console.log('[WithdrawResearchPlugin] Injected button after identification heading');
            return true;
        }

        // 3. Side modal / drawer body or workflow container
        var $container = $(
            '.sideModal__body, ' +
            '.sideModal, ' +
            '.pkpWorkflow, ' +
            '#workflow, ' +
            '.pkp_submission_workflow, ' +
            '.pkp_page_content, ' +
            '[role="main"]'
        ).filter(':visible').first();

        if ($container.length) {
            var $wrapper2 = $('<div class="withdraw_action_btn_wrapper" style="padding: 12px 18px; margin: 10px 0; background: #fff7ed; border: 1px solid #ffedd5; border-left: 4px solid #f97316; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">' +
                '<span style="font-weight: 600; color: #9a3412;">Need to withdraw this submission from the journal?</span>' +
            '</div>').append($btn);
            $container.prepend($wrapper2);
            console.log('[WithdrawResearchPlugin] Injected banner into container');
            return true;
        }

        // 4. Fixed Floating Action Pill as guaranteed UI anchor
        var $floatingPill = $(
            '<div id="withdrawFloatingPill" style="position: fixed; bottom: 25px; right: 30px; z-index: 999999; box-shadow: 0 4px 15px rgba(0,0,0,0.25); border-radius: 50px; overflow: hidden;"></div>'
        ).append($btn);
        $('body').append($floatingPill);
        console.log('[WithdrawResearchPlugin] Injected floating action pill into body.');
        return true;
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

    // Observer loop for Vue side modal / drawer mounting
    var count = 0;
    var timer = setInterval(function() {
        count++;
        var success = injectButton();
        if (success || count > 35) {
            clearInterval(timer);
        }
    }, 300);

    $(document).ajaxComplete(function() {
        injectButton();
    });

    window.addEventListener('popstate', function() {
        injectButton();
    });

})(jQuery);
