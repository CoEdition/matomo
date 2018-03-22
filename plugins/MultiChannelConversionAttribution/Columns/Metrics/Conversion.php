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
namespace Piwik\Plugins\MultiChannelConversionAttribution\Columns\Metrics;

use Piwik\Plugin\ProcessedMetric;
use Piwik\Metrics\Formatter;
use Piwik\DataTable\Row;
use Piwik\Piwik;
use Piwik\Plugins\MultiChannelConversionAttribution\Models\Base;

class Conversion extends ProcessedMetric
{
    /**
     * @var string
     */
    private $metric;

    /**
     * @var string
     */
    private $attributionModelName;

    public function __construct($metric, Base $model)
    {
        $this->metric = $metric;
        $this->attributionModelName = $model->getName();
    }

    public function getName()
    {
        return $this->metric;
    }

    public function getDocumentation()
    {
        return Piwik::translate('MultiChannelConversionAttribution_ColumnConversionsDocumentation', array('"' . $this->attributionModelName . '"'));
    }

    public function getTranslatedName()
    {
        // in the html table UI we don't show full metric name
        return Piwik::translate('Goals_ColumnConversions');
    }

    public function compute(Row $row)
    {
        return $row->getColumn($this->metric);
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyNumber($value, 1);
    }

    public function getDependentMetrics()
    {
        return array($this->metric);
    }

}