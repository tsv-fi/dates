<?php

/**
 * @file plugins/generic/dates/DatesPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class dates
 * @ingroup plugins_generic_dates
 *
 * @brief dates plugin class
 */

use APP\decision\Decision;
use APP\facades\Repo;
use PKP\plugins\GenericPlugin;

class DatesPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			HookRegistry::register('Templates::Article::Details', array($this, 'addDates'));
		}
		return $success;
	}

	/**
	 * Get the plugin display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.dates.displayName');
	}

	/**
	 * Get the plugin description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.dates.description');
	}

	/**
	 * Add dates to article landing page
	 * @param $hookName string
	 * @param $params array
	 */
	function addDates($hookName, $params) {
		$request = $this->getRequest();
		$context = $request->getContext();

		$smarty = $params[1];
		$output =& $params[2];

		$article = $smarty->getTemplateVars('article');

		$dates = "";
		$submitdate = $article->getDateSubmitted();
		$publishdate = $article->getDatePublished();
		$reviewdate = "";

        $decisions = Repo::decision()->getCollector()
                ->filterBySubmissionIds([$article->getId()])
                ->getMany();

		// Loop through the decisions
		foreach ($decisions as $decision) {
			// If we have a review stage decision and it was a submission accepted decision, get to date for the decision
			if ($decision->getData('stageId') == '1' && $decision->getData('decision') == '17'){
				$reviewdate = $decision->getData('dateDecided'); // Take the date when the editor made the decision to accept the article without peer review progress
			} elseif ($decision->getData('stageId') == '3' && $decision->getData('decision') == '2'){
				$reviewdate = $decision->getData('dateDecided'); // Take the date when the editor made the decision to accept the article with peer review progress
			} elseif ($decision->getData('stageId') == '3' && $decision->getData('decision') == '1'){
				$reviewdate = $decision->getData('dateDecided'); // Take the date when the article published with previous version of OJS (Before OJS 3.4.0.5)
			}
		}

		$dates = array();
		if ($submitdate)
			$dates['received'] = date(Config::getVar('general', 'date_format_short'),strtotime($submitdate));
		if ($reviewdate)
			$dates['accepted'] = date(Config::getVar('general', 'date_format_short'),strtotime($reviewdate));
		if ($publishdate)
			$dates['published'] = date(Config::getVar('general', 'date_format_short'),strtotime($publishdate));

		// Only show dates if there was a review
		if ($reviewdate){
			$smarty->assign('dates', $dates);
			$output .= $smarty->fetch($this->getTemplateResource('dates.tpl'));
		}
		return false;
	}
}

?>
