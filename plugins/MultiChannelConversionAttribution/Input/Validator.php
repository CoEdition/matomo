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
namespace Piwik\Plugins\MultiChannelConversionAttribution\Input;

use Piwik\Piwik;
use Exception;
use Piwik\Plugins\MultiChannelConversionAttribution\Configuration;

class Validator
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function checkWritePermission($idSite)
    {
        Piwik::checkUserHasAdminAccess($idSite);
    }

    public function checkReportViewPermission($idSite)
    {
        Piwik::checkUserHasViewAccess($idSite);
    }

    public function canWrite($idSite)
    {
        if (empty($idSite)) {
            return false;
        }

        return Piwik::isUserHasAdminAccess($idSite);
    }

    public function checkAttributionConfiguration($isEnabled)
    {
        if (!in_array($isEnabled, array(0, 1, true, false, '0', '1'), true)) {
            $message = Piwik::translate('MultiChannelConversionAttribution_ErrorXNotWhitelisted', array('isEnabled', '0, 1'));
            throw new Exception($message);
        }
    }

    public function checkValidDaysPriorToConversion($day)
    {
        if (empty($day)) {
            return; // we will use default
        }

        $allowed = $this->configuration->getDaysPriorToConversion();

        if (!in_array($day, $allowed)) {
            $message = Piwik::translate('MultiChannelConversionAttribution_ErrorXNotWhitelisted', array('daysPriorToConversion', implode(', ', $allowed)));
            throw new Exception($message);
        }
    }

}

