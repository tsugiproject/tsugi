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

namespace Google\Service\CloudRun;

class GoogleDevtoolsCloudbuildV1UploadedGenericArtifact extends \Google\Model
{
  protected $artifactFingerprintType = GoogleDevtoolsCloudbuildV1FileHashes::class;
  protected $artifactFingerprintDataType = '';
  /**
   * Output only. Path to the artifact in Artifact Registry.
   *
   * @var string
   */
  public $artifactRegistryPackage;
  protected $fileHashesType = GoogleDevtoolsCloudbuildV1FileHashes::class;
  protected $fileHashesDataType = 'map';
  protected $pushTimingType = GoogleDevtoolsCloudbuildV1TimeSpan::class;
  protected $pushTimingDataType = '';
  /**
   * Output only. URI of the uploaded artifact. Ex:
   * projects/p1/locations/us/repositories/r1/packages/p1/versions/v1
   *
   * @var string
   */
  public $uri;

  /**
   * Output only. The hash of the whole artifact.
   *
   * @param GoogleDevtoolsCloudbuildV1FileHashes $artifactFingerprint
   */
  public function setArtifactFingerprint(GoogleDevtoolsCloudbuildV1FileHashes $artifactFingerprint)
  {
    $this->artifactFingerprint = $artifactFingerprint;
  }
  /**
   * @return GoogleDevtoolsCloudbuildV1FileHashes
   */
  public function getArtifactFingerprint()
  {
    return $this->artifactFingerprint;
  }
  /**
   * Output only. Path to the artifact in Artifact Registry.
   *
   * @param string $artifactRegistryPackage
   */
  public function setArtifactRegistryPackage($artifactRegistryPackage)
  {
    $this->artifactRegistryPackage = $artifactRegistryPackage;
  }
  /**
   * @return string
   */
  public function getArtifactRegistryPackage()
  {
    return $this->artifactRegistryPackage;
  }
  /**
   * Output only. The file hashes that make up the generic artifact.
   *
   * @param GoogleDevtoolsCloudbuildV1FileHashes[] $fileHashes
   */
  public function setFileHashes($fileHashes)
  {
    $this->fileHashes = $fileHashes;
  }
  /**
   * @return GoogleDevtoolsCloudbuildV1FileHashes[]
   */
  public function getFileHashes()
  {
    return $this->fileHashes;
  }
  /**
   * Output only. Stores timing information for pushing the specified artifact.
   *
   * @param GoogleDevtoolsCloudbuildV1TimeSpan $pushTiming
   */
  public function setPushTiming(GoogleDevtoolsCloudbuildV1TimeSpan $pushTiming)
  {
    $this->pushTiming = $pushTiming;
  }
  /**
   * @return GoogleDevtoolsCloudbuildV1TimeSpan
   */
  public function getPushTiming()
  {
    return $this->pushTiming;
  }
  /**
   * Output only. URI of the uploaded artifact. Ex:
   * projects/p1/locations/us/repositories/r1/packages/p1/versions/v1
   *
   * @param string $uri
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleDevtoolsCloudbuildV1UploadedGenericArtifact::class, 'Google_Service_CloudRun_GoogleDevtoolsCloudbuildV1UploadedGenericArtifact');
