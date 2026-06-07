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

namespace Google\Service\AgentRegistry;

class Binding extends \Google\Model
{
  protected $authProviderBindingType = AuthProviderBinding::class;
  protected $authProviderBindingDataType = '';
  /**
   * Output only. Timestamp when this binding was created.
   *
   * @var string
   */
  public $createTime;
  /**
   * Optional. User-defined description of a Binding. Can have a maximum length
   * of `2048` characters.
   *
   * @var string
   */
  public $description;
  /**
   * Optional. User-defined display name for the Binding. Can have a maximum
   * length of `63` characters.
   *
   * @var string
   */
  public $displayName;
  /**
   * Required. Identifier. The resource name of the Binding. Format:
   * `projects/{project}/locations/{location}/bindings/{binding}`.
   *
   * @var string
   */
  public $name;
  protected $sourceType = Source::class;
  protected $sourceDataType = '';
  protected $targetType = Target::class;
  protected $targetDataType = '';
  /**
   * Output only. Timestamp when this binding was last updated.
   *
   * @var string
   */
  public $updateTime;

  /**
   * The binding for AuthProvider.
   *
   * @param AuthProviderBinding $authProviderBinding
   */
  public function setAuthProviderBinding(AuthProviderBinding $authProviderBinding)
  {
    $this->authProviderBinding = $authProviderBinding;
  }
  /**
   * @return AuthProviderBinding
   */
  public function getAuthProviderBinding()
  {
    return $this->authProviderBinding;
  }
  /**
   * Output only. Timestamp when this binding was created.
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
   * Optional. User-defined description of a Binding. Can have a maximum length
   * of `2048` characters.
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
   * Optional. User-defined display name for the Binding. Can have a maximum
   * length of `63` characters.
   *
   * @param string $displayName
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * Required. Identifier. The resource name of the Binding. Format:
   * `projects/{project}/locations/{location}/bindings/{binding}`.
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
   * Required. The target Agent of the Binding.
   *
   * @param Source $source
   */
  public function setSource(Source $source)
  {
    $this->source = $source;
  }
  /**
   * @return Source
   */
  public function getSource()
  {
    return $this->source;
  }
  /**
   * Required. The target Agent Registry Resource of the Binding.
   *
   * @param Target $target
   */
  public function setTarget(Target $target)
  {
    $this->target = $target;
  }
  /**
   * @return Target
   */
  public function getTarget()
  {
    return $this->target;
  }
  /**
   * Output only. Timestamp when this binding was last updated.
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
class_alias(Binding::class, 'Google_Service_AgentRegistry_Binding');
