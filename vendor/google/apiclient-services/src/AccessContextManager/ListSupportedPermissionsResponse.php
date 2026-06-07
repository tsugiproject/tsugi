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

namespace Google\Service\AccessContextManager;

class ListSupportedPermissionsResponse extends \Google\Collection
{
  protected $collection_key = 'supportedPermissions';
  /**
   * Use this pagination token to retrieve the next page of results. An empty
   * value indicates that no further results are available.
   *
   * @var string
   */
  public $nextPageToken;
  /**
   * List of VPC Service Controls supported permissions.
   *
   * @var string[]
   */
  public $supportedPermissions;

  /**
   * Use this pagination token to retrieve the next page of results. An empty
   * value indicates that no further results are available.
   *
   * @param string $nextPageToken
   */
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  /**
   * @return string
   */
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  /**
   * List of VPC Service Controls supported permissions.
   *
   * @param string[] $supportedPermissions
   */
  public function setSupportedPermissions($supportedPermissions)
  {
    $this->supportedPermissions = $supportedPermissions;
  }
  /**
   * @return string[]
   */
  public function getSupportedPermissions()
  {
    return $this->supportedPermissions;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ListSupportedPermissionsResponse::class, 'Google_Service_AccessContextManager_ListSupportedPermissionsResponse');
