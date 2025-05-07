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

namespace Google\Service\BeyondCorp;

class GoogleCloudBeyondcorpAppconnectionsV1AppConnection extends \Google\Collection
{
  protected $collection_key = 'connectors';
  protected $applicationEndpointType = GoogleCloudBeyondcorpAppconnectionsV1AppConnectionApplicationEndpoint::class;
  protected $applicationEndpointDataType = '';
  /**
   * @var string[]
   */
  public $connectors;
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $displayName;
  protected $gatewayType = GoogleCloudBeyondcorpAppconnectionsV1AppConnectionGateway::class;
  protected $gatewayDataType = '';
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $name;
  /**
   * @var bool
   */
  public $satisfiesPzi;
  /**
   * @var bool
   */
  public $satisfiesPzs;
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $type;
  /**
   * @var string
   */
  public $uid;
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param GoogleCloudBeyondcorpAppconnectionsV1AppConnectionApplicationEndpoint
   */
  public function setApplicationEndpoint(GoogleCloudBeyondcorpAppconnectionsV1AppConnectionApplicationEndpoint $applicationEndpoint)
  {
    $this->applicationEndpoint = $applicationEndpoint;
  }
  /**
   * @return GoogleCloudBeyondcorpAppconnectionsV1AppConnectionApplicationEndpoint
   */
  public function getApplicationEndpoint()
  {
    return $this->applicationEndpoint;
  }
  /**
   * @param string[]
   */
  public function setConnectors($connectors)
  {
    $this->connectors = $connectors;
  }
  /**
   * @return string[]
   */
  public function getConnectors()
  {
    return $this->connectors;
  }
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
   * @param string
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * @param GoogleCloudBeyondcorpAppconnectionsV1AppConnectionGateway
   */
  public function setGateway(GoogleCloudBeyondcorpAppconnectionsV1AppConnectionGateway $gateway)
  {
    $this->gateway = $gateway;
  }
  /**
   * @return GoogleCloudBeyondcorpAppconnectionsV1AppConnectionGateway
   */
  public function getGateway()
  {
    return $this->gateway;
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
   * @param bool
   */
  public function setSatisfiesPzi($satisfiesPzi)
  {
    $this->satisfiesPzi = $satisfiesPzi;
  }
  /**
   * @return bool
   */
  public function getSatisfiesPzi()
  {
    return $this->satisfiesPzi;
  }
  /**
   * @param bool
   */
  public function setSatisfiesPzs($satisfiesPzs)
  {
    $this->satisfiesPzs = $satisfiesPzs;
  }
  /**
   * @return bool
   */
  public function getSatisfiesPzs()
  {
    return $this->satisfiesPzs;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
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
  public function setUid($uid)
  {
    $this->uid = $uid;
  }
  /**
   * @return string
   */
  public function getUid()
  {
    return $this->uid;
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
class_alias(GoogleCloudBeyondcorpAppconnectionsV1AppConnection::class, 'Google_Service_BeyondCorp_GoogleCloudBeyondcorpAppconnectionsV1AppConnection');
