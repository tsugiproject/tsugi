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

class GoldengateAzureDataLakeStorageConnectionProperties extends \Google\Model
{
  /**
   * Authentication type not specified.
   */
  public const AUTHENTICATION_TYPE_AUTHENTICATION_TYPE_UNSPECIFIED = 'AUTHENTICATION_TYPE_UNSPECIFIED';
  /**
   * Shared key authentication.
   */
  public const AUTHENTICATION_TYPE_SHARED_KEY = 'SHARED_KEY';
  /**
   * Shared access signature authentication.
   */
  public const AUTHENTICATION_TYPE_SHARED_ACCESS_SIGNATURE = 'SHARED_ACCESS_SIGNATURE';
  /**
   * Azure active directory authentication.
   */
  public const AUTHENTICATION_TYPE_AZURE_ACTIVE_DIRECTORY = 'AZURE_ACTIVE_DIRECTORY';
  /**
   * Optional. Sets the Azure storage account name.
   *
   * @var string
   */
  public $account;
  /**
   * Optional. Azure storage account key. This property is required when
   * 'authentication_type' is set to 'SHARED_KEY'.
   *
   * @var string
   */
  public $accountKeySecret;
  /**
   * Optional. Authentication mechanism to access Azure Data Lake Storage.
   *
   * @var string
   */
  public $authenticationType;
  /**
   * Optional. The endpoint used for authentication with Microsoft Entra ID
   * (formerly Azure Active Directory). Default value:
   * https://login.microsoftonline.com
   *
   * @var string
   */
  public $azureAuthorityHost;
  /**
   * Optional. Azure tenant ID of the application. This property is required
   * when 'authentication_type' is set to 'AZURE_ACTIVE_DIRECTORY'.
   *
   * @var string
   */
  public $azureTenantId;
  /**
   * Optional. Azure client ID of the application. This property is required
   * when 'authentication_type' is set to 'AZURE_ACTIVE_DIRECTORY'.
   *
   * @var string
   */
  public $clientId;
  /**
   * Optional. Azure client secret (aka application password) for
   * authentication.
   *
   * @var string
   */
  public $clientSecret;
  /**
   * Optional. Azure Storage service endpoint. e.g:
   * https://test.blob.core.windows.net
   *
   * @var string
   */
  public $endpoint;
  /**
   * Optional. Credential that uses a shared access signature (SAS) to
   * authenticate to an Azure Service.
   *
   * @var string
   */
  public $sasTokenSecret;
  /**
   * Optional. The technology type of AzureDataLakeStorageConnection.
   *
   * @var string
   */
  public $technologyType;

  /**
   * Optional. Sets the Azure storage account name.
   *
   * @param string $account
   */
  public function setAccount($account)
  {
    $this->account = $account;
  }
  /**
   * @return string
   */
  public function getAccount()
  {
    return $this->account;
  }
  /**
   * Optional. Azure storage account key. This property is required when
   * 'authentication_type' is set to 'SHARED_KEY'.
   *
   * @param string $accountKeySecret
   */
  public function setAccountKeySecret($accountKeySecret)
  {
    $this->accountKeySecret = $accountKeySecret;
  }
  /**
   * @return string
   */
  public function getAccountKeySecret()
  {
    return $this->accountKeySecret;
  }
  /**
   * Optional. Authentication mechanism to access Azure Data Lake Storage.
   *
   * Accepted values: AUTHENTICATION_TYPE_UNSPECIFIED, SHARED_KEY,
   * SHARED_ACCESS_SIGNATURE, AZURE_ACTIVE_DIRECTORY
   *
   * @param self::AUTHENTICATION_TYPE_* $authenticationType
   */
  public function setAuthenticationType($authenticationType)
  {
    $this->authenticationType = $authenticationType;
  }
  /**
   * @return self::AUTHENTICATION_TYPE_*
   */
  public function getAuthenticationType()
  {
    return $this->authenticationType;
  }
  /**
   * Optional. The endpoint used for authentication with Microsoft Entra ID
   * (formerly Azure Active Directory). Default value:
   * https://login.microsoftonline.com
   *
   * @param string $azureAuthorityHost
   */
  public function setAzureAuthorityHost($azureAuthorityHost)
  {
    $this->azureAuthorityHost = $azureAuthorityHost;
  }
  /**
   * @return string
   */
  public function getAzureAuthorityHost()
  {
    return $this->azureAuthorityHost;
  }
  /**
   * Optional. Azure tenant ID of the application. This property is required
   * when 'authentication_type' is set to 'AZURE_ACTIVE_DIRECTORY'.
   *
   * @param string $azureTenantId
   */
  public function setAzureTenantId($azureTenantId)
  {
    $this->azureTenantId = $azureTenantId;
  }
  /**
   * @return string
   */
  public function getAzureTenantId()
  {
    return $this->azureTenantId;
  }
  /**
   * Optional. Azure client ID of the application. This property is required
   * when 'authentication_type' is set to 'AZURE_ACTIVE_DIRECTORY'.
   *
   * @param string $clientId
   */
  public function setClientId($clientId)
  {
    $this->clientId = $clientId;
  }
  /**
   * @return string
   */
  public function getClientId()
  {
    return $this->clientId;
  }
  /**
   * Optional. Azure client secret (aka application password) for
   * authentication.
   *
   * @param string $clientSecret
   */
  public function setClientSecret($clientSecret)
  {
    $this->clientSecret = $clientSecret;
  }
  /**
   * @return string
   */
  public function getClientSecret()
  {
    return $this->clientSecret;
  }
  /**
   * Optional. Azure Storage service endpoint. e.g:
   * https://test.blob.core.windows.net
   *
   * @param string $endpoint
   */
  public function setEndpoint($endpoint)
  {
    $this->endpoint = $endpoint;
  }
  /**
   * @return string
   */
  public function getEndpoint()
  {
    return $this->endpoint;
  }
  /**
   * Optional. Credential that uses a shared access signature (SAS) to
   * authenticate to an Azure Service.
   *
   * @param string $sasTokenSecret
   */
  public function setSasTokenSecret($sasTokenSecret)
  {
    $this->sasTokenSecret = $sasTokenSecret;
  }
  /**
   * @return string
   */
  public function getSasTokenSecret()
  {
    return $this->sasTokenSecret;
  }
  /**
   * Optional. The technology type of AzureDataLakeStorageConnection.
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateAzureDataLakeStorageConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateAzureDataLakeStorageConnectionProperties');
