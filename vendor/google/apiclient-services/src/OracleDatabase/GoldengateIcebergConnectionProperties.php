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

namespace Google\Service\OracleDatabase;

class GoldengateIcebergConnectionProperties extends \Google\Model
{
  protected $catalogType = IcebergCatalog::class;
  protected $catalogDataType = '';
  protected $storageType = IcebergStorage::class;
  protected $storageDataType = '';
  /**
   * Required. The technology type of Iceberg connection.
   *
   * @var string
   */
  public $technologyType;

  /**
   * Required. The Iceberg catalog.
   *
   * @param IcebergCatalog $catalog
   */
  public function setCatalog(IcebergCatalog $catalog)
  {
    $this->catalog = $catalog;
  }
  /**
   * @return IcebergCatalog
   */
  public function getCatalog()
  {
    return $this->catalog;
  }
  /**
   * Required. The Iceberg storage.
   *
   * @param IcebergStorage $storage
   */
  public function setStorage(IcebergStorage $storage)
  {
    $this->storage = $storage;
  }
  /**
   * @return IcebergStorage
   */
  public function getStorage()
  {
    return $this->storage;
  }
  /**
   * Required. The technology type of Iceberg connection.
   *
   * @param string $technologyType
   */
  public function setTechnologyType($technologyType)
  {
    $this->technologyType = $technologyType;
  }
  /**
   * @return string
   */
  public function getTechnologyType()
  {
    return $this->technologyType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateIcebergConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateIcebergConnectionProperties');
