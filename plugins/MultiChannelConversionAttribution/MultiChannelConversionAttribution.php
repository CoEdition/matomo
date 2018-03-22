<?php
/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */

namespace Piwik\Plugins\MultiChannelConversionAttribution;

use Piwik\API\Request;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugins\MultiChannelConversionAttribution\Dao\GoalAttributionDao;

class MultiChannelConversionAttribution extends \Piwik\Plugin
{
    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        $hooks = array(
            'Template.beforeGoalListActionsHead' => 'printGoalListHead',
            'Template.beforeGoalListActionsBody' => 'printGoalListBody',
            'Template.endGoalEditTable' => 'printGoalEdit',
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'API.Goals.addGoal.end' => 'setAttributionFromAddGoal',
            'API.Goals.updateGoal.end' => 'setAttributionFromGoalUpdate',
            'API.Goals.deleteGoal.end' => 'onDeleteGoal',
            'SitesManager.deleteSite.end' => 'onDeleteSite',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Metrics.getDefaultMetricTranslations' => 'getDefaultMetricTranslations',
            'Metrics.getDefaultMetricDocumentationTranslations' => 'getDefaultMetricDocumentationTranslations'
        );
        return $hooks;
    }

    public function install()
    {
        $configuration = new Configuration();
        $configuration->install();
        
        $attributionDao = new GoalAttributionDao();
        $attributionDao->install();
    }

    public function activate()
    {
        if (Plugin\Manager::getInstance()->isPluginActivated('Goals') && Piwik::hasUserSuperUserAccess()) {
            try {
                $goals = Request::processRequest('Goals.getGoals', array('idSite' => 'all'));
                if (count($goals) <= 50) {
                    $attributionDao = new GoalAttributionDao();

                    foreach ($goals as $goal) {
                        $attributionDao->addGoalAttribution($goal['idsite'], $goal['idgoal']);
                    }
                }
            } catch (\Exception $e) {

            }
        }
    }

    public function uninstall()
    {
        $configuration = new Configuration();
        $configuration->uninstall();

        $attributionDao = new GoalAttributionDao();
        $attributionDao->uninstall();
    }

    private function getDao()
    {
        return StaticContainer::get('Piwik\Plugins\MultiChannelConversionAttribution\Dao\GoalAttributionDao');
    }

    private function getValidator()
    {
        return StaticContainer::get('Piwik\Plugins\MultiChannelConversionAttribution\Input\Validator');
    }

    public function printGoalListHead(&$out)
    {
        $out .= '<th>' . Piwik::translate('MultiChannelConversionAttribution_Attribution') . '</th>';
    }

    public function setAttributionFromAddGoal($returnedValue, $info)
    {
        if ($returnedValue) {
            $idGoal = $returnedValue;
            $finalParameters = $info['parameters'];
            $idSite = $finalParameters['idSite'];

            $this->setAttribution($idSite, $idGoal);
        }
    }

    public function setAttributionFromGoalUpdate($value, $info)
    {
        if (empty($info['parameters'])) {
            return;
        }

        $finalParameters = $info['parameters'];
        $idSite = $finalParameters['idSite'];
        $idGoal = $finalParameters['idGoal'];

        $this->setAttribution($idSite, $idGoal);
    }

    private function setAttribution($idSite, $idGoal)
    {
        if (!isset($_POST['multiAttributionEnabled'])) {
            // no value was set, we should not change anything
            return;
        }

        $isEnabled = Common::getRequestVar('multiAttributionEnabled', 0, 'int');

        Request::processRequest('MultiChannelConversionAttribution.setGoalAttribution', array(
            'idSite' => $idSite,
            'idGoal' => $idGoal,
            'isEnabled' => $isEnabled
        ));
    }

    public function printGoalListBody(&$out, $goal)
    {
        $dao = $this->getDao();

        $isEnabled = $dao->isAttributionEnabled($goal['idsite'], $goal['idgoal']);

        $out .= '<td>';

        if (!empty($isEnabled)) {
            $message = Piwik::translate('MultiChannelConversionAttribution_MultiAttributionEnabledForGoal');
            $message = htmlentities($message);
            $out .= '<span title="' . $message . '" class="icon-ok multiAttributionActivated"></span>';
        } else {
            $out .= '-';
        }

        $out .= '</td>';
    }

    public function getDefaultMetricTranslations(&$translations)
    {
        $translations = array_merge($translations, Metrics::getMetricsTranslations());
    }

    public function getDefaultMetricDocumentationTranslations(&$translations)
    {
        $translations = array_merge($translations, Metrics::getMetricsDocumentationTranslations());
    }

    public function printGoalEdit(&$out)
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');

        if (!$this->getValidator()->canWrite($idSite)) {
            return;
        }

        $out .= '<div piwik-manage-multiattribution></div>';
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/MultiChannelConversionAttribution/angularjs/manage-attribution/manage-attribution.directive.js";
        $jsFiles[] = "plugins/MultiChannelConversionAttribution/angularjs/report-attribution/manage-attribution.directive.js";
        $jsFiles[] = "plugins/MultiChannelConversionAttribution/javascripts/attributionDataTable.js";
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/MultiChannelConversionAttribution/angularjs/manage-attribution/manage-attribution.directive.less";
        $stylesheets[] = "plugins/MultiChannelConversionAttribution/angularjs/report-attribution/report-attribution.directive.less";
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'MultiChannelConversionAttribution_Introduction';
        $translationKeys[] = 'MultiChannelConversionAttribution_Enabled';
        $translationKeys[] = 'MultiChannelConversionAttribution_MultiChannelConversionAttribution';
    }

    public function onDeleteSite($idSite)
    {
        $dao = $this->getDao();
        $dao->removeSiteAttributions($idSite);
    }

    public function onDeleteGoal($value, $info)
    {
        if (empty($info['parameters'])) {
            return;
        }

        $finalParameters = $info['parameters'];

        $idSite = $finalParameters['idSite'];
        $idGoal = $finalParameters['idGoal'];

        $goal = Request::processRequest('Goals.getGoal', array('idSite' => $idSite, 'idGoal' => $idGoal));

        if (empty($goal['idgoal'])) {
            // we only delete attribution if that goal was actually deleted
            // we check for idgoal because API might return true even though goal does not exist
            $dao = $this->getDao();
            $dao->removeGoalAttribution($idSite, $idGoal);
        }
    }

}
