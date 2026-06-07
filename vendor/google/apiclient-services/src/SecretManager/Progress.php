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

namespace Google\Service\SecretManager;

class Progress extends \Google\Model
{
  /**
   * Output only. Number of secret versions that have been successfully
   * processed so far.
   *
   * @var int
   */
  public $completedVersionCount;
  /**
   * Output only. Number of secret versions that failed to process.
   *
   * @var int
   */
  public $failedVersionCount;
  /**
   * Output only. Provides the total number of secret versions to be processed
   * by the operation.
   *
   * @var int
   */
  public $totalVersionCount;

  /**
   * Output only. Number of secret versions that have been successfully
   * processed so far.
   *
   * @param int $completedVersionCount
   */
  public function setCompletedVersionCount($completedVersionCount)
  {
    $this->completedVersionCount = $completedVersionCount;
  }
  /**
   * @return int
   */
  public function getCompletedVersionCount()
  {
    return $this->completedVersionCount;
  }
  /**
   * Output only. Number of secret versions that failed to process.
   *
   * @param int $failedVersionCount
   */
  public function setFailedVersionCount($failedVersionCount)
  {
    $this->failedVersionCount = $failedVersionCount;
  }
  /**
   * @return int
   */
  public function getFailedVersionCount()
  {
    return $this->failedVersionCount;
  }
  /**
   * Output only. Provides the total number of secret versions to be processed
   * by the operation.
   *
   * @param int $totalVersionCount
   */
  public function setTotalVersionCount($totalVersionCount)
  {
    $this->totalVersionCount = $totalVersionCount;
  }
  /**
   * @return int
   */
  public function getTotalVersionCount()
  {
    return $this->totalVersionCount;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Progress::class, 'Google_Service_SecretManager_Progress');
