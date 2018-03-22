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

namespace Piwik\Plugins\HeatmapSessionRecording;

use Piwik\Access;
use Piwik\API\Request;
use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugins\HeatmapSessionRecording\Archiver\Aggregator;
use Piwik\Plugins\HeatmapSessionRecording\Dao\LogHsrEvent;
use Piwik\Plugins\HeatmapSessionRecording\Dao\LogHsr;
use Piwik\Plugins\HeatmapSessionRecording\Dao\SiteHsrDao;
use Piwik\Plugins\HeatmapSessionRecording\Input\Validator;
use Piwik\Plugins\HeatmapSessionRecording\Model\SiteHsrModel;
use Piwik\Plugins\HeatmapSessionRecording\Tracker\RequestProcessor;
use Piwik\Settings\Storage\Backend\PluginSettingsTable;
use Piwik\Tracker\PageUrl;
use Piwik\Url;
use Piwik\Container\StaticContainer;
use Piwik\Cookie;
use Piwik\Plugins\Login\SessionInitializer;

class Controller extends \Piwik\Plugin\Controller
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var SiteHsrModel
     */
    private $siteHsrModel;

    /**
     * @var SystemSettings
     */
    private $systemSettings;

    public function __construct(Validator $validator, SiteHsrModel $model, SystemSettings $settings)
    {
        parent::init();
        $this->validator = $validator;
        $this->siteHsrModel = $model;
        $this->systemSettings = $settings;
    }

    public function manageHeatmap()
    {
        $idSite = Common::getRequestVar('idSite');

        if (strtolower($idSite) === 'all') {
            // prevent fatal error... redirect to a specific site as it is not possible to manage for all sites
            Piwik::checkUserHasSomeAdminAccess();
            $this->redirectToIndex('HeatmapSessionRecording', 'manageHeatmap');
            exit;
        }

        $this->checkSitePermission();
        $this->validator->checkWritePermission($this->idSite);

        return $this->renderTemplate('manageHeatmap', array(
            'breakpointMobile' => (int) $this->systemSettings->breakpointMobile->getValue(),
            'breakpointTablet' => (int) $this->systemSettings->breakpointTablet->getValue()
        ));
    }

    public function manageSessions()
    {
        $idSite = Common::getRequestVar('idSite');

        if (strtolower($idSite) === 'all') {
            // prevent fatal error... redirect to a specific site as it is not possible to manage for all sites
            Piwik::checkUserHasSomeAdminAccess();
            $this->redirectToIndex('HeatmapSessionRecording', 'manageSessions');
            exit;
        }

        $this->checkSitePermission();
        $this->validator->checkWritePermission($this->idSite);

        return $this->renderTemplate('manageSessions');
    }

    public function replayRecording()
    {
        $this->validator->checkSessionReportViewPermission($this->idSite);

        $idLogHsr = Common::getRequestVar('idLogHsr', null, 'int');
        $idSiteHsr = Common::getRequestVar('idSiteHsr', null, 'int');

        $_GET['period'] = 'year'; // setting it randomly to not having to pass it in the URL
        $_GET['date'] = 'today'; // date is ignored anyway

        $recording = Request::processRequest('HeatmapSessionRecording.getRecordedSession', array(
            'idSite' => $this->idSite,
            'idLogHsr' => $idLogHsr,
            'idSiteHsr' => $idSiteHsr,
            'filter_limit' => '-1'
        ));

        $settings = $this->getPluginSettings();
        $settings = $settings->load();
        $skipPauses = !empty($settings['skip_pauses']);
        $autoPlayEnabled = !empty($settings['autoplay_pageviews']);
        $replaySpeed = !empty($settings['replay_speed']) ? (int) $settings['replay_speed'] : 1;

        return $this->renderTemplate('replayRecording', array(
            'idLogHsr' => $idLogHsr,
            'idSiteHsr' => $idSiteHsr,
            'recording' => $recording,
            'scrollAccuracy' => LogHsr::SCROLL_ACCURACY,
            'offsetAccuracy' => LogHsrEvent::OFFSET_ACCURACY,
            'autoPlayEnabled' => $autoPlayEnabled,
            'skipPausesEnabled' => $skipPauses,
            'replaySpeed' => $replaySpeed
        ));
    }

    private function getPluginSettings()
    {
        $login = Piwik::getCurrentUserLogin();

        $settings = new PluginSettingsTable('HeatmapSessionRecording', $login);
        return $settings;
    }

    public function saveSessionRecordingSettings()
    {
        Piwik::checkUserHasSomeViewAccess();

        $autoPlay = Common::getRequestVar('autoplay', '0', 'int');
        $replaySpeed = Common::getRequestVar('replayspeed', '1', 'int');
        $skipPauses = Common::getRequestVar('skippauses', '0', 'int');

        $settings = $this->getPluginSettings();
        $settings->save(array('autoplay_pageviews' => $autoPlay, 'replay_speed' => $replaySpeed, 'skip_pauses' => $skipPauses));
    }

    private function initHeatmapAuth()
    {
        $token_auth = Common::getRequestVar('token_auth', '', 'string');
        $authCookieName = 'heatmapEmbedPage';

        if (!empty($token_auth)) {
            $auth = StaticContainer::get('Piwik\Auth');
            $auth->setTokenAuth($token_auth);
            $auth->setPassword(null);
            $auth->setPasswordHash(null);
            $auth->setLogin(null);
            $sessionInitializer = new SessionInitializer($userAPI = null, $authCookieName, $halfDayInSeconds = 43200);
            $sessionInitializer->initSession($auth, $rememberMe = false);

            $url = preg_replace('/&token_auth=[^&]{20,38}|$/i', '', Url::getCurrentUrl());
            if ($url) {
                Url::redirectToUrl($url);
                return;
            }
        } else {
            $authCookie = new Cookie($authCookieName);
            if ($authCookie->isCookieFound()) {
                $auth = StaticContainer::get('Piwik\Auth');
                $auth->setLogin($authCookie->get('login'));
                $auth->setPassword(null);
                $auth->setPasswordHash(null);
                $auth->setTokenAuth($authCookie->get('token_auth'));
                $sessionInitializer = new SessionInitializer($userAPI = null, $authCookieName);

                try {
                    $sessionInitializer->initSession($auth, $rememberMe = false);
                    Access::getInstance()->reloadAccess($auth);
                } catch (\Exception $e) {
                    $authCookie->delete();// we really want to make sure the cookie will be deleted and not used the next time
                }
            }
        }
    }

    public function embedPage()
    {
        $this->initHeatmapAuth();

        $idLogHsr = Common::getRequestVar('idLogHsr', 0, 'int');
        $idSiteHsr = Common::getRequestVar('idSiteHsr', null, 'int');

        $_GET['period'] = 'year'; // setting it randomly to not having to pass it in the URL
        $_GET['date'] = 'today'; // date is ignored anyway

        if (empty($idLogHsr)) {
            $this->validator->checkHeatmapReportViewPermission($this->idSite);
            $this->siteHsrModel->checkHeatmapExists($this->idSite, $idSiteHsr);

            $heatmap = Request::processRequest('HeatmapSessionRecording.getHeatmap', array(
                'idSite' => $this->idSite,
                'idSiteHsr' => $idSiteHsr
            ));

            if (isset($heatmap[0])) {
                $heatmap = $heatmap[0];
            }

            $baseUrl = $heatmap['screenshot_url'];
            $initialMutation = $heatmap['page_treemirror'];
        } else {
            $this->validator->checkSessionReportViewPermission($this->idSite);
            $this->siteHsrModel->checkSessionRecordingExists($this->idSite, $idSiteHsr);

            // we don't use the API here for faster performance to get directly the info we need and not hundreds of other
            // info as well
            $aggregator = new Aggregator();
            $recording = $aggregator->getEmbedSessionInfo($this->idSite, $idSiteHsr, $idLogHsr);

            if (empty($recording)) {
                throw new \Exception(Piwik::translate('HeatmapSessionRecording_ErrorSessionRecordingDoesNotExist'));
            }

            $baseUrl = $recording['base_url'];
            $map = array_flip(PageUrl::$urlPrefixMap);

            if (isset($recording['url_prefix']) !== null && isset($map[$recording['url_prefix']])) {
                $baseUrl = $map[$recording['url_prefix']] . $baseUrl;
            }

            if (!empty($recording['initial_mutation'])) {
                $initialMutation = $recording['initial_mutation'];
            } else {
                $initialMutation = '';
            }
        }

        return $this->renderTemplate('embedPage', array(
            'idLogHsr' => $idLogHsr,
            'idSiteHsr' => $idSiteHsr,
            'initialMutation' => $initialMutation,
            'baseUrl' => $baseUrl
        ));
    }

    public function showHeatmap()
    {
        $this->validator->checkHeatmapReportViewPermission($this->idSite);

        $idSiteHsr = Common::getRequestVar('idSiteHsr', null, 'int');
        $heatmapType = Common::getRequestVar('heatmapType', RequestProcessor::EVENT_TYPE_CLICK, 'int');
        $deviceType = Common::getRequestVar('deviceType', LogHsr::DEVICE_TYPE_DESKTOP, 'int');

        $heatmap = Request::processRequest('HeatmapSessionRecording.getHeatmap', array(
            'idSite' => $this->idSite,
            'idSiteHsr' => $idSiteHsr
        ));

        if (isset($heatmap[0])) {
            $heatmap = $heatmap[0];
        }

        $requestDate = $this->siteHsrModel->getPiwikRequestDate($heatmap);
        $period = $requestDate['period'];
        $dateRange = $requestDate['date'];

        $metadata = Request::processRequest('HeatmapSessionRecording.getRecordedHeatmapMetadata', array(
            'idSite' => $this->idSite,
            'idSiteHsr' => $idSiteHsr,
            'period' => $period,
            'date' => $dateRange
        ));

        if (isset($metadata[0])) {
            $metadata = $metadata[0];
        }

        $editUrl = 'index.php' . Url::getCurrentQueryStringWithParametersModified(array(
                'module' => 'HeatmapSessionRecording',
                'action' => 'manageHeatmap'
            )) . '#?idSiteHsr=' . (int)$idSiteHsr;

        $reportDocumentation = '';
        if ($heatmap['status'] == SiteHsrDao::STATUS_ACTIVE) {
            $reportDocumentation = Piwik::translate('HeatmapSessionRecording_RecordedHeatmapDocStatusActive', array($heatmap['sample_limit'], $heatmap['sample_rate'] . '%'));
        } elseif ($heatmap['status'] == SiteHsrDao::STATUS_ENDED) {
            $reportDocumentation = Piwik::translate('HeatmapSessionRecording_RecordedHeatmapDocStatusEnded');
        }

        return $this->renderTemplate('showHeatmap', array(
            'idSiteHsr' => $idSiteHsr,
            'editUrl' => $editUrl,
            'heatmapType' => $heatmapType,
            'deviceType' => $deviceType,
            'heatmapPeriod' => $period,
            'heatmapDate' => $dateRange,
            'heatmap' => $heatmap,
            'heatmapMetadata' => $metadata,
            'reportDocumentation' => $reportDocumentation,
            'isScroll' => $heatmapType == RequestProcessor::EVENT_TYPE_SCROLL,
            'offsetAccuracy' => LogHsrEvent::OFFSET_ACCURACY,
            'heatmapTypes' => API::getInstance()->getAvailableHeatmapTypes(),
            'deviceTypes' => API::getInstance()->getAvailableDeviceTypes(),
        ));
    }
}
