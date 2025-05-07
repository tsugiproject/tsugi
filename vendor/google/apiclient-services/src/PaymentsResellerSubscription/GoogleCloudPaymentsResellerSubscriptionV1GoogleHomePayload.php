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

namespace Google\Service\PaymentsResellerSubscription;

class GoogleCloudPaymentsResellerSubscriptionV1GoogleHomePayload extends \Google\Model
{
  /**
   * @var string
   */
  public $partnerStructureId;

  /**
   * @param string
   */
  public function setPartnerStructureId($partnerStructureId)
  {
    $this->partnerStructureId = $partnerStructureId;
  }
  /**
   * @return string
   */
  public function getPartnerStructureId()
  {
    return $this->partnerStructureId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudPaymentsResellerSubscriptionV1GoogleHomePayload::class, 'Google_Service_PaymentsResellerSubscription_GoogleCloudPaymentsResellerSubscriptionV1GoogleHomePayload');
