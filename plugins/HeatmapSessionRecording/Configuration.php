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

use Piwik\Config;

class Configuration
{
    const DEFAULT_OPTIMIZE_TRACKING_CODE = 1;
    const DEFAULT_SESSION_RECORDING_SAMPLE_LIMITS = '50,100,250,500,1000,2000,5000';
    const KEY_OPTIMIZE_TRACKING_CODE = 'add_tracking_code_only_when_needed';
    const KEY_SESSION_RECORDING_SAMPLE_LIMITS = 'session_recording_sample_limits';

    public function install()
    {
        $config = $this->getConfig();
        $config->HeatmapSessionRecording = array(
            self::KEY_OPTIMIZE_TRACKING_CODE => self::DEFAULT_OPTIMIZE_TRACKING_CODE,
            self::KEY_SESSION_RECORDING_SAMPLE_LIMITS => self::DEFAULT_SESSION_RECORDING_SAMPLE_LIMITS
        );
        $config->forceSave();
    }

    public function uninstall()
    {
        $config = $this->getConfig();
        $config->HeatmapSessionRecording = array();
        $config->forceSave();
    }

    public function shouldOptimizeTrackingCode()
    {
        $value = $this->getConfigValue(self::KEY_OPTIMIZE_TRACKING_CODE, self::DEFAULT_OPTIMIZE_TRACKING_CODE);

        return !empty($value);
    }

    public function getSessionRecordingSampleLimits()
    {
        $value = $this->getConfigValue(self::KEY_SESSION_RECORDING_SAMPLE_LIMITS, self::DEFAULT_SESSION_RECORDING_SAMPLE_LIMITS);

        if (empty($value)) {
            $value = self::DEFAULT_SESSION_RECORDING_SAMPLE_LIMITS;
        }

        $value = explode(',', $value);
        $value = array_filter($value, function ($val) { return !empty($val); });
        $value = array_map(function ($val) { return intval(trim($val)); }, $value);
        natsort($value);

        if (empty($value)) {
            // just a fallback in case config is completely misconfigured
            $value = explode(',', self::DEFAULT_SESSION_RECORDING_SAMPLE_LIMITS);
        }

        return array_values($value);
    }

    private function getConfig()
    {
        return Config::getInstance();
    }

    private function getConfigValue($name, $default)
    {
        $config = $this->getConfig();
        $values = $config->HeatmapSessionRecording;
        if (isset($values[$name])) {
            return $values[$name];
        }
        return $default;
    }
}
