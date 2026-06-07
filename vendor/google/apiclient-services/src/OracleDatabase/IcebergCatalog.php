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

class IcebergCatalog extends \Google\Model
{
  /**
   * Catalog type not specified.
   */
  public const CATALOG_TYPE_CATALOG_TYPE_UNSPECIFIED = 'CATALOG_TYPE_UNSPECIFIED';
  /**
   * Glue catalog.
   */
  public const CATALOG_TYPE_GLUE = 'GLUE';
  /**
   * Hadoop catalog.
   */
  public const CATALOG_TYPE_HADOOP = 'HADOOP';
  /**
   * Nessie catalog.
   */
  public const CATALOG_TYPE_NESSIE = 'NESSIE';
  /**
   * Polaris catalog.
   */
  public const CATALOG_TYPE_POLARIS = 'POLARIS';
  /**
   * REST catalog.
   */
  public const CATALOG_TYPE_REST = 'REST';
  /**
   * Required. The type of Iceberg catalog.
   *
   * @var string
   */
  public $catalogType;
  protected $glueIcebergCatalogType = GlueIcebergCatalog::class;
  protected $glueIcebergCatalogDataType = '';
  protected $nessieIcebergCatalogType = NessieIcebergCatalog::class;
  protected $nessieIcebergCatalogDataType = '';
  protected $polarisIcebergCatalogType = PolarisIcebergCatalog::class;
  protected $polarisIcebergCatalogDataType = '';
  protected $restIcebergCatalogType = RestIcebergCatalog::class;
  protected $restIcebergCatalogDataType = '';

  /**
   * Required. The type of Iceberg catalog.
   *
   * Accepted values: CATALOG_TYPE_UNSPECIFIED, GLUE, HADOOP, NESSIE, POLARIS,
   * REST
   *
   * @param self::CATALOG_TYPE_* $catalogType
   */
  public function setCatalogType($catalogType)
  {
    $this->catalogType = $catalogType;
  }
  /**
   * @return self::CATALOG_TYPE_*
   */
  public function getCatalogType()
  {
    return $this->catalogType;
  }
  /**
   * The Glue Iceberg catalog.
   *
   * @param GlueIcebergCatalog $glueIcebergCatalog
   */
  public function setGlueIcebergCatalog(GlueIcebergCatalog $glueIcebergCatalog)
  {
    $this->glueIcebergCatalog = $glueIcebergCatalog;
  }
  /**
   * @return GlueIcebergCatalog
   */
  public function getGlueIcebergCatalog()
  {
    return $this->glueIcebergCatalog;
  }
  /**
   * The Nessie Iceberg catalog.
   *
   * @param NessieIcebergCatalog $nessieIcebergCatalog
   */
  public function setNessieIcebergCatalog(NessieIcebergCatalog $nessieIcebergCatalog)
  {
    $this->nessieIcebergCatalog = $nessieIcebergCatalog;
  }
  /**
   * @return NessieIcebergCatalog
   */
  public function getNessieIcebergCatalog()
  {
    return $this->nessieIcebergCatalog;
  }
  /**
   * The Polaris Iceberg catalog.
   *
   * @param PolarisIcebergCatalog $polarisIcebergCatalog
   */
  public function setPolarisIcebergCatalog(PolarisIcebergCatalog $polarisIcebergCatalog)
  {
    $this->polarisIcebergCatalog = $polarisIcebergCatalog;
  }
  /**
   * @return PolarisIcebergCatalog
   */
  public function getPolarisIcebergCatalog()
  {
    return $this->polarisIcebergCatalog;
  }
  /**
   * The REST Iceberg catalog.
   *
   * @param RestIcebergCatalog $restIcebergCatalog
   */
  public function setRestIcebergCatalog(RestIcebergCatalog $restIcebergCatalog)
  {
    $this->restIcebergCatalog = $restIcebergCatalog;
  }
  /**
   * @return RestIcebergCatalog
   */
  public function getRestIcebergCatalog()
  {
    return $this->restIcebergCatalog;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(IcebergCatalog::class, 'Google_Service_OracleDatabase_IcebergCatalog');
