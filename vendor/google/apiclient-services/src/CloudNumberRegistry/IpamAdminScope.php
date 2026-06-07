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

class IpamAdminScope extends \Google\Collection
{
  /**
   * Unspecified state.
   */
  public const STATE_DISCOVERY_PIPELINE_STATE_UNSPECIFIED = 'DISCOVERY_PIPELINE_STATE_UNSPECIFIED';
  /**
   * Internal failure.
   */
  public const STATE_INTERNAL_FAILURE = 'INTERNAL_FAILURE';
  /**
   * Failure.
   */
  public const STATE_FAILED = 'FAILED';
  /**
   * Setup in progress.
   */
  public const STATE_SETUP_IN_PROGRESS = 'SETUP_IN_PROGRESS';
  /**
   * Ready for use.
   */
  public const STATE_READY_FOR_USE = 'READY_FOR_USE';
  /**
   * Deleting in progress.
   */
  public const STATE_DELETING_IN_PROGRESS = 'DELETING_IN_PROGRESS';
  /**
   * Updating.
   */
  public const STATE_UPDATING = 'UPDATING';
  /**
   * Recovering.
   */
  public const STATE_RECOVERING = 'RECOVERING';
  /**
   * Disabled.
   */
  public const STATE_DISABLED = 'DISABLED';
  /**
   * Deleting completed.
   */
  public const STATE_DELETION_COMPLETED = 'DELETION_COMPLETED';
  /**
   * Cleanup in progress.
   */
  public const STATE_CLEANUP_IN_PROGRESS = 'CLEANUP_IN_PROGRESS';
  /**
   * Ready for deletion.
   */
  public const STATE_READY_FOR_DELETION = 'READY_FOR_DELETION';
  protected $collection_key = 'scopes';
  /**
   * Output only. [Output only] Create time stamp
   *
   * @var string
   */
  public $createTime;
  /**
   * Required. Addon platforms that are enabled for this IPAM admin scope. Cloud
   * Number Registry only discovers the IP addresses from the enabled platforms.
   *
   * @var string[]
   */
  public $enabledAddonPlatforms;
  /**
   * Optional. Labels as key value pairs
   *
   * @var string[]
   */
  public $labels;
  /**
   * Required. Identifier. name of resource
   *
   * @var string
   */
  public $name;
  /**
   * Required. Administrative scopes enabled for IP address discovery and
   * management. For example, "organizations/1234567890". Minimum of 1 scope is
   * required. In preview, only one organization scope is allowed.
   *
   * @var string[]
   */
  public $scopes;
  /**
   * Output only. State of resource discovery pipeline.
   *
   * @var string
   */
  public $state;
  /**
   * Output only. [Output only] Update time stamp
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. [Output only] Create time stamp
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
   * Required. Addon platforms that are enabled for this IPAM admin scope. Cloud
   * Number Registry only discovers the IP addresses from the enabled platforms.
   *
   * @param string[] $enabledAddonPlatforms
   */
  public function setEnabledAddonPlatforms($enabledAddonPlatforms)
  {
    $this->enabledAddonPlatforms = $enabledAddonPlatforms;
  }
  /**
   * @return string[]
   */
  public function getEnabledAddonPlatforms()
  {
    return $this->enabledAddonPlatforms;
  }
  /**
   * Optional. Labels as key value pairs
   *
   * @param string[] $labels
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
  }
  /**
   * Required. Identifier. name of resource
   *
   * @param string $name
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
   * Required. Administrative scopes enabled for IP address discovery and
   * management. For example, "organizations/1234567890". Minimum of 1 scope is
   * required. In preview, only one organization scope is allowed.
   *
   * @param string[] $scopes
   */
  public function setScopes($scopes)
  {
    $this->scopes = $scopes;
  }
  /**
   * @return string[]
   */
  public function getScopes()
  {
    return $this->scopes;
  }
  /**
   * Output only. State of resource discovery pipeline.
   *
   * Accepted values: DISCOVERY_PIPELINE_STATE_UNSPECIFIED, INTERNAL_FAILURE,
   * FAILED, SETUP_IN_PROGRESS, READY_FOR_USE, DELETING_IN_PROGRESS, UPDATING,
   * RECOVERING, DISABLED, DELETION_COMPLETED, CLEANUP_IN_PROGRESS,
   * READY_FOR_DELETION
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
   * Output only. [Output only] Update time stamp
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
class_alias(IpamAdminScope::class, 'Google_Service_CloudNumberRegistry_IpamAdminScope');
