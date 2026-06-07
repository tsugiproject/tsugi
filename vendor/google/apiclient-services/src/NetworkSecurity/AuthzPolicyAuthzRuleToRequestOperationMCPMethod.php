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

class AuthzPolicyAuthzRuleToRequestOperationMCPMethod extends \Google\Collection
{
  protected $collection_key = 'params';
  /**
   * Required. The MCP method to match against. Allowed values are as follows:
   * 1. `tools`, `prompts`, `resources` - these will match against all sub
   * methods under the respective methods. 2. `prompts/list`, `tools/list`,
   * `resources/list`, `resources/templates/list` 3. `prompts/get`,
   * `tools/call`, `resources/subscribe`, `resources/unsubscribe`,
   * `resources/read` Params cannot be specified for categories 1 and 2.
   *
   * @var string
   */
  public $name;
  protected $paramsType = AuthzPolicyAuthzRuleStringMatch::class;
  protected $paramsDataType = 'array';

  /**
   * Required. The MCP method to match against. Allowed values are as follows:
   * 1. `tools`, `prompts`, `resources` - these will match against all sub
   * methods under the respective methods. 2. `prompts/list`, `tools/list`,
   * `resources/list`, `resources/templates/list` 3. `prompts/get`,
   * `tools/call`, `resources/subscribe`, `resources/unsubscribe`,
   * `resources/read` Params cannot be specified for categories 1 and 2.
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
   * Optional. A list of MCP method parameters to match against. The match can
   * be one of exact, prefix, suffix, or contains (substring match). Matches are
   * always case sensitive unless the ignoreCase is set. Limited to 10 MCP
   * method parameters per Authorization Policy.
   *
   * @param AuthzPolicyAuthzRuleStringMatch[] $params
   */
  public function setParams($params)
  {
    $this->params = $params;
  }
  /**
   * @return AuthzPolicyAuthzRuleStringMatch[]
   */
  public function getParams()
  {
    return $this->params;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AuthzPolicyAuthzRuleToRequestOperationMCPMethod::class, 'Google_Service_NetworkSecurity_AuthzPolicyAuthzRuleToRequestOperationMCPMethod');
