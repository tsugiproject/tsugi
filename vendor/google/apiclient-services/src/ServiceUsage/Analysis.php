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

namespace Google\Service\ServiceUsage;

class Analysis extends \Google\Model
{
  protected $analysisDataType = '';
  /**
   * @var string
   */
  public $analysisType;
  /**
   * @var string
   */
  public $displayName;
  /**
   * @var string
   */
  public $service;

  /**
   * @param AnalysisResult
   */
  public function setAnalysis(AnalysisResult $analysis)
  {
    $this->analysis = $analysis;
  }
  /**
   * @return AnalysisResult
   */
  public function getAnalysis()
  {
    return $this->analysis;
  }
  /**
   * @param string
   */
  public function setAnalysisType($analysisType)
  {
    $this->analysisType = $analysisType;
  }
  /**
   * @return string
   */
  public function getAnalysisType()
  {
    return $this->analysisType;
  }
  /**
   * @param string
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * @param string
   */
  public function setService($service)
  {
    $this->service = $service;
  }
  /**
   * @return string
   */
  public function getService()
  {
    return $this->service;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Analysis::class, 'Google_Service_ServiceUsage_Analysis');
