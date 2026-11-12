/**
 * Author Research Withdrawal Plugin - Frontend script
 *
 * Handles client-side button injection into submission workflow,
 * status badge rendering, and AJAX modal submission.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initAuthorWithdrawalUI();
    });

    function initAuthorWithdrawalUI() {
        // If submission is already withdrawn, display banner
        if (window.isSubmissionWithdrawn) {
            renderWithdrawnBanner();
            return;
        }

        // Only inject if user has permission to withdraw
        if (!window.canWithdrawSubmission || !window.withdrawModalUrl) {
            return;
        }

        injectWithdrawButton();
    }

    /**
     * Inject Withdraw Research button into the workflow actions or header
     */
    function injectWithdrawButton() {
        // Avoid duplicate button injection
        if ($('#btnAuthorWithdrawResearch').length > 0) {
            return;
        }

        var btnHtml = $(
            '<button type="button" id="btnAuthorWithdrawResearch" class="pkp_button pkp_button_danger withdraw_action_btn">' +
            '<span class="fa fa-ban" aria-hidden="true" style="margin-right: 5px;"></span>' +
            (window.pkpWithdrawLocales && window.pkpWithdrawLocales.btnText ? window.pkpWithdrawLocales.btnText : 'Withdraw Research') +
            '</button>'
        );

        btnHtml.on('click', function(e) {
            e.preventDefault();
            openWithdrawModal();
        });

        // Try primary target: workflow header actions (.pkpHeader__actions, .pkp_workflow .pkp_page_title)
        var $actionsTarget = $('.pkpHeader__actions, .pkp_workflow_header .pkpHeader__actions, .stage_actions, .pkp_controllers_tab_workflow .pkpHeader__actions').first();

        if ($actionsTarget.length) {
            $actionsTarget.prepend(btnHtml);
        } else {
            // Fallback: place in editorial actions / stage container or top bar
            var $fallbackTarget = $('.pkp_tab_actions, .pkp_workflow, #workflow').first();
            if ($fallbackTarget.length) {
                var $wrapper = $('<div class="withdraw_action_btn_wrapper" style="margin: 10px 0;"></div>').append(btnHtml);
                $fallbackTarget.prepend($wrapper);
            }
        }
    }

    /**
     * Open AJAX modal for manuscript withdrawal
     */
    function openWithdrawModal() {
        var modalId = 'authorWithdrawModalDialog';
        var title = (window.pkpWithdrawLocales && window.pkpWithdrawLocales.modalTitle) ? window.pkpWithdrawLocales.modalTitle : 'Withdraw Research Submission';

        // Use PKP modal handler if available
        if ($.pkp && $.pkp.controllers && $.pkp.controllers.modal && $.pkp.controllers.modal.AjaxModalHandler) {
            var $modal = $('<div id="' + modalId + '" class="pkp_modal pkpModal"></div>');
            $('body').append($modal);
            $modal.pkpHandler('$.pkp.controllers.modal.AjaxModalHandler', {
                title: title,
                url: window.withdrawModalUrl,
                canClose: true
            });
        } else {
            // Native fallback modal container
            $.get(window.withdrawModalUrl, function(response) {
                if (response && response.status === true && response.content) {
                    var $dialog = $('<div id="' + modalId + '" class="withdraw_native_modal_overlay"><div class="withdraw_native_modal_dialog"><button class="withdraw_modal_close">&times;</button><h3>' + title + '</h3><div class="withdraw_native_modal_body">' + response.content + '</div></div></div>');
                    $('body').append($dialog);

                    $dialog.find('.withdraw_modal_close, .withdraw_btn_cancel').on('click', function() {
                        $dialog.remove();
                    });
                }
            });
        }
    }

    /**
     * Render notice banner when viewing a withdrawn manuscript
     */
    function renderWithdrawnBanner() {
        if ($('.withdraw_withdrawn_status_banner').length) {
            return;
        }

        var reason = window.submissionWithdrawalReason || '';
        var date = window.submissionWithdrawalDate || '';
        var bannerText = 'This research was withdrawn by the author' + (date ? ' on ' + date : '') + (reason ? '. Reason: ' + reason : '');

        var banner = $(
            '<div class="withdraw_withdrawn_status_banner">' +
            '<span class="fa fa-info-circle" aria-hidden="true"></span> ' +
            '<strong>' + bannerText + '</strong>' +
            '</div>'
        );

        $('.pkp_workflow, .pkp_page_content, #workflow').first().prepend(banner);
    }

    // Attach delegated submission handler for the withdrawal form
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

})(jQuery);
