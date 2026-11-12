{**
 * plugins/generic/withdrawResearchPlugin/templates/settings/settings.tpl
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * Journal manager settings modal template for Author Research Withdrawal Plugin.
 *}
<script>
    $(function() {
        $('#withdrawSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    });
</script>

<form class="pkp_form" id="withdrawSettingsForm" method="post" action="{url router=$smarty.const.PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="save"}">
    {csrf}

    <div class="pkp_notification">
        <p class="pkp_notification_content">
            {translate key="plugins.generic.withdrawResearchPlugin.description"}
        </p>
    </div>

    {fbvFormArea id="withdrawSettingsArea"}
        {fbvFormSection list="true"}
            {fbvElement type="checkbox" id="enabled" name="enabled" value="1" checked=$enabled label="plugins.generic.withdrawResearchPlugin.settings.enabled"}
            {fbvElement type="checkbox" id="requireReason" name="requireReason" value="1" checked=$requireReason label="plugins.generic.withdrawResearchPlugin.settings.requireReason"}
            <span class="instruct">{translate key="plugins.generic.withdrawResearchPlugin.settings.requireReasonDescription"}</span>
        {/fbvFormSection}

        {fbvFormSection list="true" title="plugins.generic.withdrawResearchPlugin.settings.allowedStages"}
            {foreach from=$availableStages key=stageId item=stageName}
                {assign var="isChecked" value=false}
                {if in_array($stageId, $allowedStages)}
                    {assign var="isChecked" value=true}
                {/if}
                {fbvElement type="checkbox" id="stage_"|cat:$stageId name="allowedStages[]" value=$stageId checked=$isChecked label=$stageName translate=false}
            {/foreach}
        {/fbvFormSection}

        {fbvFormSection list="true" title="common.notifications"}
            {fbvElement type="checkbox" id="notifyEditors" name="notifyEditors" value="1" checked=$notifyEditors label="plugins.generic.withdrawResearchPlugin.settings.notifyEditors"}
            {fbvElement type="checkbox" id="notifyAuthor" name="notifyAuthor" value="1" checked=$notifyAuthor label="plugins.generic.withdrawResearchPlugin.settings.notifyAuthor"}
        {/fbvFormSection}

        {fbvFormSection title="plugins.generic.withdrawResearchPlugin.settings.withdrawalPolicy"}
            {fbvElement type="textarea" id="withdrawalPolicy" name="withdrawalPolicy" value=$withdrawalPolicy rich=false size="medium"}
            <span class="instruct">{translate key="plugins.generic.withdrawResearchPlugin.settings.withdrawalPolicyDescription"}</span>
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormButtons id="withdrawSettingsButtons" submitText="plugins.generic.withdrawResearchPlugin.settings.save"}
</form>
