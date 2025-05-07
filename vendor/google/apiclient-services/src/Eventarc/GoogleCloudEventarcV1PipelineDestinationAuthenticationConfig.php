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

namespace Google\Service\Eventarc;

class GoogleCloudEventarcV1PipelineDestinationAuthenticationConfig extends \Google\Model
{
  protected $googleOidcType = GoogleCloudEventarcV1PipelineDestinationAuthenticationConfigOidcToken::class;
  protected $googleOidcDataType = '';
  protected $oauthTokenType = GoogleCloudEventarcV1PipelineDestinationAuthenticationConfigOAuthToken::class;
  protected $oauthTokenDataType = '';

  /**
   * @param GoogleCloudEventarcV1PipelineDestinationAuthenticationConfigOidcToken
   */
  public function setGoogleOidc(GoogleCloudEventarcV1PipelineDestinationAuthenticationConfigOidcToken $googleOidc)
  {
    $this->googleOidc = $googleOidc;
  }
  /**
   * @return GoogleCloudEventarcV1PipelineDestinationAuthenticationConfigOidcToken
   */
  public function getGoogleOidc()
  {
    return $this->googleOidc;
  }
  /**
   * @param GoogleCloudEventarcV1PipelineDestinationAuthenticationConfigOAuthToken
   */
  public function setOauthToken(GoogleCloudEventarcV1PipelineDestinationAuthenticationConfigOAuthToken $oauthToken)
  {
    $this->oauthToken = $oauthToken;
  }
  /**
   * @return GoogleCloudEventarcV1PipelineDestinationAuthenticationConfigOAuthToken
   */
  public function getOauthToken()
  {
    return $this->oauthToken;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudEventarcV1PipelineDestinationAuthenticationConfig::class, 'Google_Service_Eventarc_GoogleCloudEventarcV1PipelineDestinationAuthenticationConfig');
