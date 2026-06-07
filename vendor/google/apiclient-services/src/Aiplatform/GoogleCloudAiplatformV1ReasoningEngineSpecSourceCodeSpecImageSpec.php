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

class GoogleCloudAiplatformV1ReasoningEngineSpecSourceCodeSpecImageSpec extends \Google\Model
{
  /**
   * Optional. Build arguments to be used. They will be passed through --build-
   * arg flags.
   *
   * @var string[]
   */
  public $buildArgs;

  /**
   * Optional. Build arguments to be used. They will be passed through --build-
   * arg flags.
   *
   * @param string[] $buildArgs
   */
  public function setBuildArgs($buildArgs)
  {
    $this->buildArgs = $buildArgs;
  }
  /**
   * @return string[]
   */
  public function getBuildArgs()
  {
    return $this->buildArgs;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1ReasoningEngineSpecSourceCodeSpecImageSpec::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1ReasoningEngineSpecSourceCodeSpecImageSpec');
