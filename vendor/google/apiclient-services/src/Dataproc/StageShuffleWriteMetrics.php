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

namespace Google\Service\Dataproc;

class StageShuffleWriteMetrics extends \Google\Model
{
  /**
   * @var string
   */
  public $bytesWritten;
  /**
   * @var string
   */
  public $recordsWritten;
  /**
   * @var string
   */
  public $writeTimeNanos;

  /**
   * @param string
   */
  public function setBytesWritten($bytesWritten)
  {
    $this->bytesWritten = $bytesWritten;
  }
  /**
   * @return string
   */
  public function getBytesWritten()
  {
    return $this->bytesWritten;
  }
  /**
   * @param string
   */
  public function setRecordsWritten($recordsWritten)
  {
    $this->recordsWritten = $recordsWritten;
  }
  /**
   * @return string
   */
  public function getRecordsWritten()
  {
    return $this->recordsWritten;
  }
  /**
   * @param string
   */
  public function setWriteTimeNanos($writeTimeNanos)
  {
    $this->writeTimeNanos = $writeTimeNanos;
  }
  /**
   * @return string
   */
  public function getWriteTimeNanos()
  {
    return $this->writeTimeNanos;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(StageShuffleWriteMetrics::class, 'Google_Service_Dataproc_StageShuffleWriteMetrics');
