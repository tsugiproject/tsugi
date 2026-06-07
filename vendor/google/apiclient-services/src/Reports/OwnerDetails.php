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

namespace Google\Service\Reports;

class OwnerDetails extends \Google\Collection
{
  protected $collection_key = 'ownerIdentity';
  protected $ownerIdentityType = OwnerIdentity::class;
  protected $ownerIdentityDataType = 'array';
  /**
   * Type of the owner of the resource.
   *
   * @var string
   */
  public $ownerType;

  /**
   * Identity details of the owner(s) of the resource.
   *
   * @param OwnerIdentity[] $ownerIdentity
   */
  public function setOwnerIdentity($ownerIdentity)
  {
    $this->ownerIdentity = $ownerIdentity;
  }
  /**
   * @return OwnerIdentity[]
   */
  public function getOwnerIdentity()
  {
    return $this->ownerIdentity;
  }
  /**
   * Type of the owner of the resource.
   *
   * @param string $ownerType
   */
  public function setOwnerType($ownerType)
  {
    $this->ownerType = $ownerType;
  }
  /**
   * @return string
   */
  public function getOwnerType()
  {
    return $this->ownerType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(OwnerDetails::class, 'Google_Service_Reports_OwnerDetails');
