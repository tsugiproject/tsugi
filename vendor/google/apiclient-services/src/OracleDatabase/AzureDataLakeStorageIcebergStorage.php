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

class AzureDataLakeStorageIcebergStorage extends \Google\Model
{
  /**
   * Optional. The account key of Azure Data Lake Storage.
   *
   * @var string
   */
  public $accountKeySecret;
  /**
   * Required. The account of Azure Data Lake Storage.
   *
   * @var string
   */
  public $azureAccount;
  /**
   * Required. The container of Azure Data Lake Storage.
   *
   * @var string
   */
  public $container;
  /**
   * Optional. The endpoint of Azure Data Lake Storage.
   *
   * @var string
   */
  public $endpoint;

  /**
   * Optional. The account key of Azure Data Lake Storage.
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
   * Required. The account of Azure Data Lake Storage.
   *
   * @param string $azureAccount
   */
  public function setAzureAccount($azureAccount)
  {
    $this->azureAccount = $azureAccount;
  }
  /**
   * @return string
   */
  public function getAzureAccount()
  {
    return $this->azureAccount;
  }
  /**
   * Required. The container of Azure Data Lake Storage.
   *
   * @param string $container
   */
  public function setContainer($container)
  {
    $this->container = $container;
  }
  /**
   * @return string
   */
  public function getContainer()
  {
    return $this->container;
  }
  /**
   * Optional. The endpoint of Azure Data Lake Storage.
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AzureDataLakeStorageIcebergStorage::class, 'Google_Service_OracleDatabase_AzureDataLakeStorageIcebergStorage');
