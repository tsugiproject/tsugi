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

namespace Google\Service\BigQueryReservation;

class Reservation extends \Google\Model
{
  protected $autoscaleType = Autoscale::class;
  protected $autoscaleDataType = '';
  /**
   * @var string
   */
  public $concurrency;
  /**
   * @var string
   */
  public $creationTime;
  /**
   * @var string
   */
  public $edition;
  /**
   * @var bool
   */
  public $ignoreIdleSlots;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var bool
   */
  public $multiRegionAuxiliary;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $originalPrimaryLocation;
  /**
   * @var string
   */
  public $primaryLocation;
  protected $replicationStatusType = ReplicationStatus::class;
  protected $replicationStatusDataType = '';
  /**
   * @var string
   */
  public $secondaryLocation;
  /**
   * @var string
   */
  public $slotCapacity;
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param Autoscale
   */
  public function setAutoscale(Autoscale $autoscale)
  {
    $this->autoscale = $autoscale;
  }
  /**
   * @return Autoscale
   */
  public function getAutoscale()
  {
    return $this->autoscale;
  }
  /**
   * @param string
   */
  public function setConcurrency($concurrency)
  {
    $this->concurrency = $concurrency;
  }
  /**
   * @return string
   */
  public function getConcurrency()
  {
    return $this->concurrency;
  }
  /**
   * @param string
   */
  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  /**
   * @return string
   */
  public function getCreationTime()
  {
    return $this->creationTime;
  }
  /**
   * @param string
   */
  public function setEdition($edition)
  {
    $this->edition = $edition;
  }
  /**
   * @return string
   */
  public function getEdition()
  {
    return $this->edition;
  }
  /**
   * @param bool
   */
  public function setIgnoreIdleSlots($ignoreIdleSlots)
  {
    $this->ignoreIdleSlots = $ignoreIdleSlots;
  }
  /**
   * @return bool
   */
  public function getIgnoreIdleSlots()
  {
    return $this->ignoreIdleSlots;
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
   * @param bool
   */
  public function setMultiRegionAuxiliary($multiRegionAuxiliary)
  {
    $this->multiRegionAuxiliary = $multiRegionAuxiliary;
  }
  /**
   * @return bool
   */
  public function getMultiRegionAuxiliary()
  {
    return $this->multiRegionAuxiliary;
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
   * @param string
   */
  public function setOriginalPrimaryLocation($originalPrimaryLocation)
  {
    $this->originalPrimaryLocation = $originalPrimaryLocation;
  }
  /**
   * @return string
   */
  public function getOriginalPrimaryLocation()
  {
    return $this->originalPrimaryLocation;
  }
  /**
   * @param string
   */
  public function setPrimaryLocation($primaryLocation)
  {
    $this->primaryLocation = $primaryLocation;
  }
  /**
   * @return string
   */
  public function getPrimaryLocation()
  {
    return $this->primaryLocation;
  }
  /**
   * @param ReplicationStatus
   */
  public function setReplicationStatus(ReplicationStatus $replicationStatus)
  {
    $this->replicationStatus = $replicationStatus;
  }
  /**
   * @return ReplicationStatus
   */
  public function getReplicationStatus()
  {
    return $this->replicationStatus;
  }
  /**
   * @param string
   */
  public function setSecondaryLocation($secondaryLocation)
  {
    $this->secondaryLocation = $secondaryLocation;
  }
  /**
   * @return string
   */
  public function getSecondaryLocation()
  {
    return $this->secondaryLocation;
  }
  /**
   * @param string
   */
  public function setSlotCapacity($slotCapacity)
  {
    $this->slotCapacity = $slotCapacity;
  }
  /**
   * @return string
   */
  public function getSlotCapacity()
  {
    return $this->slotCapacity;
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
class_alias(Reservation::class, 'Google_Service_BigQueryReservation_Reservation');
