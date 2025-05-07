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

namespace Google\Service\Bigquery;

class ParquetOptions extends \Google\Model
{
  /**
   * @var bool
   */
  public $enableListInference;
  /**
   * @var bool
   */
  public $enumAsString;
  /**
   * @var string
   */
  public $mapTargetType;

  /**
   * @param bool
   */
  public function setEnableListInference($enableListInference)
  {
    $this->enableListInference = $enableListInference;
  }
  /**
   * @return bool
   */
  public function getEnableListInference()
  {
    return $this->enableListInference;
  }
  /**
   * @param bool
   */
  public function setEnumAsString($enumAsString)
  {
    $this->enumAsString = $enumAsString;
  }
  /**
   * @return bool
   */
  public function getEnumAsString()
  {
    return $this->enumAsString;
  }
  /**
   * @param string
   */
  public function setMapTargetType($mapTargetType)
  {
    $this->mapTargetType = $mapTargetType;
  }
  /**
   * @return string
   */
  public function getMapTargetType()
  {
    return $this->mapTargetType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ParquetOptions::class, 'Google_Service_Bigquery_ParquetOptions');
