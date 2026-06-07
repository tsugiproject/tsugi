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

class AmazonS3IcebergStorage extends \Google\Model
{
  /**
   * Scheme type not specified.
   */
  public const SCHEME_TYPE_SCHEME_TYPE_UNSPECIFIED = 'SCHEME_TYPE_UNSPECIFIED';
  /**
   * S3 scheme.
   */
  public const SCHEME_TYPE_S3 = 'S3';
  /**
   * S3A scheme.
   */
  public const SCHEME_TYPE_S3A = 'S3A';
  /**
   * Required. The access key ID of Amazon S3.
   *
   * @var string
   */
  public $accessKeyId;
  /**
   * Required. The bucket of Amazon S3.
   *
   * @var string
   */
  public $bucket;
  /**
   * Optional. The endpoint of Amazon S3.
   *
   * @var string
   */
  public $endpoint;
  /**
   * Required. The region of Amazon S3.
   *
   * @var string
   */
  public $region;
  /**
   * Required. The scheme type of Amazon S3.
   *
   * @var string
   */
  public $schemeType;
  /**
   * Optional. The secret access key of Amazon S3.
   *
   * @var string
   */
  public $secretAccessKeySecret;

  /**
   * Required. The access key ID of Amazon S3.
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
   * Required. The bucket of Amazon S3.
   *
   * @param string $bucket
   */
  public function setBucket($bucket)
  {
    $this->bucket = $bucket;
  }
  /**
   * @return string
   */
  public function getBucket()
  {
    return $this->bucket;
  }
  /**
   * Optional. The endpoint of Amazon S3.
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
   * Required. The region of Amazon S3.
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
   * Required. The scheme type of Amazon S3.
   *
   * Accepted values: SCHEME_TYPE_UNSPECIFIED, S3, S3A
   *
   * @param self::SCHEME_TYPE_* $schemeType
   */
  public function setSchemeType($schemeType)
  {
    $this->schemeType = $schemeType;
  }
  /**
   * @return self::SCHEME_TYPE_*
   */
  public function getSchemeType()
  {
    return $this->schemeType;
  }
  /**
   * Optional. The secret access key of Amazon S3.
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AmazonS3IcebergStorage::class, 'Google_Service_OracleDatabase_AmazonS3IcebergStorage');
