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

namespace Google\Service\NetAppFiles;

class HybridReplicationParameters extends \Google\Collection
{
  protected $collection_key = 'peerIpAddresses';
  /**
   * @var string
   */
  public $clusterLocation;
  /**
   * @var string
   */
  public $description;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $peerClusterName;
  /**
   * @var string[]
   */
  public $peerIpAddresses;
  /**
   * @var string
   */
  public $peerSvmName;
  /**
   * @var string
   */
  public $peerVolumeName;
  /**
   * @var string
   */
  public $replication;

  /**
   * @param string
   */
  public function setClusterLocation($clusterLocation)
  {
    $this->clusterLocation = $clusterLocation;
  }
  /**
   * @return string
   */
  public function getClusterLocation()
  {
    return $this->clusterLocation;
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
  public function setPeerClusterName($peerClusterName)
  {
    $this->peerClusterName = $peerClusterName;
  }
  /**
   * @return string
   */
  public function getPeerClusterName()
  {
    return $this->peerClusterName;
  }
  /**
   * @param string[]
   */
  public function setPeerIpAddresses($peerIpAddresses)
  {
    $this->peerIpAddresses = $peerIpAddresses;
  }
  /**
   * @return string[]
   */
  public function getPeerIpAddresses()
  {
    return $this->peerIpAddresses;
  }
  /**
   * @param string
   */
  public function setPeerSvmName($peerSvmName)
  {
    $this->peerSvmName = $peerSvmName;
  }
  /**
   * @return string
   */
  public function getPeerSvmName()
  {
    return $this->peerSvmName;
  }
  /**
   * @param string
   */
  public function setPeerVolumeName($peerVolumeName)
  {
    $this->peerVolumeName = $peerVolumeName;
  }
  /**
   * @return string
   */
  public function getPeerVolumeName()
  {
    return $this->peerVolumeName;
  }
  /**
   * @param string
   */
  public function setReplication($replication)
  {
    $this->replication = $replication;
  }
  /**
   * @return string
   */
  public function getReplication()
  {
    return $this->replication;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HybridReplicationParameters::class, 'Google_Service_NetAppFiles_HybridReplicationParameters');
