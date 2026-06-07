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

class LfA2aV1SendMessageConfiguration extends \Google\Collection
{
  protected $collection_key = 'acceptedOutputModes';
  /**
   * A list of media types the client is prepared to accept for response parts.
   * Agents SHOULD use this to tailor their output.
   *
   * @var string[]
   */
  public $acceptedOutputModes;
  /**
   * The maximum number of most recent messages from the task's history to
   * retrieve in the response. An unset value means the client does not impose
   * any limit. A value of zero is a request to not include any messages. The
   * server MUST NOT return more messages than the provided value, but MAY apply
   * a lower limit.
   *
   * @var int
   */
  public $historyLength;
  /**
   * If `true`, the operation returns immediately after creating the task, even
   * if processing is still in progress. If `false` (default), the operation
   * MUST wait until the task reaches a terminal (`COMPLETED`, `FAILED`,
   * `CANCELED`, `REJECTED`) or interrupted (`INPUT_REQUIRED`, `AUTH_REQUIRED`)
   * state before returning.
   *
   * @var bool
   */
  public $returnImmediately;
  protected $taskPushNotificationConfigType = LfA2aV1TaskPushNotificationConfig::class;
  protected $taskPushNotificationConfigDataType = '';

  /**
   * A list of media types the client is prepared to accept for response parts.
   * Agents SHOULD use this to tailor their output.
   *
   * @param string[] $acceptedOutputModes
   */
  public function setAcceptedOutputModes($acceptedOutputModes)
  {
    $this->acceptedOutputModes = $acceptedOutputModes;
  }
  /**
   * @return string[]
   */
  public function getAcceptedOutputModes()
  {
    return $this->acceptedOutputModes;
  }
  /**
   * The maximum number of most recent messages from the task's history to
   * retrieve in the response. An unset value means the client does not impose
   * any limit. A value of zero is a request to not include any messages. The
   * server MUST NOT return more messages than the provided value, but MAY apply
   * a lower limit.
   *
   * @param int $historyLength
   */
  public function setHistoryLength($historyLength)
  {
    $this->historyLength = $historyLength;
  }
  /**
   * @return int
   */
  public function getHistoryLength()
  {
    return $this->historyLength;
  }
  /**
   * If `true`, the operation returns immediately after creating the task, even
   * if processing is still in progress. If `false` (default), the operation
   * MUST wait until the task reaches a terminal (`COMPLETED`, `FAILED`,
   * `CANCELED`, `REJECTED`) or interrupted (`INPUT_REQUIRED`, `AUTH_REQUIRED`)
   * state before returning.
   *
   * @param bool $returnImmediately
   */
  public function setReturnImmediately($returnImmediately)
  {
    $this->returnImmediately = $returnImmediately;
  }
  /**
   * @return bool
   */
  public function getReturnImmediately()
  {
    return $this->returnImmediately;
  }
  /**
   * Configuration for the agent to send push notifications for task updates.
   * Task id should be empty when sending this configuration in a `SendMessage`
   * request.
   *
   * @param LfA2aV1TaskPushNotificationConfig $taskPushNotificationConfig
   */
  public function setTaskPushNotificationConfig(LfA2aV1TaskPushNotificationConfig $taskPushNotificationConfig)
  {
    $this->taskPushNotificationConfig = $taskPushNotificationConfig;
  }
  /**
   * @return LfA2aV1TaskPushNotificationConfig
   */
  public function getTaskPushNotificationConfig()
  {
    return $this->taskPushNotificationConfig;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(LfA2aV1SendMessageConfiguration::class, 'Google_Service_CustomerEngagementSuite_LfA2aV1SendMessageConfiguration');
