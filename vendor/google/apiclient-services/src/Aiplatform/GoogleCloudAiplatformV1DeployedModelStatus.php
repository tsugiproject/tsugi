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

class GoogleCloudAiplatformV1DeployedModelStatus extends \Google\Model
{
  /**
   * @var int
   */
  public $availableReplicaCount;
  /**
   * @var string
   */
  public $lastUpdateTime;
  /**
   * @var string
   */
  public $message;

  /**
   * @param int
   */
  public function setAvailableReplicaCount($availableReplicaCount)
  {
    $this->availableReplicaCount = $availableReplicaCount;
  }
  /**
   * @return int
   */
  public function getAvailableReplicaCount()
  {
    return $this->availableReplicaCount;
  }
  /**
   * @param string
   */
  public function setLastUpdateTime($lastUpdateTime)
  {
    $this->lastUpdateTime = $lastUpdateTime;
  }
  /**
   * @return string
   */
  public function getLastUpdateTime()
  {
    return $this->lastUpdateTime;
  }
  /**
   * @param string
   */
  public function setMessage($message)
  {
    $this->message = $message;
  }
  /**
   * @return string
   */
  public function getMessage()
  {
    return $this->message;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1DeployedModelStatus::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1DeployedModelStatus');
