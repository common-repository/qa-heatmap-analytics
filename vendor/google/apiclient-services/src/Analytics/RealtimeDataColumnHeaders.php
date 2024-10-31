<?php

/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */
namespace QAAnalyticsVendor\Google\Service\Analytics;

class RealtimeDataColumnHeaders extends \QAAnalyticsVendor\Google\Model
{
    /**
     * @var string
     */
    public $columnType;
    /**
     * @var string
     */
    public $dataType;
    /**
     * @var string
     */
    public $name;
    /**
     * @param string
     */
    public function setColumnType($columnType)
    {
        $this->columnType = $columnType;
    }
    /**
     * @return string
     */
    public function getColumnType()
    {
        return $this->columnType;
    }
    /**
     * @param string
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }
    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }
    /**
     * @param string
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
\class_alias(RealtimeDataColumnHeaders::class, 'QAAnalyticsVendor\\Google_Service_Analytics_RealtimeDataColumnHeaders');
