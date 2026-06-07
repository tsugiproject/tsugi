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

namespace Google\Service\Dataflow;

class Package extends \Google\Model
{
  /**
   * The resource to read the package from. The supported resource type is:
   * Google Cloud Storage: storage.googleapis.com/{bucket}
   * bucket.storage.googleapis.com/
   *
   * @var string
   */
  public $location;
  /**
   * The name of the package.
   *
   * @var string
   */
  public $name;
  /**
   * Optional. The hex-encoded SHA256 checksum of the package. If the checksum
   * is provided, the worker will verify the checksum of the package before
   * using it. If the checksum does not match, the worker will fail to start.
   *
   * @var string
   */
  public $sha256;

  /**
   * The resource to read the package from. The supported resource type is:
   * Google Cloud Storage: storage.googleapis.com/{bucket}
   * bucket.storage.googleapis.com/
   *
   * @param string $location
   */
  public function setLocation($location)
  {
    $this->location = $location;
  }
  /**
   * @return string
   */
  public function getLocation()
  {
    return $this->location;
  }
  /**
   * The name of the package.
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * Optional. The hex-encoded SHA256 checksum of the package. If the checksum
   * is provided, the worker will verify the checksum of the package before
   * using it. If the checksum does not match, the worker will fail to start.
   *
   * @param string $sha256
   */
  public function setSha256($sha256)
  {
    $this->sha256 = $sha256;
  }
  /**
   * @return string
   */
  public function getSha256()
  {
    return $this->sha256;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Package::class, 'Google_Service_Dataflow_Package');
