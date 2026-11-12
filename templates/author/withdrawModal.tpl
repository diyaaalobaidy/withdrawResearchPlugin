{**
 * plugins/generic/withdrawResearchPlugin/templates/author/withdrawModal.tpl
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * Author modal dialog for submitting a manuscript withdrawal.
 *}
<div id="authorWithdrawModalContainer" class="pkp_author_withdraw_modal">
    <div class="withdraw_alert_banner withdraw_alert_warning">
        <span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
        <div class="withdraw_alert_text">
            <strong>{translate key="plugins.generic.withdrawResearchPlugin.author.warning"}</strong>
        </div>
    </div>

    {if $withdrawalPolicy}
        <div class="withdraw_policy_box">
            <h4>{translate key="plugins.generic.withdrawResearchPlugin.settings.withdrawalPolicy"}</h4>
            <div class="withdraw_policy_text">
                {$withdrawalPolicy|nl2br}
            </div>
        </div>
    {/if}

    <div class="withdraw_submission_summary">
        <div class="withdraw_sub_item">
            <span class="withdraw_label">{translate key="submission.submission"}:</span>
            <span class="withdraw_val">#{$submissionId} &mdash; {$submissionTitle|escape}</span>
        </div>
    </div>

    <form id="authorWithdrawForm" class="pkp_form" method="post" action="{$submitUrl}">
        {csrf}
        <input type="hidden" name="submissionId" value="{$submissionId}" />

        <div class="withdraw_form_group">
            <label for="withdrawalReason" class="withdraw_field_label">
                {translate key="plugins.generic.withdrawResearchPlugin.author.reasonLabel"}
                {if $requireReason}<span class="req">*</span>{/if}
            </label>
            <select id="withdrawalReason" name="withdrawalReason" class="pkp_select" {if $requireReason}required{/if}>
                <option value="">-- {translate key="plugins.generic.withdrawResearchPlugin.author.selectReason"} --</option>
                {foreach from=$reasons key=reasonKey item=reasonTitle}
                    <option value="{$reasonKey|escape}">{$reasonTitle|escape}</option>
                {/foreach}
            </select>
        </div>

        <div class="withdraw_form_group">
            <label for="withdrawalNote" class="withdraw_field_label">
                {translate key="plugins.generic.withdrawResearchPlugin.author.noteLabel"}
            </label>
            <textarea id="withdrawalNote" name="withdrawalNote" class="pkp_textarea" rows="4" placeholder="{translate key="plugins.generic.withdrawResearchPlugin.author.noteLabel"}"></textarea>
        </div>

        <div class="withdraw_form_group withdraw_confirm_group">
            <label class="withdraw_checkbox_label">
                <input type="checkbox" id="confirmWithdrawal" name="confirmWithdrawal" value="1" required />
                <span>{translate key="plugins.generic.withdrawResearchPlugin.author.confirmCheck"}</span>
            </label>
        </div>

        <div class="withdraw_modal_footer">
            <button type="button" class="pkp_button withdraw_btn_cancel" onclick="$.pkp.classes.Helper.modalHandler.cancelModal(this);">
                {translate key="plugins.generic.withdrawResearchPlugin.author.cancelButton"}
            </button>
            <button type="submit" id="submitWithdrawBtn" class="pkp_button pkp_button_danger withdraw_btn_confirm">
                <span class="fa fa-ban" aria-hidden="true"></span>
                {translate key="plugins.generic.withdrawResearchPlugin.author.confirmButton"}
            </button>
        </div>
    </form>
</div>
