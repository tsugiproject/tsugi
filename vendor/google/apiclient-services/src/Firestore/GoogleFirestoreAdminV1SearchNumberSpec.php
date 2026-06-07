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

namespace Google\Service\Firestore;

class GoogleFirestoreAdminV1SearchNumberSpec extends \Google\Model
{
  /**
   * The index type is unspecified. Not a valid option.
   */
  public const INDEX_TYPE_NUMBER_INDEX_TYPE_UNSPECIFIED = 'NUMBER_INDEX_TYPE_UNSPECIFIED';
  /**
   * A floating point index.
   */
  public const INDEX_TYPE_FLOAT64 = 'FLOAT64';
  /**
   * A log tree index for int32 values.
   */
  public const INDEX_TYPE_INT32_LOG_TREE = 'INT32_LOG_TREE';
  /**
   * A log tree index for int64 values.
   */
  public const INDEX_TYPE_INT64_LOG_TREE = 'INT64_LOG_TREE';
  /**
   * A prefix tree index for int32 values.
   */
  public const INDEX_TYPE_INT32_PREFIX_TREE = 'INT32_PREFIX_TREE';
  /**
   * A prefix tree index for int64 values.
   */
  public const INDEX_TYPE_INT64_PREFIX_TREE = 'INT64_PREFIX_TREE';
  /**
   * Required. How to index the number field value.
   *
   * @var string
   */
  public $indexType;

  /**
   * Required. How to index the number field value.
   *
   * Accepted values: NUMBER_INDEX_TYPE_UNSPECIFIED, FLOAT64, INT32_LOG_TREE,
   * INT64_LOG_TREE, INT32_PREFIX_TREE, INT64_PREFIX_TREE
   *
   * @param self::INDEX_TYPE_* $indexType
   */
  public function setIndexType($indexType)
  {
    $this->indexType = $indexType;
  }
  /**
   * @return self::INDEX_TYPE_*
   */
  public function getIndexType()
  {
    return $this->indexType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleFirestoreAdminV1SearchNumberSpec::class, 'Google_Service_Firestore_GoogleFirestoreAdminV1SearchNumberSpec');
