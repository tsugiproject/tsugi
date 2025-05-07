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

namespace Google\Service\AnalyticsHub;

class DeadLetterPolicy extends \Google\Model
{
  /**
   * @var string
   */
  public $deadLetterTopic;
  /**
   * @var int
   */
  public $maxDeliveryAttempts;

  /**
   * @param string
   */
  public function setDeadLetterTopic($deadLetterTopic)
  {
    $this->deadLetterTopic = $deadLetterTopic;
  }
  /**
   * @return string
   */
  public function getDeadLetterTopic()
  {
    return $this->deadLetterTopic;
  }
  /**
   * @param int
   */
  public function setMaxDeliveryAttempts($maxDeliveryAttempts)
  {
    $this->maxDeliveryAttempts = $maxDeliveryAttempts;
  }
  /**
   * @return int
   */
  public function getMaxDeliveryAttempts()
  {
    return $this->maxDeliveryAttempts;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeadLetterPolicy::class, 'Google_Service_AnalyticsHub_DeadLetterPolicy');
