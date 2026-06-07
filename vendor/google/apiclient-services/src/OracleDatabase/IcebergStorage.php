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

class IcebergStorage extends \Google\Model
{
  /**
   * Storage type not specified.
   */
  public const STORAGE_TYPE_STORAGE_TYPE_UNSPECIFIED = 'STORAGE_TYPE_UNSPECIFIED';
  /**
   * Amazon S3 storage.
   */
  public const STORAGE_TYPE_AMAZON_S3 = 'AMAZON_S3';
  /**
   * Google Cloud Storage storage.
   */
  public const STORAGE_TYPE_GOOGLE_CLOUD_STORAGE = 'GOOGLE_CLOUD_STORAGE';
  /**
   * Azure Data Lake Storage storage.
   */
  public const STORAGE_TYPE_AZURE_DATA_LAKE_STORAGE = 'AZURE_DATA_LAKE_STORAGE';
  protected $amazonS3IcebergStorageType = AmazonS3IcebergStorage::class;
  protected $amazonS3IcebergStorageDataType = '';
  protected $azureDataLakeStorageIcebergStorageType = AzureDataLakeStorageIcebergStorage::class;
  protected $azureDataLakeStorageIcebergStorageDataType = '';
  protected $googleCloudStorageIcebergStorageType = GoogleCloudStorageIcebergStorage::class;
  protected $googleCloudStorageIcebergStorageDataType = '';
  /**
   * Required. The type of Iceberg storage.
   *
   * @var string
   */
  public $storageType;

  /**
   * The Amazon S3 Iceberg storage.
   *
   * @param AmazonS3IcebergStorage $amazonS3IcebergStorage
   */
  public function setAmazonS3IcebergStorage(AmazonS3IcebergStorage $amazonS3IcebergStorage)
  {
    $this->amazonS3IcebergStorage = $amazonS3IcebergStorage;
  }
  /**
   * @return AmazonS3IcebergStorage
   */
  public function getAmazonS3IcebergStorage()
  {
    return $this->amazonS3IcebergStorage;
  }
  /**
   * The Azure Data Lake Storage Iceberg storage.
   *
   * @param AzureDataLakeStorageIcebergStorage $azureDataLakeStorageIcebergStorage
   */
  public function setAzureDataLakeStorageIcebergStorage(AzureDataLakeStorageIcebergStorage $azureDataLakeStorageIcebergStorage)
  {
    $this->azureDataLakeStorageIcebergStorage = $azureDataLakeStorageIcebergStorage;
  }
  /**
   * @return AzureDataLakeStorageIcebergStorage
   */
  public function getAzureDataLakeStorageIcebergStorage()
  {
    return $this->azureDataLakeStorageIcebergStorage;
  }
  /**
   * The Google Cloud Storage Iceberg storage.
   *
   * @param GoogleCloudStorageIcebergStorage $googleCloudStorageIcebergStorage
   */
  public function setGoogleCloudStorageIcebergStorage(GoogleCloudStorageIcebergStorage $googleCloudStorageIcebergStorage)
  {
    $this->googleCloudStorageIcebergStorage = $googleCloudStorageIcebergStorage;
  }
  /**
   * @return GoogleCloudStorageIcebergStorage
   */
  public function getGoogleCloudStorageIcebergStorage()
  {
    return $this->googleCloudStorageIcebergStorage;
  }
  /**
   * Required. The type of Iceberg storage.
   *
   * Accepted values: STORAGE_TYPE_UNSPECIFIED, AMAZON_S3, GOOGLE_CLOUD_STORAGE,
   * AZURE_DATA_LAKE_STORAGE
   *
   * @param self::STORAGE_TYPE_* $storageType
   */
  public function setStorageType($storageType)
  {
    $this->storageType = $storageType;
  }
  /**
   * @return self::STORAGE_TYPE_*
   */
  public function getStorageType()
  {
    return $this->storageType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(IcebergStorage::class, 'Google_Service_OracleDatabase_IcebergStorage');
