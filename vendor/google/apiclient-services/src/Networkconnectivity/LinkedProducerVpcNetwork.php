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

namespace Google\Service\Networkconnectivity;

class LinkedProducerVpcNetwork extends \Google\Collection
{
  protected $collection_key = 'includeExportRanges';
  /**
   * @var string[]
   */
  public $excludeExportRanges;
  /**
   * @var string[]
   */
  public $includeExportRanges;
  /**
   * @var string
   */
  public $network;
  /**
   * @var string
   */
  public $peering;
  /**
   * @var string
   */
  public $producerNetwork;
  /**
   * @var string
   */
  public $serviceConsumerVpcSpoke;

  /**
   * @param string[]
   */
  public function setExcludeExportRanges($excludeExportRanges)
  {
    $this->excludeExportRanges = $excludeExportRanges;
  }
  /**
   * @return string[]
   */
  public function getExcludeExportRanges()
  {
    return $this->excludeExportRanges;
  }
  /**
   * @param string[]
   */
  public function setIncludeExportRanges($includeExportRanges)
  {
    $this->includeExportRanges = $includeExportRanges;
  }
  /**
   * @return string[]
   */
  public function getIncludeExportRanges()
  {
    return $this->includeExportRanges;
  }
  /**
   * @param string
   */
  public function setNetwork($network)
  {
    $this->network = $network;
  }
  /**
   * @return string
   */
  public function getNetwork()
  {
    return $this->network;
  }
  /**
   * @param string
   */
  public function setPeering($peering)
  {
    $this->peering = $peering;
  }
  /**
   * @return string
   */
  public function getPeering()
  {
    return $this->peering;
  }
  /**
   * @param string
   */
  public function setProducerNetwork($producerNetwork)
  {
    $this->producerNetwork = $producerNetwork;
  }
  /**
   * @return string
   */
  public function getProducerNetwork()
  {
    return $this->producerNetwork;
  }
  /**
   * @param string
   */
  public function setServiceConsumerVpcSpoke($serviceConsumerVpcSpoke)
  {
    $this->serviceConsumerVpcSpoke = $serviceConsumerVpcSpoke;
  }
  /**
   * @return string
   */
  public function getServiceConsumerVpcSpoke()
  {
    return $this->serviceConsumerVpcSpoke;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(LinkedProducerVpcNetwork::class, 'Google_Service_Networkconnectivity_LinkedProducerVpcNetwork');
