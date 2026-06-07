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

namespace Google\Service\OracleDatabase;

class GoldengateOciObjectStorageConnectionProperties extends \Google\Model
{
  /**
   * Optional. The content of the private key file (PEM file) corresponding to
   * the API key of the fingerprint.
   *
   * @var string
   */
  public $privateKeyFile;
  /**
   * Optional. The passphrase of the private key.
   *
   * @var string
   */
  public $privateKeyPassphraseSecret;
  /**
   * Optional. The fingerprint of the API Key of the user specified by the
   * userId.
   *
   * @var string
   */
  public $publicKeyFingerprint;
  /**
   * Optional. The name of the region of OCI Object Storage. e.g.: us-ashburn-1
   * If the region is not provided, backend will default to the default region.
   *
   * @var string
   */
  public $region;
  /**
   * Optional. The technology type of OciObjectStorageConnection.
   *
   * @var string
   */
  public $technologyType;
  /**
   * Optional. The OCID of the related OCI tenancy.
   *
   * @var string
   */
  public $tenancyId;
  /**
   * Optional. Specifies that the user intends to authenticate to the instance
   * using a resource principal.
   *
   * @var bool
   */
  public $useResourcePrincipal;
  /**
   * Optional. The OCID of the OCI user who will access the Object Storage. The
   * user must have write access to the bucket they want to connect to.
   *
   * @var string
   */
  public $userId;

  /**
   * Optional. The content of the private key file (PEM file) corresponding to
   * the API key of the fingerprint.
   *
   * @param string $privateKeyFile
   */
  public function setPrivateKeyFile($privateKeyFile)
  {
    $this->privateKeyFile = $privateKeyFile;
  }
  /**
   * @return string
   */
  public function getPrivateKeyFile()
  {
    return $this->privateKeyFile;
  }
  /**
   * Optional. The passphrase of the private key.
   *
   * @param string $privateKeyPassphraseSecret
   */
  public function setPrivateKeyPassphraseSecret($privateKeyPassphraseSecret)
  {
    $this->privateKeyPassphraseSecret = $privateKeyPassphraseSecret;
  }
  /**
   * @return string
   */
  public function getPrivateKeyPassphraseSecret()
  {
    return $this->privateKeyPassphraseSecret;
  }
  /**
   * Optional. The fingerprint of the API Key of the user specified by the
   * userId.
   *
   * @param string $publicKeyFingerprint
   */
  public function setPublicKeyFingerprint($publicKeyFingerprint)
  {
    $this->publicKeyFingerprint = $publicKeyFingerprint;
  }
  /**
   * @return string
   */
  public function getPublicKeyFingerprint()
  {
    return $this->publicKeyFingerprint;
  }
  /**
   * Optional. The name of the region of OCI Object Storage. e.g.: us-ashburn-1
   * If the region is not provided, backend will default to the default region.
   *
   * @param string $region
   */
  public function setRegion($region)
  {
    $this->region = $region;
  }
  /**
   * @return string
   */
  public function getRegion()
  {
    return $this->region;
  }
  /**
   * Optional. The technology type of OciObjectStorageConnection.
   *
   * @param string $technologyType
   */
  public function setTechnologyType($technologyType)
  {
    $this->technologyType = $technologyType;
  }
  /**
   * @return string
   */
  public function getTechnologyType()
  {
    return $this->technologyType;
  }
  /**
   * Optional. The OCID of the related OCI tenancy.
   *
   * @param string $tenancyId
   */
  public function setTenancyId($tenancyId)
  {
    $this->tenancyId = $tenancyId;
  }
  /**
   * @return string
   */
  public function getTenancyId()
  {
    return $this->tenancyId;
  }
  /**
   * Optional. Specifies that the user intends to authenticate to the instance
   * using a resource principal.
   *
   * @param bool $useResourcePrincipal
   */
  public function setUseResourcePrincipal($useResourcePrincipal)
  {
    $this->useResourcePrincipal = $useResourcePrincipal;
  }
  /**
   * @return bool
   */
  public function getUseResourcePrincipal()
  {
    return $this->useResourcePrincipal;
  }
  /**
   * Optional. The OCID of the OCI user who will access the Object Storage. The
   * user must have write access to the bucket they want to connect to.
   *
   * @param string $userId
   */
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  /**
   * @return string
   */
  public function getUserId()
  {
    return $this->userId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateOciObjectStorageConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateOciObjectStorageConnectionProperties');
