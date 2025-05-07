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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1ReasoningEngineSpecPackageSpec extends \Google\Model
{
  /**
   * @var string
   */
  public $dependencyFilesGcsUri;
  /**
   * @var string
   */
  public $pickleObjectGcsUri;
  /**
   * @var string
   */
  public $pythonVersion;
  /**
   * @var string
   */
  public $requirementsGcsUri;

  /**
   * @param string
   */
  public function setDependencyFilesGcsUri($dependencyFilesGcsUri)
  {
    $this->dependencyFilesGcsUri = $dependencyFilesGcsUri;
  }
  /**
   * @return string
   */
  public function getDependencyFilesGcsUri()
  {
    return $this->dependencyFilesGcsUri;
  }
  /**
   * @param string
   */
  public function setPickleObjectGcsUri($pickleObjectGcsUri)
  {
    $this->pickleObjectGcsUri = $pickleObjectGcsUri;
  }
  /**
   * @return string
   */
  public function getPickleObjectGcsUri()
  {
    return $this->pickleObjectGcsUri;
  }
  /**
   * @param string
   */
  public function setPythonVersion($pythonVersion)
  {
    $this->pythonVersion = $pythonVersion;
  }
  /**
   * @return string
   */
  public function getPythonVersion()
  {
    return $this->pythonVersion;
  }
  /**
   * @param string
   */
  public function setRequirementsGcsUri($requirementsGcsUri)
  {
    $this->requirementsGcsUri = $requirementsGcsUri;
  }
  /**
   * @return string
   */
  public function getRequirementsGcsUri()
  {
    return $this->requirementsGcsUri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1ReasoningEngineSpecPackageSpec::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1ReasoningEngineSpecPackageSpec');
