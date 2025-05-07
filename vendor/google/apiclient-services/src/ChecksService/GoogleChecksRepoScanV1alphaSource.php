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

namespace Google\Service\ChecksService;

class GoogleChecksRepoScanV1alphaSource extends \Google\Model
{
  protected $codeAttributionType = GoogleChecksRepoScanV1alphaCodeAttribution::class;
  protected $codeAttributionDataType = '';
  /**
   * @var string
   */
  public $dataType;
  /**
   * @var bool
   */
  public $falsePositive;

  /**
   * @param GoogleChecksRepoScanV1alphaCodeAttribution
   */
  public function setCodeAttribution(GoogleChecksRepoScanV1alphaCodeAttribution $codeAttribution)
  {
    $this->codeAttribution = $codeAttribution;
  }
  /**
   * @return GoogleChecksRepoScanV1alphaCodeAttribution
   */
  public function getCodeAttribution()
  {
    return $this->codeAttribution;
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
   * @param bool
   */
  public function setFalsePositive($falsePositive)
  {
    $this->falsePositive = $falsePositive;
  }
  /**
   * @return bool
   */
  public function getFalsePositive()
  {
    return $this->falsePositive;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleChecksRepoScanV1alphaSource::class, 'Google_Service_ChecksService_GoogleChecksRepoScanV1alphaSource');
