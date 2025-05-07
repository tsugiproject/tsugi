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

namespace Google\Service\AndroidProvisioningPartner;

class DeviceIdentifier extends \Google\Model
{
  /**
   * @var string
   */
  public $chromeOsAttestedDeviceId;
  /**
   * @var string
   */
  public $deviceType;
  /**
   * @var string
   */
  public $imei;
  /**
   * @var string
   */
  public $imei2;
  /**
   * @var string
   */
  public $manufacturer;
  /**
   * @var string
   */
  public $meid;
  /**
   * @var string
   */
  public $meid2;
  /**
   * @var string
   */
  public $model;
  /**
   * @var string
   */
  public $serialNumber;

  /**
   * @param string
   */
  public function setChromeOsAttestedDeviceId($chromeOsAttestedDeviceId)
  {
    $this->chromeOsAttestedDeviceId = $chromeOsAttestedDeviceId;
  }
  /**
   * @return string
   */
  public function getChromeOsAttestedDeviceId()
  {
    return $this->chromeOsAttestedDeviceId;
  }
  /**
   * @param string
   */
  public function setDeviceType($deviceType)
  {
    $this->deviceType = $deviceType;
  }
  /**
   * @return string
   */
  public function getDeviceType()
  {
    return $this->deviceType;
  }
  /**
   * @param string
   */
  public function setImei($imei)
  {
    $this->imei = $imei;
  }
  /**
   * @return string
   */
  public function getImei()
  {
    return $this->imei;
  }
  /**
   * @param string
   */
  public function setImei2($imei2)
  {
    $this->imei2 = $imei2;
  }
  /**
   * @return string
   */
  public function getImei2()
  {
    return $this->imei2;
  }
  /**
   * @param string
   */
  public function setManufacturer($manufacturer)
  {
    $this->manufacturer = $manufacturer;
  }
  /**
   * @return string
   */
  public function getManufacturer()
  {
    return $this->manufacturer;
  }
  /**
   * @param string
   */
  public function setMeid($meid)
  {
    $this->meid = $meid;
  }
  /**
   * @return string
   */
  public function getMeid()
  {
    return $this->meid;
  }
  /**
   * @param string
   */
  public function setMeid2($meid2)
  {
    $this->meid2 = $meid2;
  }
  /**
   * @return string
   */
  public function getMeid2()
  {
    return $this->meid2;
  }
  /**
   * @param string
   */
  public function setModel($model)
  {
    $this->model = $model;
  }
  /**
   * @return string
   */
  public function getModel()
  {
    return $this->model;
  }
  /**
   * @param string
   */
  public function setSerialNumber($serialNumber)
  {
    $this->serialNumber = $serialNumber;
  }
  /**
   * @return string
   */
  public function getSerialNumber()
  {
    return $this->serialNumber;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeviceIdentifier::class, 'Google_Service_AndroidProvisioningPartner_DeviceIdentifier');
