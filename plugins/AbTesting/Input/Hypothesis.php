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

namespace Piwik\Plugins\AbTesting\Input;

use \Exception;
use Piwik\Common;
use Piwik\Piwik;

class Hypothesis
{
    const MAX_LENGTH = 1000;

    /**
     * @var string
     */
    private $hypothesis;

    public function __construct($hypothesis)
    {
        $this->hypothesis = $hypothesis;
    }

    public function check()
    {
        if (empty($this->hypothesis)) {
            $title = Piwik::translate('AbTesting_Hypothesis');
            throw new Exception(Piwik::translate('AbTesting_ErrorXNotProvided', $title));
        }

        if (Common::mb_strlen($this->hypothesis) > self::MAX_LENGTH) {
            $title = Piwik::translate('AbTesting_Hypothesis');
            throw new Exception(Piwik::translate('AbTesting_ErrorXTooLong', array($title, static::MAX_LENGTH)));
        }

    }

}