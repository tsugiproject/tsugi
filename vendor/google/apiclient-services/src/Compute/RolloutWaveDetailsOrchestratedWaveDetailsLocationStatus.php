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

namespace Google\Service\Compute;

class RolloutWaveDetailsOrchestratedWaveDetailsLocationStatus extends \Google\Model
{
  /**
   * Work on the wave failed.
   */
  public const STATE_STATE_FAILED = 'STATE_FAILED';
  /**
   * Work on the wave is in progress.
   */
  public const STATE_STATE_IN_PROGRESS = 'STATE_IN_PROGRESS';
  /**
   * Work on the wave is pending.
   */
  public const STATE_STATE_PENDING = 'STATE_PENDING';
  /**
   * Work on the wave was canceled or skipped.
   */
  public const STATE_STATE_SKIPPED = 'STATE_SKIPPED';
  /**
   * Work on the wave succeeded.
   */
  public const STATE_STATE_SUCCEEDED = 'STATE_SUCCEEDED';
  /**
   * Undefined default state. Should never be exposed to users.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * Output only. Location state of the wave.
   *
   * @var string
   */
  public $state;

  /**
   * Output only. Location state of the wave.
   *
   * Accepted values: STATE_FAILED, STATE_IN_PROGRESS, STATE_PENDING,
   * STATE_SKIPPED, STATE_SUCCEEDED, STATE_UNSPECIFIED
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutWaveDetailsOrchestratedWaveDetailsLocationStatus::class, 'Google_Service_Compute_RolloutWaveDetailsOrchestratedWaveDetailsLocationStatus');
