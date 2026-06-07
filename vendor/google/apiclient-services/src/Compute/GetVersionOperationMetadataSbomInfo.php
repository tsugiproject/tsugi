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

namespace Google\Service\Compute;

class GetVersionOperationMetadataSbomInfo extends \Google\Model
{
  /**
   * A mapping of components to their currently-applied versions or other
   * appropriate identifiers.
   *
   * @var string[]
   */
  public $currentComponentVersions;
  /**
   * A mapping of components to their target versions or other appropriate
   * identifiers.
   *
   * @var string[]
   */
  public $targetComponentVersions;

  /**
   * A mapping of components to their currently-applied versions or other
   * appropriate identifiers.
   *
   * @param string[] $currentComponentVersions
   */
  public function setCurrentComponentVersions($currentComponentVersions)
  {
    $this->currentComponentVersions = $currentComponentVersions;
  }
  /**
   * @return string[]
   */
  public function getCurrentComponentVersions()
  {
    return $this->currentComponentVersions;
  }
  /**
   * A mapping of components to their target versions or other appropriate
   * identifiers.
   *
   * @param string[] $targetComponentVersions
   */
  public function setTargetComponentVersions($targetComponentVersions)
  {
    $this->targetComponentVersions = $targetComponentVersions;
  }
  /**
   * @return string[]
   */
  public function getTargetComponentVersions()
  {
    return $this->targetComponentVersions;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GetVersionOperationMetadataSbomInfo::class, 'Google_Service_Compute_GetVersionOperationMetadataSbomInfo');
