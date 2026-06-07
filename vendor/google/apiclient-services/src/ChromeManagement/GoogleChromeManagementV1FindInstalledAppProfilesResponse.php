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

namespace Google\Service\ChromeManagement;

class GoogleChromeManagementV1FindInstalledAppProfilesResponse extends \Google\Collection
{
  protected $collection_key = 'profiles';
  /**
   * Token to specify the next page of the request.
   *
   * @var string
   */
  public $nextPageToken;
  protected $profilesType = GoogleChromeManagementV1ProfileAppInstallInstance::class;
  protected $profilesDataType = 'array';
  /**
   * Total number of profiles matching request.
   *
   * @var int
   */
  public $totalSize;

  /**
   * Token to specify the next page of the request.
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
   * A list of profiles which have the app installed. Sorted in ascending
   * alphabetical order on the profile.Email field.
   *
   * @param GoogleChromeManagementV1ProfileAppInstallInstance[] $profiles
   */
  public function setProfiles($profiles)
  {
    $this->profiles = $profiles;
  }
  /**
   * @return GoogleChromeManagementV1ProfileAppInstallInstance[]
   */
  public function getProfiles()
  {
    return $this->profiles;
  }
  /**
   * Total number of profiles matching request.
   *
   * @param int $totalSize
   */
  public function setTotalSize($totalSize)
  {
    $this->totalSize = $totalSize;
  }
  /**
   * @return int
   */
  public function getTotalSize()
  {
    return $this->totalSize;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleChromeManagementV1FindInstalledAppProfilesResponse::class, 'Google_Service_ChromeManagement_GoogleChromeManagementV1FindInstalledAppProfilesResponse');
