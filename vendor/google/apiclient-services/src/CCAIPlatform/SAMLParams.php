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

namespace Google\Service\CCAIPlatform;

class SAMLParams extends \Google\Collection
{
  protected $collection_key = 'authenticationContexts';
  /**
   * @var string[]
   */
  public $authenticationContexts;
  /**
   * @var string
   */
  public $certificate;
  /**
   * @var string
   */
  public $emailMapping;
  /**
   * @var string
   */
  public $entityId;
  /**
   * @var string
   */
  public $ssoUri;
  /**
   * @var string
   */
  public $userEmail;

  /**
   * @param string[]
   */
  public function setAuthenticationContexts($authenticationContexts)
  {
    $this->authenticationContexts = $authenticationContexts;
  }
  /**
   * @return string[]
   */
  public function getAuthenticationContexts()
  {
    return $this->authenticationContexts;
  }
  /**
   * @param string
   */
  public function setCertificate($certificate)
  {
    $this->certificate = $certificate;
  }
  /**
   * @return string
   */
  public function getCertificate()
  {
    return $this->certificate;
  }
  /**
   * @param string
   */
  public function setEmailMapping($emailMapping)
  {
    $this->emailMapping = $emailMapping;
  }
  /**
   * @return string
   */
  public function getEmailMapping()
  {
    return $this->emailMapping;
  }
  /**
   * @param string
   */
  public function setEntityId($entityId)
  {
    $this->entityId = $entityId;
  }
  /**
   * @return string
   */
  public function getEntityId()
  {
    return $this->entityId;
  }
  /**
   * @param string
   */
  public function setSsoUri($ssoUri)
  {
    $this->ssoUri = $ssoUri;
  }
  /**
   * @return string
   */
  public function getSsoUri()
  {
    return $this->ssoUri;
  }
  /**
   * @param string
   */
  public function setUserEmail($userEmail)
  {
    $this->userEmail = $userEmail;
  }
  /**
   * @return string
   */
  public function getUserEmail()
  {
    return $this->userEmail;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SAMLParams::class, 'Google_Service_CCAIPlatform_SAMLParams');
