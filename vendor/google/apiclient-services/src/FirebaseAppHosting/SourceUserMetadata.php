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

namespace Google\Service\FirebaseAppHosting;

class SourceUserMetadata extends \Google\Model
{
  /**
   * Output only. Deprecated: Not used. The user-chosen displayname. May be
   * empty.
   *
   * @deprecated
   * @var string
   */
  public $displayName;
  /**
   * Output only. Deprecated: Not used. The account email linked to the EUC that
   * created the build. May be a service account or other robot account.
   *
   * @deprecated
   * @var string
   */
  public $email;
  /**
   * Output only. Deprecated: Not used. The URI of a profile photo associated
   * with the user who created the build.
   *
   * @deprecated
   * @var string
   */
  public $imageUri;

  /**
   * Output only. Deprecated: Not used. The user-chosen displayname. May be
   * empty.
   *
   * @deprecated
   * @param string $displayName
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @deprecated
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * Output only. Deprecated: Not used. The account email linked to the EUC that
   * created the build. May be a service account or other robot account.
   *
   * @deprecated
   * @param string $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }
  /**
   * @deprecated
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  }
  /**
   * Output only. Deprecated: Not used. The URI of a profile photo associated
   * with the user who created the build.
   *
   * @deprecated
   * @param string $imageUri
   */
  public function setImageUri($imageUri)
  {
    $this->imageUri = $imageUri;
  }
  /**
   * @deprecated
   * @return string
   */
  public function getImageUri()
  {
    return $this->imageUri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SourceUserMetadata::class, 'Google_Service_FirebaseAppHosting_SourceUserMetadata');
