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

namespace Google\Service\CustomerEngagementSuite;

class LfA2aV1TaskStatus extends \Google\Model
{
  /**
   * The task is in an unknown or indeterminate state.
   */
  public const STATE_TASK_STATE_UNSPECIFIED = 'TASK_STATE_UNSPECIFIED';
  /**
   * Indicates that a task has been successfully submitted and acknowledged.
   */
  public const STATE_TASK_STATE_SUBMITTED = 'TASK_STATE_SUBMITTED';
  /**
   * Indicates that a task is actively being processed by the agent.
   */
  public const STATE_TASK_STATE_WORKING = 'TASK_STATE_WORKING';
  /**
   * Indicates that a task has finished successfully. This is a terminal state.
   */
  public const STATE_TASK_STATE_COMPLETED = 'TASK_STATE_COMPLETED';
  /**
   * Indicates that a task has finished with an error. This is a terminal state.
   */
  public const STATE_TASK_STATE_FAILED = 'TASK_STATE_FAILED';
  /**
   * Indicates that a task was canceled before completion. This is a terminal
   * state.
   */
  public const STATE_TASK_STATE_CANCELED = 'TASK_STATE_CANCELED';
  /**
   * Indicates that the agent requires additional user input to proceed. This is
   * an interrupted state.
   */
  public const STATE_TASK_STATE_INPUT_REQUIRED = 'TASK_STATE_INPUT_REQUIRED';
  /**
   * Indicates that the agent has decided to not perform the task. This may be
   * done during initial task creation or later once an agent has determined it
   * can't or won't proceed. This is a terminal state.
   */
  public const STATE_TASK_STATE_REJECTED = 'TASK_STATE_REJECTED';
  /**
   * Indicates that authentication is required to proceed. This is an
   * interrupted state.
   */
  public const STATE_TASK_STATE_AUTH_REQUIRED = 'TASK_STATE_AUTH_REQUIRED';
  protected $messageType = LfA2aV1Message::class;
  protected $messageDataType = '';
  /**
   * Required. The current state of this task.
   *
   * @var string
   */
  public $state;
  /**
   * ISO 8601 Timestamp when the status was recorded. Example:
   * "2023-10-27T10:00:00Z"
   *
   * @var string
   */
  public $timestamp;

  /**
   * A message associated with the status.
   *
   * @param LfA2aV1Message $message
   */
  public function setMessage(LfA2aV1Message $message)
  {
    $this->message = $message;
  }
  /**
   * @return LfA2aV1Message
   */
  public function getMessage()
  {
    return $this->message;
  }
  /**
   * Required. The current state of this task.
   *
   * Accepted values: TASK_STATE_UNSPECIFIED, TASK_STATE_SUBMITTED,
   * TASK_STATE_WORKING, TASK_STATE_COMPLETED, TASK_STATE_FAILED,
   * TASK_STATE_CANCELED, TASK_STATE_INPUT_REQUIRED, TASK_STATE_REJECTED,
   * TASK_STATE_AUTH_REQUIRED
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
  /**
   * ISO 8601 Timestamp when the status was recorded. Example:
   * "2023-10-27T10:00:00Z"
   *
   * @param string $timestamp
   */
  public function setTimestamp($timestamp)
  {
    $this->timestamp = $timestamp;
  }
  /**
   * @return string
   */
  public function getTimestamp()
  {
    return $this->timestamp;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(LfA2aV1TaskStatus::class, 'Google_Service_CustomerEngagementSuite_LfA2aV1TaskStatus');
