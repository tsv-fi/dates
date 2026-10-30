{**
 * plugins/generic/dates/templates/articleFooter.tpl
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 *}

{if $datesPluginDates}
<div class="item">
		{if $datesPluginDates.submitted}
     		<div class="value">
				<strong>Submitted:</strong> {$datesPluginDates.submitted}
			</div>
		{/if}
	
		{if $datesPluginDates.accepted}
     		<div class="value">
				<strong>Accepted:</strong> {$datesPluginDates.accepted}
			</div>
		{/if}
	
		{if $datesPluginDates.published}
     		<div class="value">
				<strong>Published:</strong> {$datesPluginDates.published}
			</div>
		{/if}
</div>
{/if}
