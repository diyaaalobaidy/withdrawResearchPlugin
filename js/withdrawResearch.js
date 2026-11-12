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

    // Also run on dynamic Vue / Ajax changes
    $(document).ajaxComplete(function() {
        if ($('#btnAuthorWithdrawResearch').length === 0 && $('#btnAuthorWithdrawDirect').length === 0) {
            initAuthorWithdrawalUI();
        }
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
        if ($('#btnAuthorWithdrawResearch').length > 0) {
            return;
        }

        var btnText = (window.pkpWithdrawLocales && window.pkpWithdrawLocales.btnText)
            ? window.pkpWithdrawLocales.btnText
            : 'Withdraw Research';

        var btnHtml = $(
            '<button type="button" id="btnAuthorWithdrawResearch" class="pkp_button pkp_button_danger withdraw_action_btn" style="background-color: #dc2626 !important; border-color: #b91c1c !important; color: #fff !important; font-weight: 600; margin-right: 8px;">' +
            '<span class="fa fa-ban" aria-hidden="true" style="margin-right: 5px;"></span>' +
            btnText +
            '</button>'
        );

        btnHtml.on('click', function(e) {
            e.preventDefault();
            openWithdrawModal();
        });

        // Search for primary targets in OJS 3.5 Vue workflow
        var $actionsTarget = $('.pkpWorkflow__header .pkpHeader__actions, .pkpHeader__actions, .stage_actions').first();

        if ($actionsTarget.length) {
            $actionsTarget.prepend(btnHtml);
        } else {
            // Secondary target: insertion right after submission identification / title
            var $idTarget = $('.pkpWorkflow__identification, h1.app__pageHeading').first();
            if ($idTarget.length) {
                var $wrapper = $('<div class="withdraw_action_btn_wrapper" style="margin: 12px 0;"></div>').append(btnHtml);
                $idTarget.after($wrapper);
            } else {
                // Fallback: prepend to workflow container
                var $fallback = $('#workflow, .pkpWorkflow, .pkp_submission_workflow').first();
                if ($fallback.length) {
                    var $wrapper2 = $('<div class="withdraw_action_btn_wrapper" style="margin: 12px 0;"></div>').append(btnHtml);
                    $fallback.prepend($wrapper2);
                }
            }
        }
    }

    /**
     * Open AJAX modal for manuscript withdrawal
     */
    window.openWithdrawModal = function() {
        var modalId = 'authorWithdrawModalDialog';
        var title = (window.pkpWithdrawLocales && window.pkpWithdrawLocales.modalTitle)
            ? window.pkpWithdrawLocales.modalTitle
            : 'Withdraw Research Submission';

        if ($.pkp && $.pkp.controllers && $.pkp.controllers.modal && $.pkp.controllers.modal.AjaxModalHandler) {
            var $modal = $('<div id="' + modalId + '" class="pkp_modal pkpModal"></div>');
            $('body').append($modal);
            $modal.pkpHandler('$.pkp.controllers.modal.AjaxModalHandler', {
                title: title,
                url: window.withdrawModalUrl,
                canClose: true
            });
        } else {
            // Fallback modal container
            $.get(window.withdrawModalUrl, function(response) {
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

        var reason = window.submissionWithdrawalReason || '';
        var date = window.submissionWithdrawalDate || '';
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

})(jQuery);
