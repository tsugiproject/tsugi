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

namespace Google\Service\Compute;

class GlobalVmExtensionPolicy extends \Google\Collection
{
  /**
   * The zonal policies are being deleted.
   */
  public const SCOPED_RESOURCE_STATUS_SCOPED_RESOURCE_STATUS_DELETING = 'SCOPED_RESOURCE_STATUS_DELETING';
  /**
   * Default value. This value is unused.
   */
  public const SCOPED_RESOURCE_STATUS_SCOPED_RESOURCE_STATUS_UNSPECIFIED = 'SCOPED_RESOURCE_STATUS_UNSPECIFIED';
  protected $collection_key = 'instanceSelectors';
  /**
   * Output only. [Output Only] Creation timestamp inRFC3339 text format.
   *
   * @var string
   */
  public $creationTimestamp;
  /**
   * An optional description of this resource. Provide this property when you
   * create the resource.
   *
   * @var string
   */
  public $description;
  protected $extensionPoliciesType = GlobalVmExtensionPolicyExtensionPolicy::class;
  protected $extensionPoliciesDataType = 'map';
  /**
   * Output only. [Output Only] The unique identifier for the resource. This
   * identifier is defined by the server.
   *
   * @var string
   */
  public $id;
  protected $instanceSelectorsType = GlobalVmExtensionPolicyInstanceSelector::class;
  protected $instanceSelectorsDataType = 'array';
  /**
   * Output only. [Output Only] Type of the resource.
   * Alwayscompute#globalVmExtensionPolicy for globalVmExtensionPolicies.
   *
   * @var string
   */
  public $kind;
  /**
   * Name of the resource. Provided by the client when the resource is created.
   * The name must be 1-63 characters long, and comply withRFC1035.
   * Specifically, the name must be 1-63 characters long and match the regular
   * expression `[a-z]([-a-z0-9]*[a-z0-9])?` which means the first character
   * must be a lowercase letter, and all following characters must be a dash,
   * lowercase letter, or digit, except the last character, which cannot be a
   * dash.
   *
   * @var string
   */
  public $name;
  /**
   * Optional. Used to resolve conflicts when multiple policies are active for
   * the same extension. Defaults to 0.
   *
   * Larger the number, higher the priority. When the priority is the same, the
   * policy with the newer create time has higher priority.
   *
   * @var int
   */
  public $priority;
  protected $rolloutOperationType = GlobalVmExtensionPolicyRolloutOperation::class;
  protected $rolloutOperationDataType = '';
  /**
   * Output only. [Output Only] The scoped resource status. It's only for
   * tracking the purging status of the policy.
   *
   * @var string
   */
  public $scopedResourceStatus;
  /**
   * Output only. [Output Only] Server-defined fully-qualified URL for this
   * resource.
   *
   * @var string
   */
  public $selfLink;
  /**
   * Output only. [Output Only] Server-defined URL for this resource's resource
   * id.
   *
   * @var string
   */
  public $selfLinkWithId;
  /**
   * Output only. [Output Only] Update timestamp inRFC3339 text format.
   *
   * @var string
   */
  public $updateTimestamp;

