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

namespace Google\Service\StorageBatchOperations;

class Counters extends \Google\Model
{
  /**
   * Output only. The number of objects that failed due to user errors or
   * service errors.
   *
   * @var string
   */
  public $failedObjectCount;
  /**
   * Output only. Number of object custom contexts created. This field is only
   * populated for jobs with the UpdateObjectCustomContext transformation.
   *
   * @var string
   */
  public $objectCustomContextsCreated;
  /**
   * Output only. Number of object custom contexts deleted. This field is only
   * populated for jobs with the UpdateObjectCustomContext transformation.
   *
   * @var string
   */
  public $objectCustomContextsDeleted;
  /**
   * Output only. Number of object custom contexts updated. This counter tracks
   * custom contexts where the key already existed, but the payload was
   * modified. This field is only populated for jobs with the
   * UpdateObjectCustomContext transformation.
   *
   * @var string
   */
  public $objectCustomContextsUpdated;
  /**
   * Output only. Number of objects completed.
   *
   * @var string
   */
  public $succeededObjectCount;
  /**
   * Output only. Number of bytes found from source. This field is only
   * populated for jobs with a prefix list object configuration.
   *
   * @var string
   */
  public $totalBytesFound;
  /**
   * Output only. The total number of bytes affected by the transformation. For
   * example, this counts bytes deleted for `DeleteObject` operations and bytes
   * rewritten for `RewriteObject` operations.
   *
   * @var string
   */
  public $totalBytesTransformed;
  /**
   * Output only. Number of objects listed.
   *
   * @var string
   */
  public $totalObjectCount;

  /**
   * Output only. The number of objects that failed due to user errors or
   * service errors.
   *
   * @param string $failedObjectCount
   */
  public function setFailedObjectCount($failedObjectCount)
  {
    $this->failedObjectCount = $failedObjectCount;
  }
  /**
   * @return string
   */
  public function getFailedObjectCount()
  {
    return $this->failedObjectCount;
  }
  /**
   * Output only. Number of object custom contexts created. This field is only
   * populated for jobs with the UpdateObjectCustomContext transformation.
   *
   * @param string $objectCustomContextsCreated
   */
  public function setObjectCustomContextsCreated($objectCustomContextsCreated)
  {
    $this->objectCustomContextsCreated = $objectCustomContextsCreated;
  }
  /**
   * @return string
   */
  public function getObjectCustomContextsCreated()
  {
    return $this->objectCustomContextsCreated;
  }
  /**
   * Output only. Number of object custom contexts deleted. This field is only
   * populated for jobs with the UpdateObjectCustomContext transformation.
   *
   * @param string $objectCustomContextsDeleted
   */
  public function setObjectCustomContextsDeleted($objectCustomContextsDeleted)
  {
    $this->objectCustomContextsDeleted = $objectCustomContextsDeleted;
  }
  /**
   * @return string
   */
  public function getObjectCustomContextsDeleted()
  {
    return $this->objectCustomContextsDeleted;
  }
  /**
   * Output only. Number of object custom contexts updated. This counter tracks
   * custom contexts where the key already existed, but the payload was
   * modified. This field is only populated for jobs with the
   * UpdateObjectCustomContext transformation.
   *
   * @param string $objectCustomContextsUpdated
   */
  public function setObjectCustomContextsUpdated($objectCustomContextsUpdated)
  {
    $this->objectCustomContextsUpdated = $objectCustomContextsUpdated;
  }
  /**
   * @return string
   */
  public function getObjectCustomContextsUpdated()
  {
    return $this->objectCustomContextsUpdated;
  }
  /**
   * Output only. Number of objects completed.
   *
   * @param string $succeededObjectCount
   */
  public function setSucceededObjectCount($succeededObjectCount)
  {
    $this->succeededObjectCount = $succeededObjectCount;
  }
  /**
   * @return string
   */
  public function getSucceededObjectCount()
  {
    return $this->succeededObjectCount;
  }
  /**
   * Output only. Number of bytes found from source. This field is only
   * populated for jobs with a prefix list object configuration.
   *
   * @param string $totalBytesFound
   */
  public function setTotalBytesFound($totalBytesFound)
  {
    $this->totalBytesFound = $totalBytesFound;
  }
  /**
   * @return string
   */
  public function getTotalBytesFound()
  {
    return $this->totalBytesFound;
  }
  /**
   * Output only. The total number of bytes affected by the transformation. For
   * example, this counts bytes deleted for `DeleteObject` operations and bytes
   * rewritten for `RewriteObject` operations.
   *
   * @param string $totalBytesTransformed
   */
  public function setTotalBytesTransformed($totalBytesTransformed)
  {
    $this->totalBytesTransformed = $totalBytesTransformed;
  }
  /**
   * @return string
   */
  public function getTotalBytesTransformed()
  {
    return $this->totalBytesTransformed;
  }
  /**
   * Output only. Number of objects listed.
   *
   * @param string $totalObjectCount
   */
  public function setTotalObjectCount($totalObjectCount)
  {
    $this->totalObjectCount = $totalObjectCount;
  }
  /**
   * @return string
   */
  public function getTotalObjectCount()
  {
    return $this->totalObjectCount;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Counters::class, 'Google_Service_StorageBatchOperations_Counters');
