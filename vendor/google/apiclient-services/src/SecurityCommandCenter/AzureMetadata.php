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

namespace Google\Service\SecurityCommandCenter;

class AzureMetadata extends \Google\Collection
{
  protected $collection_key = 'managementGroups';
  protected $managementGroupsType = AzureManagementGroup::class;
  protected $managementGroupsDataType = 'array';
  protected $resourceGroupType = AzureResourceGroup::class;
  protected $resourceGroupDataType = '';
  protected $subscriptionType = AzureSubscription::class;
  protected $subscriptionDataType = '';
  protected $tenantType = AzureTenant::class;
  protected $tenantDataType = '';

  /**
   * @param AzureManagementGroup[]
   */
  public function setManagementGroups($managementGroups)
  {
    $this->managementGroups = $managementGroups;
  }
  /**
   * @return AzureManagementGroup[]
   */
  public function getManagementGroups()
  {
    return $this->managementGroups;
  }
  /**
   * @param AzureResourceGroup
   */
  public function setResourceGroup(AzureResourceGroup $resourceGroup)
  {
    $this->resourceGroup = $resourceGroup;
  }
  /**
   * @return AzureResourceGroup
   */
  public function getResourceGroup()
  {
    return $this->resourceGroup;
  }
  /**
   * @param AzureSubscription
   */
  public function setSubscription(AzureSubscription $subscription)
  {
    $this->subscription = $subscription;
  }
  /**
   * @return AzureSubscription
   */
  public function getSubscription()
  {
    return $this->subscription;
  }
  /**
   * @param AzureTenant
   */
  public function setTenant(AzureTenant $tenant)
  {
    $this->tenant = $tenant;
  }
  /**
   * @return AzureTenant
   */
  public function getTenant()
  {
    return $this->tenant;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AzureMetadata::class, 'Google_Service_SecurityCommandCenter_AzureMetadata');
