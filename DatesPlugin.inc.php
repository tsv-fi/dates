<?php

/**
 * @file plugins/generic/dates/DatesPlugin.inc.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Dates Plugin for OJS 3.5
 *
 * Adds publication and editorial workflow dates
 * to the public article page.
 */

use APP\core\Application;
use APP\decision\Decision;
use APP\facades\Repo;

use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class DatesPlugin extends GenericPlugin
{
    /**
     * Register the plugin
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled($mainContextId)) {
            Hook::add(
                'Templates::Article::Details',
                [$this, 'addDates']
            );
        }

        return $success;
    }

    /**
     * Plugin display name
     */
    public function getDisplayName()
    {
        return __('plugins.generic.dates.displayName');
    }

    /**
     * Plugin description
     */
    public function getDescription()
    {
        return __('plugins.generic.dates.description');
    }

    /**
     * Safe PHP 8.1+ date formatting
     */
    protected function formatDate($dateString, $locale = 'en_US')
    {
        if (!$dateString) {
            return null;
        }

        $timestamp = strtotime($dateString);

        if (!$timestamp) {
            return null;
        }

        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::NONE
        );

        return $formatter->format($timestamp);
    }

    /**
     * Add article dates to template output
     */
    public function addDates($hookName, $args)
    {
        error_log('DATES PLUGIN HOOK FIRED');

        /**
         * VERY IMPORTANT:
         * For frontend template hooks:
         *
         * $args[1] = TemplateManager
         * $args[2] = output (by reference)
         */
        $templateMgr = $args[1];
        $output =& $args[2];

        $article = $templateMgr->getTemplateVars('article');
        $publication = $templateMgr->getTemplateVars('publication');

        if (!$article || !$publication) {
            error_log('DATES PLUGIN: article or publication missing');
            return false;
        }

        $request = Application::get()->getRequest();
        if (!$request) {
            return false;
        }

        $context = $request->getContext();
        if (!$context) {
            return false;
        }

        $dates = [];

        /**
         * Better locale handling for OJS
         */
        $locale = $context->getPrimaryLocale();
        if (!$locale) {
            $locale = 'en_US';
        }

        /**
         * Submitted date
         */
        $dateSubmitted = $article->getData('dateSubmitted');

        if ($dateSubmitted) {
            $dates['submitted'] = $this->formatDate(
                $dateSubmitted,
                $locale
            );
        }

        /**
         * Published date
         * IMPORTANT:
         * This comes from publication, not article
         */
        $datePublished = $publication->getData('datePublished');

        if ($datePublished) {
            $dates['published'] = $this->formatDate(
                $datePublished,
                $locale
            );
        }

        /**
         * Accepted date via editorial decisions
         */
        try {
            $decisions = Repo::decision()
                ->getCollector()
                ->filterBySubmissionIds([$article->getId()])
                ->getMany();

            foreach ($decisions as $decision) {
                if (
                    (int) $decision->getData('decision')
                    === (int) Decision::ACCEPT
                ) {
                    $dates['accepted'] = $this->formatDate(
                        $decision->getData('dateDecided'),
                        $locale
                    );
                    break;
                }
            }
        } catch (\Throwable $e) {
            /**
             * Never break article rendering because of plugin issues
             */
            error_log(
                'DatesPlugin decision lookup failed: '
                . $e->getMessage()
            );
        }

        /**
         * Debug log
         */
        error_log(
            'DATES PLUGIN DATA: ' . print_r($dates, true)
        );

        /**
         * Assign to Smarty
         */
        $templateMgr->assign(
            'datesPluginDates',
            $dates
        );

        /**
         * THIS is the crucial part:
         * append rendered template output
         */
        $output .= $templateMgr->fetch(
            $this->getTemplateResource('dates.tpl')
        );

        return false;
    }
}
