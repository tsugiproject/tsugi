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

namespace Google\Service\CloudIAP;

class AccessSettings extends \Google\Collection
{
  protected $collection_key = 'identitySources';
  protected $allowedDomainsSettingsType = AllowedDomainsSettings::class;
  protected $allowedDomainsSettingsDataType = '';
  protected $corsSettingsType = CorsSettings::class;
  protected $corsSettingsDataType = '';
  protected $gcipSettingsType = GcipSettings::class;
  protected $gcipSettingsDataType = '';
  /**
   * @var string[]
   */
  public $identitySources;
  protected $oauthSettingsType = OAuthSettings::class;
  protected $oauthSettingsDataType = '';
  protected $policyDelegationSettingsType = PolicyDelegationSettings::class;
  protected $policyDelegationSettingsDataType = '';
  protected $reauthSettingsType = ReauthSettings::class;
  protected $reauthSettingsDataType = '';
  protected $workforceIdentitySettingsType = WorkforceIdentitySettings::class;
  protected $workforceIdentitySettingsDataType = '';

  /**
   * @param AllowedDomainsSettings
   */
  public function setAllowedDomainsSettings(AllowedDomainsSettings $allowedDomainsSettings)
  {
    $this->allowedDomainsSettings = $allowedDomainsSettings;
  }
  /**
   * @return AllowedDomainsSettings
   */
  public function getAllowedDomainsSettings()
  {
    return $this->allowedDomainsSettings;
  }
  /**
   * @param CorsSettings
   */
  public function setCorsSettings(CorsSettings $corsSettings)
  {
    $this->corsSettings = $corsSettings;
  }
  /**
   * @return CorsSettings
   */
  public function getCorsSettings()
  {
    return $this->corsSettings;
  }
  /**
   * @param GcipSettings
   */
  public function setGcipSettings(GcipSettings $gcipSettings)
  {
    $this->gcipSettings = $gcipSettings;
  }
  /**
   * @return GcipSettings
   */
  public function getGcipSettings()
  {
    return $this->gcipSettings;
  }
  /**
   * @param string[]
   */
  public function setIdentitySources($identitySources)
  {
    $this->identitySources = $identitySources;
  }
  /**
   * @return string[]
   */
  public function getIdentitySources()
  {
    return $this->identitySources;
  }
  /**
   * @param OAuthSettings
   */
  public function setOauthSettings(OAuthSettings $oauthSettings)
  {
    $this->oauthSettings = $oauthSettings;
  }
  /**
   * @return OAuthSettings
   */
  public function getOauthSettings()
  {
    return $this->oauthSettings;
  }
  /**
   * @param PolicyDelegationSettings
   */
  public function setPolicyDelegationSettings(PolicyDelegationSettings $policyDelegationSettings)
  {
    $this->policyDelegationSettings = $policyDelegationSettings;
  }
  /**
   * @return PolicyDelegationSettings
   */
  public function getPolicyDelegationSettings()
  {
    return $this->policyDelegationSettings;
  }
  /**
   * @param ReauthSettings
   */
  public function setReauthSettings(ReauthSettings $reauthSettings)
  {
    $this->reauthSettings = $reauthSettings;
  }
  /**
   * @return ReauthSettings
   */
  public function getReauthSettings()
  {
    return $this->reauthSettings;
  }
  /**
   * @param WorkforceIdentitySettings
   */
  public function setWorkforceIdentitySettings(WorkforceIdentitySettings $workforceIdentitySettings)
  {
    $this->workforceIdentitySettings = $workforceIdentitySettings;
  }
  /**
   * @return WorkforceIdentitySettings
   */
  public function getWorkforceIdentitySettings()
  {
    return $this->workforceIdentitySettings;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AccessSettings::class, 'Google_Service_CloudIAP_AccessSettings');
