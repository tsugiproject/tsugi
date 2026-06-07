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

class GoldengateAmazonS3ConnectionProperties extends \Google\Model
{
  /**
   * Optional. Access key ID to access the Amazon S3 bucket.
   *
   * @var string
   */
  public $accessKeyId;
  /**
   * Optional. The Amazon Endpoint for S3.
   *
   * @var string
   */
  public $endpoint;
  /**
   * Optional. The name of the AWS region where the bucket is created.
   *
   * @var string
   */
  public $region;
  /**
   * Optional. Secret access key to access the Amazon S3 bucket.
   *
   * @var string
   */
  public $secretAccessKeySecret;
  /**
   * Optional. The technology type of AmazonS3Connection.
   *
   * @var string
   */
  public $technologyType;

  /**
   * Optional. Access key ID to access the Amazon S3 bucket.
   *
   * @param string $accessKeyId
   */
  public function setAccessKeyId($accessKeyId)
  {
    $this->accessKeyId = $accessKeyId;
  }
  /**
   * @return string
   */
  public function getAccessKeyId()
  {
    return $this->accessKeyId;
  }
  /**
   * Optional. The Amazon Endpoint for S3.
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
   * Optional. The name of the AWS region where the bucket is created.
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
   * Optional. Secret access key to access the Amazon S3 bucket.
   *
   * @param string $secretAccessKeySecret
   */
  public function setSecretAccessKeySecret($secretAccessKeySecret)
  {
    $this->secretAccessKeySecret = $secretAccessKeySecret;
  }
  /**
   * @return string
   */
  public function getSecretAccessKeySecret()
  {
    return $this->secretAccessKeySecret;
  }
  /**
   * Optional. The technology type of AmazonS3Connection.
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
class_alias(GoldengateAmazonS3ConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateAmazonS3ConnectionProperties');
