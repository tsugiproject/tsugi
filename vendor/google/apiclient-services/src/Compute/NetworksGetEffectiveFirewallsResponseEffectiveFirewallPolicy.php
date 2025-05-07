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

class NetworksGetEffectiveFirewallsResponseEffectiveFirewallPolicy extends \Google\Collection
{
  protected $collection_key = 'rules';
  /**
   * @var string
   */
  public $displayName;
  /**
   * @var string
   */
  public $name;
  protected $packetMirroringRulesType = FirewallPolicyRule::class;
  protected $packetMirroringRulesDataType = 'array';
  /**
   * @var int
   */
  public $priority;
  protected $rulesType = FirewallPolicyRule::class;
  protected $rulesDataType = 'array';
  /**
   * @var string
   */
  public $shortName;
  /**
   * @var string
   */
  public $type;

  /**
   * @param string
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
   * @param FirewallPolicyRule[]
   */
  public function setPacketMirroringRules($packetMirroringRules)
  {
    $this->packetMirroringRules = $packetMirroringRules;
  }
  /**
   * @return FirewallPolicyRule[]
   */
  public function getPacketMirroringRules()
  {
    return $this->packetMirroringRules;
  }
  /**
   * @param int
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
   * @param FirewallPolicyRule[]
   */
  public function setRules($rules)
  {
    $this->rules = $rules;
  }
  /**
   * @return FirewallPolicyRule[]
   */
  public function getRules()
  {
    return $this->rules;
  }
  /**
   * @param string
   */
  public function setShortName($shortName)
  {
    $this->shortName = $shortName;
  }
  /**
   * @return string
   */
  public function getShortName()
  {
    return $this->shortName;
  }
  /**
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(NetworksGetEffectiveFirewallsResponseEffectiveFirewallPolicy::class, 'Google_Service_Compute_NetworksGetEffectiveFirewallsResponseEffectiveFirewallPolicy');
