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

namespace Google\Service\CloudSecurityToken;

class GoogleIdentityStsV1Options extends \Google\Model
{
  protected $accessBoundaryType = GoogleIdentityStsV1AccessBoundary::class;
  protected $accessBoundaryDataType = '';
  /**
   * @var string
   */
  public $userProject;

  /**
   * @param GoogleIdentityStsV1AccessBoundary
   */
  public function setAccessBoundary(GoogleIdentityStsV1AccessBoundary $accessBoundary)
  {
    $this->accessBoundary = $accessBoundary;
  }
  /**
   * @return GoogleIdentityStsV1AccessBoundary
   */
  public function getAccessBoundary()
  {
    return $this->accessBoundary;
  }
  /**
   * @param string
   */
  public function setUserProject($userProject)
  {
    $this->userProject = $userProject;
  }
  /**
   * @return string
   */
  public function getUserProject()
  {
    return $this->userProject;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleIdentityStsV1Options::class, 'Google_Service_CloudSecurityToken_GoogleIdentityStsV1Options');
