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

namespace Google\Service\Aiplatform;

class CloudAiLargeModelsVisionGenerateVideoResponse extends \Google\Collection
{
  protected $collection_key = 'raiMediaFilteredReasons';
  protected $generatedSamplesType = CloudAiLargeModelsVisionMedia::class;
  protected $generatedSamplesDataType = 'array';
  /**
   * @var int
   */
  public $raiMediaFilteredCount;
  /**
   * @var string[]
   */
  public $raiMediaFilteredReasons;

  /**
   * @param CloudAiLargeModelsVisionMedia[]
   */
  public function setGeneratedSamples($generatedSamples)
  {
    $this->generatedSamples = $generatedSamples;
  }
  /**
   * @return CloudAiLargeModelsVisionMedia[]
   */
  public function getGeneratedSamples()
  {
    return $this->generatedSamples;
  }
  /**
   * @param int
   */
  public function setRaiMediaFilteredCount($raiMediaFilteredCount)
  {
    $this->raiMediaFilteredCount = $raiMediaFilteredCount;
  }
  /**
   * @return int
   */
  public function getRaiMediaFilteredCount()
  {
    return $this->raiMediaFilteredCount;
  }
  /**
   * @param string[]
   */
  public function setRaiMediaFilteredReasons($raiMediaFilteredReasons)
  {
    $this->raiMediaFilteredReasons = $raiMediaFilteredReasons;
  }
  /**
   * @return string[]
   */
  public function getRaiMediaFilteredReasons()
  {
    return $this->raiMediaFilteredReasons;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CloudAiLargeModelsVisionGenerateVideoResponse::class, 'Google_Service_Aiplatform_CloudAiLargeModelsVisionGenerateVideoResponse');
