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

class GoldengateAmazonKinesisConnectionProperties extends \Google\Model
{
  /**
   * Optional. Access key ID to access the Amazon Kinesis.
   *
   * @var string
   */
  public $accessKeyId;
  /**
   * Optional. The name of the AWS region. If not provided, Goldengate will
   * default to 'us-west-1'.
   *
   * @var string
   */
  public $awsRegion;
  /**
   * Optional. The endpoint URL of the Amazon Kinesis service. e.g.:
   * 'https://kinesis.us-east-1.amazonaws.com' If not provided, Goldengate will
   * default to 'https://kinesis..amazonaws.com'.
   *
   * @var string
   */
  public $endpoint;
  /**
   * Optional. Secret access key to access the Amazon Kinesis.
   *
   * @var string
   */
  public $secretAccessKeySecret;
  /**
   * Optional. The technology type of AmazonKinesisConnection.
   *
   * @var string
   */
  public $technologyType;

  /**
   * Optional. Access key ID to access the Amazon Kinesis.
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
   * Optional. The name of the AWS region. If not provided, Goldengate will
   * default to 'us-west-1'.
   *
   * @param string $awsRegion
   */
  public function setAwsRegion($awsRegion)
  {
    $this->awsRegion = $awsRegion;
  }
  /**
   * @return string
   */
  public function getAwsRegion()
  {
    return $this->awsRegion;
  }
  /**
   * Optional. The endpoint URL of the Amazon Kinesis service. e.g.:
   * 'https://kinesis.us-east-1.amazonaws.com' If not provided, Goldengate will
   * default to 'https://kinesis..amazonaws.com'.
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
   * Optional. Secret access key to access the Amazon Kinesis.
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
   * Optional. The technology type of AmazonKinesisConnection.
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
class_alias(GoldengateAmazonKinesisConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateAmazonKinesisConnectionProperties');
