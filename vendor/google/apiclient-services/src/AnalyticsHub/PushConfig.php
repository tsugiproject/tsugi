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

class PushConfig extends \Google\Model
{
  /**
   * @var string[]
   */
  public $attributes;
  protected $noWrapperType = NoWrapper::class;
  protected $noWrapperDataType = '';
  protected $oidcTokenType = OidcToken::class;
  protected $oidcTokenDataType = '';
  protected $pubsubWrapperType = PubsubWrapper::class;
  protected $pubsubWrapperDataType = '';
  /**
   * @var string
   */
  public $pushEndpoint;

  /**
   * @param string[]
   */
  public function setAttributes($attributes)
  {
    $this->attributes = $attributes;
  }
  /**
   * @return string[]
   */
  public function getAttributes()
  {
    return $this->attributes;
  }
  /**
   * @param NoWrapper
   */
  public function setNoWrapper(NoWrapper $noWrapper)
  {
    $this->noWrapper = $noWrapper;
  }
  /**
   * @return NoWrapper
   */
  public function getNoWrapper()
  {
    return $this->noWrapper;
  }
  /**
   * @param OidcToken
   */
  public function setOidcToken(OidcToken $oidcToken)
  {
    $this->oidcToken = $oidcToken;
  }
  /**
   * @return OidcToken
   */
  public function getOidcToken()
  {
    return $this->oidcToken;
  }
  /**
   * @param PubsubWrapper
   */
  public function setPubsubWrapper(PubsubWrapper $pubsubWrapper)
  {
    $this->pubsubWrapper = $pubsubWrapper;
  }
  /**
   * @return PubsubWrapper
   */
  public function getPubsubWrapper()
  {
    return $this->pubsubWrapper;
  }
  /**
   * @param string
   */
  public function setPushEndpoint($pushEndpoint)
  {
    $this->pushEndpoint = $pushEndpoint;
  }
  /**
   * @return string
   */
  public function getPushEndpoint()
  {
    return $this->pushEndpoint;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PushConfig::class, 'Google_Service_AnalyticsHub_PushConfig');
