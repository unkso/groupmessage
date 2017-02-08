<fieldset>
	<legend>{lang}wcf.acp.group.groupmessage{/lang}</legend>
	
	<dl{if $errorType.messagingAlias|isset} class="formError"{/if}>
		<dt><label for="messagingAlias">{lang}wcf.acp.group.messagingAlias{/lang}</label></dt>
		<dd>
			<input type="text" id="messagingAlias" name="messagingAlias" value="{$group->messagingAlias}" class="long" />
			{if $errorType.messagingAlias|isset}
				<small class="innerError">
					{lang}wcf.acp.group.messagingAlias.error.{@$errorType.messagingAlias}{/lang}
				</small>
			{/if}
			<small>{lang}wcf.acp.group.messagingAlias.description{/lang}</small>
		</dd>
	</dl>
	
	<dl>
		<dt></dt>
		<dd>
			<label><input type="checkbox" id="canBeMessaged" name="canBeMessaged" value="1" {if $group->canBeMessaged}checked="checked" {/if} /> {lang}wcf.acp.group.canBeMessaged{/lang}</label>
		</dd>
	</dl>
</fieldset>