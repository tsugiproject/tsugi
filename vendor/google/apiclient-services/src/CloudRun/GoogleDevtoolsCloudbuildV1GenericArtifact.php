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

class GoogleDevtoolsCloudbuildV1GenericArtifact extends \Google\Model
{
  /**
   * Required. Path to the generic artifact in the build's workspace to be
   * uploaded to Artifact Registry.
   *
   * @var string
   */
  public $folder;
  /**
   * Required. Registry path to upload the generic artifact to, in the form proj
   * ects/$PROJECT/locations/$LOCATION/repositories/$REPO/packages/$PACKAGE/vers
   * ions/$VERSION
   *
   * @var string
   */
  public $registryPath;

  /**
   * Required. Path to the generic artifact in the build's workspace to be
   * uploaded to Artifact Registry.
   *
   * @param string $folder
   */
  public function setFolder($folder)
  {
    $this->folder = $folder;
  }
  /**
   * @return string
   */
  public function getFolder()
  {
    return $this->folder;
  }
  /**
   * Required. Registry path to upload the generic artifact to, in the form proj
   * ects/$PROJECT/locations/$LOCATION/repositories/$REPO/packages/$PACKAGE/vers
   * ions/$VERSION
   *
   * @param string $registryPath
   */
  public function setRegistryPath($registryPath)
  {
    $this->registryPath = $registryPath;
  }
  /**
   * @return string
   */
  public function getRegistryPath()
  {
    return $this->registryPath;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleDevtoolsCloudbuildV1GenericArtifact::class, 'Google_Service_CloudRun_GoogleDevtoolsCloudbuildV1GenericArtifact');
