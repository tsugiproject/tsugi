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

namespace Google\Service\FirebaseDataConnect;

class GenerationStatus extends \Google\Model
{
  /**
   * Unspecified state.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * The agent is analyzing schema or operations.
   */
  public const STATE_ANALYZING_CODE = 'ANALYZING_CODE';
  /**
   * The agent is generating code
   */
  public const STATE_GENERATING_CODE = 'GENERATING_CODE';
  /**
   * Generation is complete.
   */
  public const STATE_COMPLETED = 'COMPLETED';
  /**
   * Output only. A message providing more details about the state.
   *
   * @var string
   */
  public $message;
  /**
   * Output only. The state of generation.
   *
   * @var string
   */
  public $state;

  /**
   * Output only. A message providing more details about the state.
   *
   * @param string $message
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
  /**
   * Output only. The state of generation.
   *
   * Accepted values: STATE_UNSPECIFIED, ANALYZING_CODE, GENERATING_CODE,
   * COMPLETED
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
class_alias(GenerationStatus::class, 'Google_Service_FirebaseDataConnect_GenerationStatus');
