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

namespace Google\Service\DisplayVideo;

class DcmTrackingInfo extends \Google\Model
{
  /**
   * Required. The DCM creative id.
   *
   * @var string
   */
  public $creativeId;
  /**
   * Required. The DCM placement id.
   *
   * @var string
   */
  public $placementId;
  /**
   * Required. The DCM tracking ad id.
   *
   * @var string
   */
  public $trackingAdId;

  /**
   * Required. The DCM creative id.
   *
   * @param string $creativeId
   */
  public function setCreativeId($creativeId)
  {
    $this->creativeId = $creativeId;
  }
  /**
   * @return string
   */
  public function getCreativeId()
  {
    return $this->creativeId;
  }
  /**
   * Required. The DCM placement id.
   *
   * @param string $placementId
   */
  public function setPlacementId($placementId)
  {
    $this->placementId = $placementId;
  }
  /**
   * @return string
   */
  public function getPlacementId()
  {
    return $this->placementId;
  }
  /**
   * Required. The DCM tracking ad id.
   *
   * @param string $trackingAdId
   */
  public function setTrackingAdId($trackingAdId)
  {
    $this->trackingAdId = $trackingAdId;
  }
  /**
   * @return string
   */
  public function getTrackingAdId()
  {
    return $this->trackingAdId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DcmTrackingInfo::class, 'Google_Service_DisplayVideo_DcmTrackingInfo');
