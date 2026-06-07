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

class DmlStatistics extends \Google\Model
{
  /**
   * Default value. This value is unused.
   */
  public const DML_MODE_DML_MODE_UNSPECIFIED = 'DML_MODE_UNSPECIFIED';
  /**
   * Coarse-grained DML was used.
   */
  public const DML_MODE_COARSE_GRAINED_DML = 'COARSE_GRAINED_DML';
  /**
   * Fine-grained DML was used.
   */
  public const DML_MODE_FINE_GRAINED_DML = 'FINE_GRAINED_DML';
  /**
   * Default value. This value is unused.
   */
  public const FINE_GRAINED_DML_UNUSED_REASON_FINE_GRAINED_DML_UNUSED_REASON_UNSPECIFIED = 'FINE_GRAINED_DML_UNUSED_REASON_UNSPECIFIED';
  /**
   * Max partition size threshold exceeded. [Fine-grained DML Limitations]
   * (https://docs.cloud.google.com/bigquery/docs/data-manipulation-
   * language#fine-grained-dml-limitations)
   */
  public const FINE_GRAINED_DML_UNUSED_REASON_MAX_PARTITION_SIZE_EXCEEDED = 'MAX_PARTITION_SIZE_EXCEEDED';
  /**
   * The table is not enrolled for fine-grained DML.
   */
  public const FINE_GRAINED_DML_UNUSED_REASON_TABLE_NOT_ENROLLED = 'TABLE_NOT_ENROLLED';
  /**
   * The DML statement is part of a multi-statement transaction.
   */
  public const FINE_GRAINED_DML_UNUSED_REASON_DML_IN_MULTI_STATEMENT_TRANSACTION = 'DML_IN_MULTI_STATEMENT_TRANSACTION';
  /**
   * Output only. Number of deleted Rows. populated by DML DELETE, MERGE and
   * TRUNCATE statements.
   *
   * @var string
   */
  public $deletedRowCount;
  /**
   * Output only. DML mode used.
   *
   * @var string
   */
  public $dmlMode;
  /**
   * Output only. Reason for disabling fine-grained DML if applicable.
   *
   * @var string
   */
  public $fineGrainedDmlUnusedReason;
  /**
   * Output only. Number of inserted Rows. Populated by DML INSERT and MERGE
   * statements
   *
   * @var string
   */
  public $insertedRowCount;
  /**
   * Output only. Number of updated Rows. Populated by DML UPDATE and MERGE
   * statements.
   *
   * @var string
   */
  public $updatedRowCount;

  /**
   * Output only. Number of deleted Rows. populated by DML DELETE, MERGE and
   * TRUNCATE statements.
   *
   * @param string $deletedRowCount
   */
  public function setDeletedRowCount($deletedRowCount)
  {
    $this->deletedRowCount = $deletedRowCount;
  }
  /**
   * @return string
   */
  public function getDeletedRowCount()
  {
    return $this->deletedRowCount;
  }
  /**
   * Output only. DML mode used.
   *
   * Accepted values: DML_MODE_UNSPECIFIED, COARSE_GRAINED_DML, FINE_GRAINED_DML
   *
   * @param self::DML_MODE_* $dmlMode
   */
  public function setDmlMode($dmlMode)
  {
    $this->dmlMode = $dmlMode;
  }
  /**
   * @return self::DML_MODE_*
   */
  public function getDmlMode()
  {
    return $this->dmlMode;
  }
  /**
   * Output only. Reason for disabling fine-grained DML if applicable.
   *
   * Accepted values: FINE_GRAINED_DML_UNUSED_REASON_UNSPECIFIED,
   * MAX_PARTITION_SIZE_EXCEEDED, TABLE_NOT_ENROLLED,
   * DML_IN_MULTI_STATEMENT_TRANSACTION
   *
   * @param self::FINE_GRAINED_DML_UNUSED_REASON_* $fineGrainedDmlUnusedReason
   */
  public function setFineGrainedDmlUnusedReason($fineGrainedDmlUnusedReason)
  {
    $this->fineGrainedDmlUnusedReason = $fineGrainedDmlUnusedReason;
  }
  /**
   * @return self::FINE_GRAINED_DML_UNUSED_REASON_*
   */
  public function getFineGrainedDmlUnusedReason()
  {
    return $this->fineGrainedDmlUnusedReason;
  }
  /**
   * Output only. Number of inserted Rows. Populated by DML INSERT and MERGE
   * statements
   *
   * @param string $insertedRowCount
   */
  public function setInsertedRowCount($insertedRowCount)
  {
    $this->insertedRowCount = $insertedRowCount;
  }
  /**
   * @return string
   */
  public function getInsertedRowCount()
  {
    return $this->insertedRowCount;
  }
  /**
   * Output only. Number of updated Rows. Populated by DML UPDATE and MERGE
   * statements.
   *
   * @param string $updatedRowCount
   */
  public function setUpdatedRowCount($updatedRowCount)
  {
    $this->updatedRowCount = $updatedRowCount;
  }
  /**
   * @return string
   */
  public function getUpdatedRowCount()
  {
    return $this->updatedRowCount;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DmlStatistics::class, 'Google_Service_Bigquery_DmlStatistics');
