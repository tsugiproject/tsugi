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

namespace Google\Service\Apigee;

class GoogleCloudApigeeV1ListSecurityProfilesV2Response extends \Google\Collection
{
  protected $collection_key = 'securityProfilesV2';
  /**
   * @var string
   */
  public $nextPageToken;
  protected $securityProfilesV2Type = GoogleCloudApigeeV1SecurityProfileV2::class;
  protected $securityProfilesV2DataType = 'array';

  /**
   * @param string
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
   * @param GoogleCloudApigeeV1SecurityProfileV2[]
   */
  public function setSecurityProfilesV2($securityProfilesV2)
  {
    $this->securityProfilesV2 = $securityProfilesV2;
  }
  /**
   * @return GoogleCloudApigeeV1SecurityProfileV2[]
   */
  public function getSecurityProfilesV2()
  {
    return $this->securityProfilesV2;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudApigeeV1ListSecurityProfilesV2Response::class, 'Google_Service_Apigee_GoogleCloudApigeeV1ListSecurityProfilesV2Response');
