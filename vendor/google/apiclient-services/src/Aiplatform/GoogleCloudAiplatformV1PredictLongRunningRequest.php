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

class GoogleCloudAiplatformV1PredictLongRunningRequest extends \Google\Collection
{
  protected $collection_key = 'instances';
  /**
   * @var array[]
   */
  public $instances;
  /**
   * @var array
   */
  public $parameters;

  /**
   * @param array[]
   */
  public function setInstances($instances)
  {
    $this->instances = $instances;
  }
  /**
   * @return array[]
   */
  public function getInstances()
  {
    return $this->instances;
  }
  /**
   * @param array
   */
  public function setParameters($parameters)
  {
    $this->parameters = $parameters;
  }
  /**
   * @return array
   */
  public function getParameters()
  {
    return $this->parameters;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1PredictLongRunningRequest::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1PredictLongRunningRequest');
