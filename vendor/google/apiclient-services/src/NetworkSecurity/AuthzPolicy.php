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

namespace Google\Service\NetworkSecurity;

class AuthzPolicy extends \Google\Collection
{
  protected $collection_key = 'httpRules';
  /**
   * @var string
   */
  public $action;
  /**
   * @var string
   */
  public $createTime;
  protected $customProviderType = AuthzPolicyCustomProvider::class;
  protected $customProviderDataType = '';
  /**
   * @var string
   */
  public $description;
  protected $httpRulesType = AuthzPolicyAuthzRule::class;
  protected $httpRulesDataType = 'array';
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $name;
  protected $targetType = AuthzPolicyTarget::class;
  protected $targetDataType = '';
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param string
   */
  public function setAction($action)
  {
    $this->action = $action;
  }
  /**
   * @return string
   */
  public function getAction()
  {
    return $this->action;
  }
  /**
   * @param string
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
   * @param AuthzPolicyCustomProvider
   */
  public function setCustomProvider(AuthzPolicyCustomProvider $customProvider)
  {
    $this->customProvider = $customProvider;
  }
  /**
   * @return AuthzPolicyCustomProvider
   */
  public function getCustomProvider()
  {
    return $this->customProvider;
  }
  /**
   * @param string
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
   * @param AuthzPolicyAuthzRule[]
   */
  public function setHttpRules($httpRules)
  {
    $this->httpRules = $httpRules;
  }
  /**
   * @return AuthzPolicyAuthzRule[]
   */
  public function getHttpRules()
  {
    return $this->httpRules;
  }
  /**
   * @param string[]
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
   * @param string
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
   * @param AuthzPolicyTarget
   */
  public function setTarget(AuthzPolicyTarget $target)
  {
    $this->target = $target;
  }
  /**
   * @return AuthzPolicyTarget
   */
  public function getTarget()
  {
    return $this->target;
  }
  /**
   * @param string
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
class_alias(AuthzPolicy::class, 'Google_Service_NetworkSecurity_AuthzPolicy');
