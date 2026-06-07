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

namespace Google\Service\ContainerAnalysis;

class ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGenericArtifactDependency extends \Google\Model
{
  /**
   * Required. Where the artifact files should be placed on the worker.
   *
   * @var string
   */
  public $destPath;
  /**
   * Required. The location to download the artifact files from. Ex:
   * projects/p1/locations/us/repositories/r1/packages/p1/versions/v1
   *
   * @var string
   */
  public $resource;

  /**
   * Required. Where the artifact files should be placed on the worker.
   *
   * @param string $destPath
   */
  public function setDestPath($destPath)
  {
    $this->destPath = $destPath;
  }
  /**
   * @return string
   */
  public function getDestPath()
  {
    return $this->destPath;
  }
  /**
   * Required. The location to download the artifact files from. Ex:
   * projects/p1/locations/us/repositories/r1/packages/p1/versions/v1
   *
   * @param string $resource
   */
  public function setResource($resource)
  {
    $this->resource = $resource;
  }
  /**
   * @return string
   */
  public function getResource()
  {
    return $this->resource;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGenericArtifactDependency::class, 'Google_Service_ContainerAnalysis_ContaineranalysisGoogleDevtoolsCloudbuildV1DependencyGenericArtifactDependency');
