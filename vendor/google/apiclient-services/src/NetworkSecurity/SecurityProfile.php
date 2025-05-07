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

namespace Google\Service\NetworkSecurity;

class SecurityProfile extends \Google\Model
{
  /**
   * @var string
   */
  public $createTime;
  protected $customInterceptProfileType = CustomInterceptProfile::class;
  protected $customInterceptProfileDataType = '';
  protected $customMirroringProfileType = CustomMirroringProfile::class;
  protected $customMirroringProfileDataType = '';
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $etag;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $name;
  protected $threatPreventionProfileType = ThreatPreventionProfile::class;
  protected $threatPreventionProfileDataType = '';
  /**
   * @var string
   */
  public $type;
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param CustomInterceptProfile
   */
  public function setCustomInterceptProfile(CustomInterceptProfile $customInterceptProfile)
  {
    $this->customInterceptProfile = $customInterceptProfile;
  }
  /**
   * @return CustomInterceptProfile
   */
  public function getCustomInterceptProfile()
  {
    return $this->customInterceptProfile;
  }
  /**
   * @param CustomMirroringProfile
   */
  public function setCustomMirroringProfile(CustomMirroringProfile $customMirroringProfile)
  {
    $this->customMirroringProfile = $customMirroringProfile;
  }
  /**
   * @return CustomMirroringProfile
   */
  public function getCustomMirroringProfile()
  {
    return $this->customMirroringProfile;
  }
  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * @param string
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * @param string[]
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param ThreatPreventionProfile
   */
  public function setThreatPreventionProfile(ThreatPreventionProfile $threatPreventionProfile)
  {
    $this->threatPreventionProfile = $threatPreventionProfile;
  }
  /**
   * @return ThreatPreventionProfile
   */
  public function getThreatPreventionProfile()
  {
    return $this->threatPreventionProfile;
  }
  /**
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
  /**
   * @param string
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SecurityProfile::class, 'Google_Service_NetworkSecurity_SecurityProfile');
