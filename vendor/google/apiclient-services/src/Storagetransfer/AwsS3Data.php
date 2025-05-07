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

namespace Google\Service\Storagetransfer;

class AwsS3Data extends \Google\Model
{
  protected $awsAccessKeyType = AwsAccessKey::class;
  protected $awsAccessKeyDataType = '';
  /**
   * @var string
   */
  public $bucketName;
  /**
   * @var string
   */
  public $cloudfrontDomain;
  /**
   * @var string
   */
  public $credentialsSecret;
  /**
   * @var bool
   */
  public $managedPrivateNetwork;
  /**
   * @var string
   */
  public $path;
  /**
   * @var string
   */
  public $roleArn;

  /**
   * @param AwsAccessKey
   */
  public function setAwsAccessKey(AwsAccessKey $awsAccessKey)
  {
    $this->awsAccessKey = $awsAccessKey;
  }
  /**
   * @return AwsAccessKey
   */
  public function getAwsAccessKey()
  {
    return $this->awsAccessKey;
  }
  /**
   * @param string
   */
  public function setBucketName($bucketName)
  {
    $this->bucketName = $bucketName;
  }
  /**
   * @return string
   */
  public function getBucketName()
  {
    return $this->bucketName;
  }
  /**
   * @param string
   */
  public function setCloudfrontDomain($cloudfrontDomain)
  {
    $this->cloudfrontDomain = $cloudfrontDomain;
  }
  /**
   * @return string
   */
  public function getCloudfrontDomain()
  {
    return $this->cloudfrontDomain;
  }
  /**
   * @param string
   */
  public function setCredentialsSecret($credentialsSecret)
  {
    $this->credentialsSecret = $credentialsSecret;
  }
  /**
   * @return string
   */
  public function getCredentialsSecret()
  {
    return $this->credentialsSecret;
  }
  /**
   * @param bool
   */
  public function setManagedPrivateNetwork($managedPrivateNetwork)
  {
    $this->managedPrivateNetwork = $managedPrivateNetwork;
  }
  /**
   * @return bool
   */
  public function getManagedPrivateNetwork()
  {
    return $this->managedPrivateNetwork;
  }
  /**
   * @param string
   */
  public function setPath($path)
  {
    $this->path = $path;
  }
  /**
   * @return string
   */
  public function getPath()
  {
    return $this->path;
  }
  /**
   * @param string
   */
  public function setRoleArn($roleArn)
  {
    $this->roleArn = $roleArn;
  }
  /**
   * @return string
   */
  public function getRoleArn()
  {
    return $this->roleArn;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AwsS3Data::class, 'Google_Service_Storagetransfer_AwsS3Data');
