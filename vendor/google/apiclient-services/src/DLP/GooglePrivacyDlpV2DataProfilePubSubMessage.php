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

namespace Google\Service\DLP;

class GooglePrivacyDlpV2DataProfilePubSubMessage extends \Google\Model
{
  /**
   * @var string
   */
  public $event;
  protected $fileStoreProfileType = GooglePrivacyDlpV2FileStoreDataProfile::class;
  protected $fileStoreProfileDataType = '';
  protected $profileType = GooglePrivacyDlpV2TableDataProfile::class;
  protected $profileDataType = '';

  /**
   * @param string
   */
  public function setEvent($event)
  {
    $this->event = $event;
  }
  /**
   * @return string
   */
  public function getEvent()
  {
    return $this->event;
  }
  /**
   * @param GooglePrivacyDlpV2FileStoreDataProfile
   */
  public function setFileStoreProfile(GooglePrivacyDlpV2FileStoreDataProfile $fileStoreProfile)
  {
    $this->fileStoreProfile = $fileStoreProfile;
  }
  /**
   * @return GooglePrivacyDlpV2FileStoreDataProfile
   */
  public function getFileStoreProfile()
  {
    return $this->fileStoreProfile;
  }
  /**
   * @param GooglePrivacyDlpV2TableDataProfile
   */
  public function setProfile(GooglePrivacyDlpV2TableDataProfile $profile)
  {
    $this->profile = $profile;
  }
  /**
   * @return GooglePrivacyDlpV2TableDataProfile
   */
  public function getProfile()
  {
    return $this->profile;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GooglePrivacyDlpV2DataProfilePubSubMessage::class, 'Google_Service_DLP_GooglePrivacyDlpV2DataProfilePubSubMessage');
