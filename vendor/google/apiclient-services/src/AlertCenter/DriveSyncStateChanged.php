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

namespace Google\Service\AlertCenter;

class DriveSyncStateChanged extends \Google\Model
{
  /**
   * Unspecified state
   */
  public const SYNC_STATE_SYNC_STATE_UNSPECIFIED = 'SYNC_STATE_UNSPECIFIED';
  /**
   * Sync is paused
   */
  public const SYNC_STATE_PAUSED = 'PAUSED';
  /**
   * Sync is resumed
   */
  public const SYNC_STATE_RESUMED = 'RESUMED';
  /**
   * Unspecified state change reason
   */
  public const SYNC_STATE_CHANGE_REASON_SYNC_STATE_CHANGE_REASON_UNSPECIFIED = 'SYNC_STATE_CHANGE_REASON_UNSPECIFIED';
  /**
   * Sync state was changed due to unusual activity, such as potential
   * ransomware
   */
  public const SYNC_STATE_CHANGE_REASON_UNUSUAL_ACTIVITY = 'UNUSUAL_ACTIVITY';
  /**
   * The user provided feedback indicating that the initial sync state change
   * may have been caused by unexpected activity
   */
  public const SYNC_STATE_CHANGE_REASON_USER_FEEDBACK_TRUE_POSITIVE = 'USER_FEEDBACK_TRUE_POSITIVE';
  /**
   * The user provided feedback indicating that the initial sync state change
   * may have been caused by a false positive
   */
  public const SYNC_STATE_CHANGE_REASON_USER_FEEDBACK_FALSE_POSITIVE = 'USER_FEEDBACK_FALSE_POSITIVE';
  /**
   * Email of the user affected.
   *
   * @var string
   */
  public $email;
  /**
   * Time at which sync was paused.
   *
   * @var string
   */
  public $syncPauseStartTime;
  /**
   * The current sync state.
   *
   * @var string
   */
  public $syncState;
  /**
   * The reason for the sync state change.
   *
   * @var string
   */
  public $syncStateChangeReason;

  /**
   * Email of the user affected.
   *
   * @param string $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }
  /**
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  }
  /**
   * Time at which sync was paused.
   *
   * @param string $syncPauseStartTime
   */
  public function setSyncPauseStartTime($syncPauseStartTime)
  {
    $this->syncPauseStartTime = $syncPauseStartTime;
  }
  /**
   * @return string
   */
  public function getSyncPauseStartTime()
  {
    return $this->syncPauseStartTime;
  }
  /**
   * The current sync state.
   *
   * Accepted values: SYNC_STATE_UNSPECIFIED, PAUSED, RESUMED
   *
   * @param self::SYNC_STATE_* $syncState
   */
  public function setSyncState($syncState)
  {
    $this->syncState = $syncState;
  }
  /**
   * @return self::SYNC_STATE_*
   */
  public function getSyncState()
  {
    return $this->syncState;
  }
  /**
   * The reason for the sync state change.
   *
   * Accepted values: SYNC_STATE_CHANGE_REASON_UNSPECIFIED, UNUSUAL_ACTIVITY,
   * USER_FEEDBACK_TRUE_POSITIVE, USER_FEEDBACK_FALSE_POSITIVE
   *
   * @param self::SYNC_STATE_CHANGE_REASON_* $syncStateChangeReason
   */
  public function setSyncStateChangeReason($syncStateChangeReason)
  {
    $this->syncStateChangeReason = $syncStateChangeReason;
  }
  /**
   * @return self::SYNC_STATE_CHANGE_REASON_*
   */
  public function getSyncStateChangeReason()
  {
    return $this->syncStateChangeReason;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DriveSyncStateChanged::class, 'Google_Service_AlertCenter_DriveSyncStateChanged');
