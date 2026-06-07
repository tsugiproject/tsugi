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

namespace Google\Service\CustomerEngagementSuite;

class AgentCard extends \Google\Collection
{
  protected $collection_key = 'supportedInterfaces';
  /**
   * Required. A description of the agent's domain of action/solution space.
   *
   * @var string
   */
  public $description;
  /**
   * Required. A human-readable name for the agent.
   *
   * @var string
   */
  public $name;
  protected $skillsType = AgentSkill::class;
  protected $skillsDataType = 'array';
  protected $supportedInterfacesType = AgentInterface::class;
  protected $supportedInterfacesDataType = 'array';
  /**
   * Required. The version of the agent.
   *
   * @var string
   */
  public $version;

  /**
   * Required. A description of the agent's domain of action/solution space.
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
   * Required. A human-readable name for the agent.
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
   * Required. Skills represent a unit of ability an agent can perform. This may
   * somewhat abstract but represents a more focused set of actions that the
   * agent is highly likely to succeed at.
   *
   * @param AgentSkill[] $skills
   */
  public function setSkills($skills)
  {
    $this->skills = $skills;
  }
  /**
   * @return AgentSkill[]
   */
  public function getSkills()
  {
    return $this->skills;
  }
  /**
   * Required. Ordered list of supported interfaces. The first entry is
   * preferred.
   *
   * @param AgentInterface[] $supportedInterfaces
   */
  public function setSupportedInterfaces($supportedInterfaces)
  {
    $this->supportedInterfaces = $supportedInterfaces;
  }
  /**
   * @return AgentInterface[]
   */
  public function getSupportedInterfaces()
  {
    return $this->supportedInterfaces;
  }
  /**
   * Required. The version of the agent.
   *
   * @param string $version
   */
  public function setVersion($version)
  {
    $this->version = $version;
  }
  /**
   * @return string
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AgentCard::class, 'Google_Service_CustomerEngagementSuite_AgentCard');
