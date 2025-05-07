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

namespace Google\Service\OrgPolicyAPI;

class GoogleCloudOrgpolicyV2ConstraintCustomConstraintDefinition extends \Google\Collection
{
  protected $collection_key = 'resourceTypes';
  /**
   * @var string
   */
  public $actionType;
  /**
   * @var string
   */
  public $condition;
  /**
   * @var string[]
   */
  public $methodTypes;
  protected $parametersType = GoogleCloudOrgpolicyV2ConstraintCustomConstraintDefinitionParameter::class;
  protected $parametersDataType = 'map';
  /**
   * @var string[]
   */
  public $resourceTypes;

  /**
   * @param string
   */
  public function setActionType($actionType)
  {
    $this->actionType = $actionType;
  }
  /**
   * @return string
   */
  public function getActionType()
  {
    return $this->actionType;
  }
  /**
   * @param string
   */
  public function setCondition($condition)
  {
    $this->condition = $condition;
  }
  /**
   * @return string
   */
  public function getCondition()
  {
    return $this->condition;
  }
  /**
   * @param string[]
   */
  public function setMethodTypes($methodTypes)
  {
    $this->methodTypes = $methodTypes;
  }
  /**
   * @return string[]
   */
  public function getMethodTypes()
  {
    return $this->methodTypes;
  }
  /**
   * @param GoogleCloudOrgpolicyV2ConstraintCustomConstraintDefinitionParameter[]
   */
  public function setParameters($parameters)
  {
    $this->parameters = $parameters;
  }
  /**
   * @return GoogleCloudOrgpolicyV2ConstraintCustomConstraintDefinitionParameter[]
   */
  public function getParameters()
  {
    return $this->parameters;
  }
  /**
   * @param string[]
   */
  public function setResourceTypes($resourceTypes)
  {
    $this->resourceTypes = $resourceTypes;
  }
  /**
   * @return string[]
   */
  public function getResourceTypes()
  {
    return $this->resourceTypes;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudOrgpolicyV2ConstraintCustomConstraintDefinition::class, 'Google_Service_OrgPolicyAPI_GoogleCloudOrgpolicyV2ConstraintCustomConstraintDefinition');
