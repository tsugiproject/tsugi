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

namespace Google\Service\Compute;

class RolloutWaveDetails extends \Google\Model
{
  protected $orchestratedWaveDetailsType = RolloutWaveDetailsOrchestratedWaveDetails::class;
  protected $orchestratedWaveDetailsDataType = '';
  /**
   * Output only. Wave name. Ex. wave1
   *
   * @var string
   */
  public $waveDisplayName;
  /**
   * Output only. System generated number for the wave.
   *
   * @var string
   */
  public $waveNumber;

  /**
   * Output only. Additional details of the wave for products using the
   * Orchestrated Integration model.
   *
   * @param RolloutWaveDetailsOrchestratedWaveDetails $orchestratedWaveDetails
   */
  public function setOrchestratedWaveDetails(RolloutWaveDetailsOrchestratedWaveDetails $orchestratedWaveDetails)
  {
    $this->orchestratedWaveDetails = $orchestratedWaveDetails;
  }
  /**
   * @return RolloutWaveDetailsOrchestratedWaveDetails
   */
  public function getOrchestratedWaveDetails()
  {
    return $this->orchestratedWaveDetails;
  }
  /**
   * Output only. Wave name. Ex. wave1
   *
   * @param string $waveDisplayName
   */
  public function setWaveDisplayName($waveDisplayName)
  {
    $this->waveDisplayName = $waveDisplayName;
  }
  /**
   * @return string
   */
  public function getWaveDisplayName()
  {
    return $this->waveDisplayName;
  }
  /**
   * Output only. System generated number for the wave.
   *
   * @param string $waveNumber
   */
  public function setWaveNumber($waveNumber)
  {
    $this->waveNumber = $waveNumber;
  }
  /**
   * @return string
   */
  public function getWaveNumber()
  {
    return $this->waveNumber;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutWaveDetails::class, 'Google_Service_Compute_RolloutWaveDetails');
