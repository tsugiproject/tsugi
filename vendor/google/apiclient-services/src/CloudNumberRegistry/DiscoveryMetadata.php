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

namespace Google\Service\CloudNumberRegistry;

class DiscoveryMetadata extends \Google\Model
{
  /**
   * Unspecified state.
   */
  public const STATE_RESOURCE_STATE_UNSPECIFIED = 'RESOURCE_STATE_UNSPECIFIED';
  /**
   * The resource is in an invalid state.
   */
  public const STATE_INVALID = 'INVALID';
  /**
   * The resource exists in the backing store (is not tombstoned or completely
   * missing) and there were no failures reading out the resource level
   * metadata.
   */
  public const STATE_EXISTS = 'EXISTS';
  /**
   * Resource does not exist or has been deleted or soft-deleted.
   */
  public const STATE_DOES_NOT_EXIST = 'DOES_NOT_EXIST';
  /**
   * There was an error reading out the resource level metadata.
   */
  public const STATE_ERROR = 'ERROR';
  /**
   * Output only. The time when the resource was created.
   *
   * @var string
   */
  public $createTime;
  /**
   * Output only. The time when the event happened.
   *
   * @var string
   */
  public $eventTime;
  /**
   * Output only. The resource name of the discovered resource, should be API-
   * agnostic. Example: "projects/{project_number}/networks/{network_id}".
   *
   * @var string
   */
  public $resource;
  /**
   * Output only. The resource uri of the discovered resource.
   *
   * @var string
   */
  public $resourceUri;
  /**
   * Output only. The canonical google.aip.dev/122 name of the source resource.
   *
   * @var string
   */
  public $sourceId;
  /**
   * Output only. A single source resource can be the source of multiple CNR
   * resources. This sub_id is used to distinguish between the different CNR
   * resources derived from the same upstream resource. For example, a single
   * subnetwork can be the source of multiple ranges, one for each protocol. In
   * this case, the sub_id could be "private-ipv4" or "private-ipv6".
   *
   * @var string
   */
  public $sourceSubId;
  /**
   * Output only. The state of the resource.
   *
   * @var string
   */
  public $state;
  /**
   * Output only. The time when the resource was last modified.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. The time when the resource was created.
   *
   * @param string $createTime
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
   * Output only. The time when the event happened.
   *
   * @param string $eventTime
   */
  public function setEventTime($eventTime)
  {
    $this->eventTime = $eventTime;
  }
  /**
   * @return string
   */
  public function getEventTime()
  {
    return $this->eventTime;
  }
  /**
   * Output only. The resource name of the discovered resource, should be API-
   * agnostic. Example: "projects/{project_number}/networks/{network_id}".
   *
   * @param string $resource
   */
  public function setResource($resource)
  {
    $this->resource = $resource;
  }
  /**
   * @return string
   */
  public function getResource()
  {
    return $this->resource;
  }
  /**
   * Output only. The resource uri of the discovered resource.
   *
   * @param string $resourceUri
   */
  public function setResourceUri($resourceUri)
  {
    $this->resourceUri = $resourceUri;
  }
  /**
   * @return string
   */
  public function getResourceUri()
  {
    return $this->resourceUri;
  }
  /**
   * Output only. The canonical google.aip.dev/122 name of the source resource.
   *
   * @param string $sourceId
   */
  public function setSourceId($sourceId)
  {
    $this->sourceId = $sourceId;
  }
  /**
   * @return string
   */
  public function getSourceId()
  {
    return $this->sourceId;
  }
  /**
   * Output only. A single source resource can be the source of multiple CNR
   * resources. This sub_id is used to distinguish between the different CNR
   * resources derived from the same upstream resource. For example, a single
   * subnetwork can be the source of multiple ranges, one for each protocol. In
   * this case, the sub_id could be "private-ipv4" or "private-ipv6".
   *
   * @param string $sourceSubId
   */
  public function setSourceSubId($sourceSubId)
  {
    $this->sourceSubId = $sourceSubId;
  }
  /**
   * @return string
   */
  public function getSourceSubId()
  {
    return $this->sourceSubId;
  }
  /**
   * Output only. The state of the resource.
   *
   * Accepted values: RESOURCE_STATE_UNSPECIFIED, INVALID, EXISTS,
   * DOES_NOT_EXIST, ERROR
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * Output only. The time when the resource was last modified.
   *
   * @param string $updateTime
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
class_alias(DiscoveryMetadata::class, 'Google_Service_CloudNumberRegistry_DiscoveryMetadata');