  /**
   * Output only. [Output Only] Creation timestamp inRFC3339 text format.
   *
   * @param string $creationTimestamp
   */
  public function setCreationTimestamp($creationTimestamp)
  {
    $this->creationTimestamp = $creationTimestamp;
  }
  /**
   * @return string
   */
  public function getCreationTimestamp()
  {
    return $this->creationTimestamp;
  }
  /**
   * An optional description of this resource. Provide this property when you
   * create the resource.
   *
   * @param string $description
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * Required. Map from extension (eg: "cloudops") to its policy configuration.
   * The key is the name of the extension.
   *
   * @param GlobalVmExtensionPolicyExtensionPolicy[] $extensionPolicies
   */
  public function setExtensionPolicies($extensionPolicies)
  {
    $this->extensionPolicies = $extensionPolicies;
  }
  /**
   * @return GlobalVmExtensionPolicyExtensionPolicy[]
   */
  public function getExtensionPolicies()
  {
    return $this->extensionPolicies;
  }
  /**
   * Output only. [Output Only] The unique identifier for the resource. This
   * identifier is defined by the server.
   *
   * @param string $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }
  /**
   * Optional. Selector to target VMs for a policy. There is a logical "AND"
   * between instance_selectors.
   *
   * @param GlobalVmExtensionPolicyInstanceSelector[] $instanceSelectors
   */
  public function setInstanceSelectors($instanceSelectors)
  {
    $this->instanceSelectors = $instanceSelectors;
  }
  /**
   * @return GlobalVmExtensionPolicyInstanceSelector[]
   */
  public function getInstanceSelectors()
  {
    return $this->instanceSelectors;
  }
  /**
   * Output only. [Output Only] Type of the resource.
   * Alwayscompute#globalVmExtensionPolicy for globalVmExtensionPolicies.
   *
   * @param string $kind
   */
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  /**
   * @return string
   */
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * Name of the resource. Provided by the client when the resource is created.
   * The name must be 1-63 characters long, and comply withRFC1035.
   * Specifically, the name must be 1-63 characters long and match the regular
   * expression `[a-z]([-a-z0-9]*[a-z0-9])?` which means the first character
   * must be a lowercase letter, and all following characters must be a dash,
   * lowercase letter, or digit, except the last character, which cannot be a
   * dash.
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
   * Optional. Used to resolve conflicts when multiple policies are active for
   * the same extension. Defaults to 0.
   *
   * Larger the number, higher the priority. When the priority is the same, the
   * policy with the newer create time has higher priority.
   *
   * @param int $priority
   */
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  /**
   * @return int
   */
  public function getPriority()
  {
    return $this->priority;
  }
  /**
   * Required. The rollout strategy and status.
   *
   * @param GlobalVmExtensionPolicyRolloutOperation $rolloutOperation
   */
  public function setRolloutOperation(GlobalVmExtensionPolicyRolloutOperation $rolloutOperation)
  {
    $this->rolloutOperation = $rolloutOperation;
  }
  /**
   * @return GlobalVmExtensionPolicyRolloutOperation
   */
  public function getRolloutOperation()
  {
    return $this->rolloutOperation;
  }
  /**
   * Output only. [Output Only] The scoped resource status. It's only for
   * tracking the purging status of the policy.
   *
   * Accepted values: SCOPED_RESOURCE_STATUS_DELETING,
   * SCOPED_RESOURCE_STATUS_UNSPECIFIED
   *
   * @param self::SCOPED_RESOURCE_STATUS_* $scopedResourceStatus
   */
  public function setScopedResourceStatus($scopedResourceStatus)
  {
    $this->scopedResourceStatus = $scopedResourceStatus;
  }
  /**
   * @return self::SCOPED_RESOURCE_STATUS_*
   */
  public function getScopedResourceStatus()
  {
    return $this->scopedResourceStatus;
  }
  /**
   * Output only. [Output Only] Server-defined fully-qualified URL for this
   * resource.
   *
   * @param string $selfLink
   */
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  /**
   * @return string
   */
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  /**
   * Output only. [Output Only] Server-defined URL for this resource's resource
   * id.
   *
   * @param string $selfLinkWithId
   */
  public function setSelfLinkWithId($selfLinkWithId)
  {
    $this->selfLinkWithId = $selfLinkWithId;
  }
  /**
   * @return string
   */
  public function getSelfLinkWithId()
  {
    return $this->selfLinkWithId;
  }
  /**
   * Output only. [Output Only] Update timestamp inRFC3339 text format.
   *
   * @param string $updateTimestamp
   */
  public function setUpdateTimestamp($updateTimestamp)
  {
    $this->updateTimestamp = $updateTimestamp;
  }
  /**
   * @return string
   */
  public function getUpdateTimestamp()
  {
    return $this->updateTimestamp;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GlobalVmExtensionPolicy::class, 'Google_Service_Compute_GlobalVmExtensionPolicy');
