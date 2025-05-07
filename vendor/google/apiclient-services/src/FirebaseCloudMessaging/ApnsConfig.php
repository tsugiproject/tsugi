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

namespace Google\Service\FirebaseCloudMessaging;

class ApnsConfig extends \Google\Model
{
  protected $fcmOptionsType = ApnsFcmOptions::class;
  protected $fcmOptionsDataType = '';
  /**
   * @var string[]
   */
  public $headers;
  /**
   * @var string
   */
  public $liveActivityToken;
  /**
   * @var array[]
   */
  public $payload;

  /**
   * @param ApnsFcmOptions
   */
  public function setFcmOptions(ApnsFcmOptions $fcmOptions)
  {
    $this->fcmOptions = $fcmOptions;
  }
  /**
   * @return ApnsFcmOptions
   */
  public function getFcmOptions()
  {
    return $this->fcmOptions;
  }
  /**
   * @param string[]
   */
  public function setHeaders($headers)
  {
    $this->headers = $headers;
  }
  /**
   * @return string[]
   */
  public function getHeaders()
  {
    return $this->headers;
  }
  /**
   * @param string
   */
  public function setLiveActivityToken($liveActivityToken)
  {
    $this->liveActivityToken = $liveActivityToken;
  }
  /**
   * @return string
   */
  public function getLiveActivityToken()
  {
    return $this->liveActivityToken;
  }
  /**
   * @param array[]
   */
  public function setPayload($payload)
  {
    $this->payload = $payload;
  }
  /**
   * @return array[]
   */
  public function getPayload()
  {
    return $this->payload;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ApnsConfig::class, 'Google_Service_FirebaseCloudMessaging_ApnsConfig');
