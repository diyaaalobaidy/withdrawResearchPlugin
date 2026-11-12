{**
 * plugins/generic/withdrawResearchPlugin/templates/author/workflowAction.tpl
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * Direct workflow template hook injection for author withdrawal button and notice.
 *}

{if $isSubmissionWithdrawn}
    <div class="withdraw_withdrawn_status_banner" style="margin: 15px 0; padding: 12px 18px; background: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #e11d48; border-radius: 4px; color: #9f1239; font-size: 14px; display: flex; align-items: center; gap: 10px;">
        <span class="fa fa-info-circle" style="font-size: 18px;" aria-hidden="true"></span>
        <div>
            <strong>{translate key="plugins.generic.withdrawResearchPlugin.status.withdrawnBadge"}:</strong>
            <span>
                {translate key="plugins.generic.withdrawResearchPlugin.status.withdrawnNotice" date=$submissionWithdrawalDate reason=$submissionWithdrawalReason}
            </span>
        </div>
    </div>
{elseif $canWithdrawSubmission}
    <div id="pkpAuthorWithdrawalSection" class="pkp_author_withdrawal_section" style="margin: 15px 0; padding: 12px 16px; background: #fff7ed; border: 1px solid #ffedd5; border-left: 4px solid #f97316; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="font-size: 13px; color: #9a3412;">
            <strong>{translate key="plugins.generic.withdrawResearchPlugin.displayName"}:</strong>
            <span>{translate key="plugins.generic.withdrawResearchPlugin.author.actionDesc" default="Need to withdraw this manuscript from editorial review?"}</span>
        </div>
        <button type="button" id="btnAuthorWithdrawDirect" class="pkp_button pkp_button_danger" style="background-color: #dc2626 !important; border-color: #b91c1c !important; color: #fff !important; font-weight: 600; padding: 6px 14px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <span class="fa fa-ban" aria-hidden="true"></span>
            {translate key="plugins.generic.withdrawResearchPlugin.author.button"}
        </button>
    </div>

    <script type="text/javascript">
        $(function() {
            $('#btnAuthorWithdrawDirect').on('click', function(e) {
                e.preventDefault();
                if (typeof openWithdrawModal === 'function') {
                    openWithdrawModal();
                } else {
                    var modalUrl = "{$withdrawModalUrl|escape:javascript}";
                    var title = "{translate key="plugins.generic.withdrawResearchPlugin.author.modalTitle"}";
                    if ($.pkp && $.pkp.controllers && $.pkp.controllers.modal && $.pkp.controllers.modal.AjaxModalHandler) {
                        var $modal = $('<div id="authorWithdrawModalDialog" class="pkp_modal pkpModal"></div>');
                        $('body').append($modal);
                        $modal.pkpHandler('$.pkp.controllers.modal.AjaxModalHandler', {
                            title: title,
                            url: modalUrl,
                            canClose: true
                        });
                    } else {
                        window.location.href = modalUrl;
                    }
                }
            });
        });
    </script>
{/if}
