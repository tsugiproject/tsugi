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

namespace Google\Service\Meet;

class GatewaySipAccess extends \Google\Model
{
  /**
   * The permanent numeric code for manual entry on specially configured
   * devices.
   *
   * @var string
   */
  public $sipAccessCode;
  /**
   * The Session Initiation Protocol (SIP) URI the conference can be reached
   * through. The string is in one of these formats: *
   * "sip:USER_ID@GATEWAY_ADDRESS" * "sips:USER_ID@GATEWAY_ADDRESS" where
   * USER_ID is the 13-digit universal pin (with the future option to support
   * using a Meet meeting code as well), and GATEWAY_ADDRESS is a valid address
   * to be resolved using a DNS SRV lookup, or a dotted quad.
   *
   * @var string
   */
  public $uri;

  /**
   * The permanent numeric code for manual entry on specially configured
   * devices.
   *
   * @param string $sipAccessCode
   */
  public function setSipAccessCode($sipAccessCode)
  {
    $this->sipAccessCode = $sipAccessCode;
  }
  /**
   * @return string
   */
  public function getSipAccessCode()
  {
    return $this->sipAccessCode;
  }
  /**
   * The Session Initiation Protocol (SIP) URI the conference can be reached
   * through. The string is in one of these formats: *
   * "sip:USER_ID@GATEWAY_ADDRESS" * "sips:USER_ID@GATEWAY_ADDRESS" where
   * USER_ID is the 13-digit universal pin (with the future option to support
   * using a Meet meeting code as well), and GATEWAY_ADDRESS is a valid address
   * to be resolved using a DNS SRV lookup, or a dotted quad.
   *
   * @param string $uri
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GatewaySipAccess::class, 'Google_Service_Meet_GatewaySipAccess');
