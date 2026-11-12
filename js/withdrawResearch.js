/**
 * Author Research Withdrawal Plugin - Frontend script for OJS 3.5
 */

(function($) {
    'use strict';

    function getCfg() {
        return window.withdrawResearchPluginConfig || {};
    }

    function getSubmissionId() {
        var cfg = getCfg();
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
        var cfg = getCfg();
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
        var cfg = getCfg();
        var modalUrl = buildModalUrl(subId);
        var btnText = cfg.btnText || (window.pkpWithdrawLocales && window.pkpWithdrawLocales.btnText) || 'Withdraw Research';

        var $btn = $(
            '<button type="button" id="btnAuthorWithdrawResearch" class="pkpButton pkpButton--isWarnable withdraw_action_btn" ' +
            'style="background-color: #dc2626 !important; border: 1px solid #b91c1c !important; color: #fff !important; ' +
            'font-weight: 600; padding: 6px 14px; border-radius: 4px; cursor: pointer; margin-right: 8px; margin-left: 8px; ' +
            'display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.15); z-index: 99999;">' +
            '<span class="fa fa-ban" aria-hidden="true"></span>' +
            '<span>' + btnText + '</span>' +
            '</button>'
        );

        $btn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var currentSubId = getSubmissionId();
            var currentUrl = buildModalUrl(currentSubId);
            openWithdrawModal(currentUrl);
        });

        return $btn;
    }

    function findLibraryButton() {
        var $candidates = $('button, a.pkpButton').filter(function() {
            var text = ($(this).text() || '').trim().toLowerCase();
            var ref = $(this).attr('ref') || '';
            var onclick = $(this).attr('@click') || '';
            return ref === 'library' ||
                   onclick.indexOf('openLibrary') >= 0 ||
                   text.indexOf('submission library') >= 0 ||
                   text.indexOf('مكتبة التقديم') >= 0 ||
                   text.indexOf('مكتبة') >= 0 ||
                   text.indexOf('library') >= 0;
        });

        return $candidates.filter(':visible').first();
    }

    function injectButton() {
        // STRICT AUTHOR CHECK: Only show if author permission is true
        var cfg = getCfg();
        if (cfg.canWithdraw !== true && window.canWithdrawSubmission !== true) {
            // If user is an editor / not the author, remove button if present and return
            $('#btnAuthorWithdrawResearch').remove();
            return false;
        }

        if ($('#btnAuthorWithdrawResearch').length > 0) {
            return true;
        }

        var subId = getSubmissionId();
        if (!subId) {
            return false;
        }

        var $btn = createWithdrawButton(subId);

        // 1. Target: Directly beside the Library button
        var $libraryBtn = findLibraryButton();
        if ($libraryBtn.length) {
            $libraryBtn.before($btn);
            return true;
        }

        // 2. Target: Actions bar in pkp-header
        var $actionsTarget = $(
            '.pkpHeader__actions, ' +
            '.pkpWorkflow__header .pkpHeader__actions, ' +
            '.app__pageActions'
        ).filter(':visible').first();

        if ($actionsTarget.length) {
            $actionsTarget.prepend($btn);
            return true;
        }

        return false;
    }

    function openWithdrawModal(url) {
        var modalId = 'authorWithdrawModalDialog';
        var title = (window.pkpWithdrawLocales && window.pkpWithdrawLocales.modalTitle) || 'Withdraw Research Submission';

        $('#' + modalId).remove();
        $('.withdraw_native_modal_overlay').remove();

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.status === true && response.content) {
                    var $dialog = $(
                        '<div id="' + modalId + '" class="withdraw_native_modal_overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.65); z-index: 9999999; display: flex; align-items: center; justify-content: center;">' +
                        '<div class="withdraw_native_modal_dialog" style="background: #fff; width: 90%; max-width: 650px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); padding: 24px; position: relative; max-height: 90vh; overflow-y: auto;">' +
                        '<button type="button" class="withdraw_modal_close" style="position: absolute; top: 12px; right: 16px; background: none; border: none; font-size: 26px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>' +
                        '<h3 style="margin-top: 0; color: #1e293b; font-size: 1.2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">' + title + '</h3>' +
                        '<div class="withdraw_native_modal_body">' + response.content + '</div>' +
                        '</div></div>'
                    );

                    $('body').append($dialog);

                    $dialog.find('.withdraw_modal_close, .withdraw_btn_cancel').on('click', function(e) {
                        e.preventDefault();
                        $dialog.remove();
                    });
                } else {
                    alert((response && response.content) ? response.content : 'Failed to load withdrawal modal.');
                }
            },
            error: function(xhr, status, error) {
                alert('Error loading withdrawal modal: ' + (error || xhr.statusText));
            }
        });
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

    var count = 0;
    var timer = setInterval(function() {
        count++;
        var success = injectButton();
        if (success || count > 35) {
            clearInterval(timer);
        }
    }, 250);

    $(document).ajaxComplete(function() {
        injectButton();
    });

    window.addEventListener('popstate', function() {
        injectButton();
    });

})(jQuery);
