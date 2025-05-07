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

namespace Google\Service\Cloudchannel;

class GoogleCloudChannelV1alpha1OpportunityEvent extends \Google\Model
{
  /**
   * @var string
   */
  public $eventType;
  /**
   * @var string
   */
  public $opportunity;

  /**
   * @param string
   */
  public function setEventType($eventType)
  {
    $this->eventType = $eventType;
  }
  /**
   * @return string
   */
  public function getEventType()
  {
    return $this->eventType;
  }
  /**
   * @param string
   */
  public function setOpportunity($opportunity)
  {
    $this->opportunity = $opportunity;
  }
  /**
   * @return string
   */
  public function getOpportunity()
  {
    return $this->opportunity;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudChannelV1alpha1OpportunityEvent::class, 'Google_Service_Cloudchannel_GoogleCloudChannelV1alpha1OpportunityEvent');
