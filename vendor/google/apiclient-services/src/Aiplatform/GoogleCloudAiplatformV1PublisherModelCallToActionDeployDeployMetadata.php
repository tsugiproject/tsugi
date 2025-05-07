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

class GoogleCloudAiplatformV1PublisherModelCallToActionDeployDeployMetadata extends \Google\Model
{
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $sampleRequest;

  /**
   * @param string[]
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
  }
  /**
   * @param string
   */
  public function setSampleRequest($sampleRequest)
  {
    $this->sampleRequest = $sampleRequest;
  }
  /**
   * @return string
   */
  public function getSampleRequest()
  {
    return $this->sampleRequest;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1PublisherModelCallToActionDeployDeployMetadata::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1PublisherModelCallToActionDeployDeployMetadata');
