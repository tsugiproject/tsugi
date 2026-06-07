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

namespace Google\Service\Assuredworkloads;

class GoogleCloudAssuredworkloadsV1ArchiveResourceEventsRequest extends \Google\Model
{
  /**
   * Optional. Time to set as ArchiveTime in the archive table. If not provided,
   * the current time is used.
   *
   * @var string
   */
  public $archiveTime;
  /**
   * Required. The number of events to process in a single transaction batch.
   *
   * @var int
   */
  public $batchSize;
  /**
   * Required. Only events with EventTime earlier than this cutoff will be
   * archived.
   *
   * @var string
   */
  public $eventCutoffTime;
  /**
   * Required. The maximum total number of events to move in this request.
   *
   * @var int
   */
  public $maxEventsMove;
  /**
   * Required. The organization ID for which to archive events.
   *
   * @var string
   */
  public $organizationId;
  /**
   * Required. The region of the workload(s) whose events should be archived.
   * This is used to filter workloads based on AssurantWorkloadData.region.
   *
   * @var string
   */
  public $region;

  /**
   * Optional. Time to set as ArchiveTime in the archive table. If not provided,
   * the current time is used.
   *
   * @param string $archiveTime
   */
  public function setArchiveTime($archiveTime)
  {
    $this->archiveTime = $archiveTime;
  }
  /**
   * @return string
   */
  public function getArchiveTime()
  {
    return $this->archiveTime;
  }
  /**
   * Required. The number of events to process in a single transaction batch.
   *
   * @param int $batchSize
   */
  public function setBatchSize($batchSize)
  {
    $this->batchSize = $batchSize;
  }
  /**
   * @return int
   */
  public function getBatchSize()
  {
    return $this->batchSize;
  }
  /**
   * Required. Only events with EventTime earlier than this cutoff will be
   * archived.
   *
   * @param string $eventCutoffTime
   */
  public function setEventCutoffTime($eventCutoffTime)
  {
    $this->eventCutoffTime = $eventCutoffTime;
  }
  /**
   * @return string
   */
  public function getEventCutoffTime()
  {
    return $this->eventCutoffTime;
  }
  /**
   * Required. The maximum total number of events to move in this request.
   *
   * @param int $maxEventsMove
   */
  public function setMaxEventsMove($maxEventsMove)
  {
    $this->maxEventsMove = $maxEventsMove;
  }
  /**
   * @return int
   */
  public function getMaxEventsMove()
  {
    return $this->maxEventsMove;
  }
  /**
   * Required. The organization ID for which to archive events.
   *
   * @param string $organizationId
   */
  public function setOrganizationId($organizationId)
  {
    $this->organizationId = $organizationId;
  }
  /**
   * @return string
   */
  public function getOrganizationId()
  {
    return $this->organizationId;
  }
  /**
   * Required. The region of the workload(s) whose events should be archived.
   * This is used to filter workloads based on AssurantWorkloadData.region.
   *
   * @param string $region
   */
  public function setRegion($region)
  {
    $this->region = $region;
  }
  /**
   * @return string
   */
  public function getRegion()
  {
    return $this->region;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAssuredworkloadsV1ArchiveResourceEventsRequest::class, 'Google_Service_Assuredworkloads_GoogleCloudAssuredworkloadsV1ArchiveResourceEventsRequest');
