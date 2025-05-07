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

namespace Google\Service\DataCatalog;

class GoogleCloudDatacatalogV1EntryGroup extends \Google\Model
{
  protected $dataCatalogTimestampsType = GoogleCloudDatacatalogV1SystemTimestamps::class;
  protected $dataCatalogTimestampsDataType = '';
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $displayName;
  /**
   * @var string
   */
  public $name;
  /**
   * @var bool
   */
  public $transferredToDataplex;

  /**
   * @param GoogleCloudDatacatalogV1SystemTimestamps
   */
  public function setDataCatalogTimestamps(GoogleCloudDatacatalogV1SystemTimestamps $dataCatalogTimestamps)
  {
    $this->dataCatalogTimestamps = $dataCatalogTimestamps;
  }
  /**
   * @return GoogleCloudDatacatalogV1SystemTimestamps
   */
  public function getDataCatalogTimestamps()
  {
    return $this->dataCatalogTimestamps;
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
  public function setTransferredToDataplex($transferredToDataplex)
  {
    $this->transferredToDataplex = $transferredToDataplex;
  }
  /**
   * @return bool
   */
  public function getTransferredToDataplex()
  {
    return $this->transferredToDataplex;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDatacatalogV1EntryGroup::class, 'Google_Service_DataCatalog_GoogleCloudDatacatalogV1EntryGroup');
