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

namespace Google\Service\CloudNumberRegistry;

class IpamAdminScopeAvailability extends \Google\Model
{
  /**
   * Unspecified availability.
   */
  public const AVAILABILITY_AVAILABILITY_UNSPECIFIED = 'AVAILABILITY_UNSPECIFIED';
  /**
   * The scope is available.
   */
  public const AVAILABILITY_AVAILABLE = 'AVAILABLE';
  /**
   * The scope is not available.
   */
  public const AVAILABILITY_UNAVAILABLE = 'UNAVAILABLE';
  /**
   * The admin project of the IpamAdminScope if it exists.
   *
   * @var string
   */
  public $adminProject;
  /**
   * The availability of the scope.
   *
   * @var string
   */
  public $availability;
  /**
   * The scope of the IpamAdminScope.
   *
   * @var string
   */
  public $scope;

  /**
   * The admin project of the IpamAdminScope if it exists.
   *
   * @param string $adminProject
   */
  public function setAdminProject($adminProject)
  {
    $this->adminProject = $adminProject;
  }
  /**
   * @return string
   */
  public function getAdminProject()
  {
    return $this->adminProject;
  }
  /**
   * The availability of the scope.
   *
   * Accepted values: AVAILABILITY_UNSPECIFIED, AVAILABLE, UNAVAILABLE
   *
   * @param self::AVAILABILITY_* $availability
   */
  public function setAvailability($availability)
  {
    $this->availability = $availability;
  }
  /**
   * @return self::AVAILABILITY_*
   */
  public function getAvailability()
  {
    return $this->availability;
  }
  /**
   * The scope of the IpamAdminScope.
   *
   * @param string $scope
   */
  public function setScope($scope)
  {
    $this->scope = $scope;
  }
  /**
   * @return string
   */
  public function getScope()
  {
    return $this->scope;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(IpamAdminScopeAvailability::class, 'Google_Service_CloudNumberRegistry_IpamAdminScopeAvailability');
