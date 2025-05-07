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

namespace Google\Service\Eventarc;

class GoogleCloudEventarcV1PipelineMediation extends \Google\Model
{
  protected $transformationType = GoogleCloudEventarcV1PipelineMediationTransformation::class;
  protected $transformationDataType = '';

  /**
   * @param GoogleCloudEventarcV1PipelineMediationTransformation
   */
  public function setTransformation(GoogleCloudEventarcV1PipelineMediationTransformation $transformation)
  {
    $this->transformation = $transformation;
  }
  /**
   * @return GoogleCloudEventarcV1PipelineMediationTransformation
   */
  public function getTransformation()
  {
    return $this->transformation;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudEventarcV1PipelineMediation::class, 'Google_Service_Eventarc_GoogleCloudEventarcV1PipelineMediation');
