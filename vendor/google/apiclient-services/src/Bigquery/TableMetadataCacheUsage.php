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

namespace Google\Service\Bigquery;

class TableMetadataCacheUsage extends \Google\Model
{
  /**
   * @var string
   */
  public $explanation;
  /**
   * @var string
   */
  public $staleness;
  protected $tableReferenceType = TableReference::class;
  protected $tableReferenceDataType = '';
  /**
   * @var string
   */
  public $tableType;
  /**
   * @var string
   */
  public $unusedReason;

  /**
   * @param string
   */
  public function setExplanation($explanation)
  {
    $this->explanation = $explanation;
  }
  /**
   * @return string
   */
  public function getExplanation()
  {
    return $this->explanation;
  }
  /**
   * @param string
   */
  public function setStaleness($staleness)
  {
    $this->staleness = $staleness;
  }
  /**
   * @return string
   */
  public function getStaleness()
  {
    return $this->staleness;
  }
  /**
   * @param TableReference
   */
  public function setTableReference(TableReference $tableReference)
  {
    $this->tableReference = $tableReference;
  }
  /**
   * @return TableReference
   */
  public function getTableReference()
  {
    return $this->tableReference;
  }
  /**
   * @param string
   */
  public function setTableType($tableType)
  {
    $this->tableType = $tableType;
  }
  /**
   * @return string
   */
  public function getTableType()
  {
    return $this->tableType;
  }
  /**
   * @param string
   */
  public function setUnusedReason($unusedReason)
  {
    $this->unusedReason = $unusedReason;
  }
  /**
   * @return string
   */
  public function getUnusedReason()
  {
    return $this->unusedReason;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TableMetadataCacheUsage::class, 'Google_Service_Bigquery_TableMetadataCacheUsage');
