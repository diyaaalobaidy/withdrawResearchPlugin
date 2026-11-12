{**
 * plugins/generic/withdrawResearchPlugin/templates/settings/settings.tpl
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * Journal manager settings modal template for Author Research Withdrawal Plugin (OJS 3.5).
 *}

<div class="pkp_withdraw_settings_modal">
    <form class="pkp_form" id="withdrawSettingsForm" method="post" action="{url op="manage" category="generic" plugin=$pluginName verb="save"}">
        {csrf}

        <div class="pkp_notification" style="margin-bottom: 20px; padding: 12px 16px; background: #f1f5f9; border-left: 4px solid #0284c7; border-radius: 4px;">
            <p class="pkp_notification_content" style="margin: 0; font-size: 13px; color: #334155;">
                {translate key="plugins.generic.withdrawResearchPlugin.description"}
            </p>
        </div>

        {* Section 1: Activation & Reason Requirement *}
        <div class="pkp_form_section" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
            <div style="margin-bottom: 12px;">
                <label style="display: flex; align-items: center; gap: 10px; font-weight: 600; cursor: pointer; color: #1e293b;">
                    <input type="hidden" name="enabled" value="0" />
                    <input type="checkbox" name="enabled" value="1" {if $enabled}checked="checked"{/if} style="width: 17px; height: 17px;" />
                    <span>{translate key="plugins.generic.withdrawResearchPlugin.settings.enabled"}</span>
                </label>
            </div>

            <div style="margin-bottom: 6px;">
                <label style="display: flex; align-items: center; gap: 10px; font-weight: 600; cursor: pointer; color: #1e293b;">
                    <input type="hidden" name="requireReason" value="0" />
                    <input type="checkbox" name="requireReason" value="1" {if $requireReason}checked="checked"{/if} style="width: 17px; height: 17px;" />
                    <span>{translate key="plugins.generic.withdrawResearchPlugin.settings.requireReason"}</span>
                </label>
                <div style="font-size: 12px; color: #64748b; margin-left: 27px; margin-top: 2px;">
                    {translate key="plugins.generic.withdrawResearchPlugin.settings.requireReasonDescription"}
                </div>
            </div>
        </div>

        {* Section 2: Allowed Stages *}
        <div class="pkp_form_section" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px; font-weight: 700; color: #334155;">
                {translate key="plugins.generic.withdrawResearchPlugin.settings.allowedStages"}
            </h4>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                {foreach from=$availableStages key=stageId item=stageName}
                    {assign var="isChecked" value=false}
                    {if in_array($stageId, $allowedStages)}
                        {assign var="isChecked" value=true}
                    {/if}
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #334155;">
                        <input type="checkbox" name="allowedStages[]" value="{$stageId}" {if $isChecked}checked="checked"{/if} style="width: 16px; height: 16px;" />
                        <span>{$stageName}</span>
                    </label>
                {/foreach}
            </div>
        </div>

        {* Section 3: Email Notifications *}
        <div class="pkp_form_section" style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
            <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px; font-weight: 700; color: #334155;">
                {translate key="common.notifications"}
            </h4>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #1e293b; font-size: 13px;">
                    <input type="hidden" name="notifyEditors" value="0" />
                    <input type="checkbox" name="notifyEditors" value="1" {if $notifyEditors}checked="checked"{/if} style="width: 17px; height: 17px;" />
                    <span>{translate key="plugins.generic.withdrawResearchPlugin.settings.notifyEditors"}</span>
                </label>

                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #1e293b; font-size: 13px;">
                    <input type="hidden" name="notifyAuthor" value="0" />
                    <input type="checkbox" name="notifyAuthor" value="1" {if $notifyAuthor}checked="checked"{/if} style="width: 17px; height: 17px;" />
                    <span>{translate key="plugins.generic.withdrawResearchPlugin.settings.notifyAuthor"}</span>
                </label>
            </div>
        </div>

        {* Section 4: Withdrawal Policy *}
        <div class="pkp_form_section" style="margin-bottom: 24px;">
            <label for="withdrawalPolicy" style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: #1e293b;">
                {translate key="plugins.generic.withdrawResearchPlugin.settings.withdrawalPolicy"}
            </label>
            <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                {translate key="plugins.generic.withdrawResearchPlugin.settings.withdrawalPolicyDescription"}
            </div>
            <textarea id="withdrawalPolicy" name="withdrawalPolicy" rows="5" class="pkp_input" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; resize: vertical;">{$withdrawalPolicy|escape}</textarea>
        </div>

        {* Modal Actions *}
        <div class="pkp_modal_footer" style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
            <button type="button" class="pkp_button" id="withdrawSettingsCancelBtn" style="padding: 8px 18px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; cursor: pointer;">
                {translate key="common.cancel"}
            </button>
            <button type="submit" class="pkp_button pkp_button_primary" style="padding: 8px 22px; background: #006699; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                {translate key="plugins.generic.withdrawResearchPlugin.settings.save"}
            </button>
        </div>
    </form>

    <script type="text/javascript">
        $(function() {
            var $form = $('#withdrawSettingsForm');

            $('#withdrawSettingsCancelBtn').on('click', function() {
                var modal = $form.closest('.ui-dialog-content');
                if (modal.length) {
                    modal.dialog('close');
                } else {
                    var panel = $form.closest('.pkp_modal_panel');
                    if (panel.length && window.pkp && pkp.classes && pkp.classes.Handler) {
                        pkp.classes.Handler.getHandler(panel).cancel();
                    }
                }
            });

            $form.on('submit', function(e) {
                e.preventDefault();
                var form = this;
                var formData = new FormData(form);
                var $btn = $form.find('button[type="submit"]');
                var origText = $btn.text();
                $btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $btn.prop('disabled', false).text(origText);
                        if (response && response.status === true) {
                            if (window.pkp && pkp.eventBus) {
                                pkp.eventBus.$emit('notify', '{translate key="common.saved"}', 'success');
                            }
                            var modal = $form.closest('.ui-dialog-content');
                            if (modal.length) {
                                modal.dialog('close');
                            } else {
                                var panel = $form.closest('.pkp_modal_panel');
                                if (panel.length && window.pkp && pkp.classes && pkp.classes.Handler) {
                                    pkp.classes.Handler.getHandler(panel).cancel();
                                }
                            }
                        } else {
                            var msg = (response && response.content) ? response.content : 'Failed to save settings.';
                            alert(msg);
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).text(origText);
                        alert('Error saving settings: ' + (xhr.responseText || xhr.statusText));
                    }
                });
            });
        });
    </script>
</div>
