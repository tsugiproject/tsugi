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

class AuthzPolicyAuthzRuleToRequestOperationMCP extends \Google\Collection
{
  /**
   * Unspecified option. Defaults to SKIP_BASE_PROTOCOL_METHODS.
   */
  public const BASE_PROTOCOL_METHODS_OPTION_BASE_PROTOCOL_METHODS_OPTION_UNSPECIFIED = 'BASE_PROTOCOL_METHODS_OPTION_UNSPECIFIED';
  /**
   * Skip matching on the base MCP protocol methods.
   */
  public const BASE_PROTOCOL_METHODS_OPTION_SKIP_BASE_PROTOCOL_METHODS = 'SKIP_BASE_PROTOCOL_METHODS';
  /**
   * Match on the base MCP protocol methods.
   */
  public const BASE_PROTOCOL_METHODS_OPTION_MATCH_BASE_PROTOCOL_METHODS = 'MATCH_BASE_PROTOCOL_METHODS';
  protected $collection_key = 'methods';
  /**
   * Optional. If specified, matches on the MCP protocol’s non-access specific
   * methods namely: * initialize * completion/ * logging/ * notifications/ *
   * ping Defaults to SKIP_BASE_PROTOCOL_METHODS if not specified.
   *
   * @var string
   */
  public $baseProtocolMethodsOption;
  protected $methodsType = AuthzPolicyAuthzRuleToRequestOperationMCPMethod::class;
  protected $methodsDataType = 'array';

  /**
   * Optional. If specified, matches on the MCP protocol’s non-access specific
   * methods namely: * initialize * completion/ * logging/ * notifications/ *
   * ping Defaults to SKIP_BASE_PROTOCOL_METHODS if not specified.
   *
   * Accepted values: BASE_PROTOCOL_METHODS_OPTION_UNSPECIFIED,
   * SKIP_BASE_PROTOCOL_METHODS, MATCH_BASE_PROTOCOL_METHODS
   *
   * @param self::BASE_PROTOCOL_METHODS_OPTION_* $baseProtocolMethodsOption
   */
  public function setBaseProtocolMethodsOption($baseProtocolMethodsOption)
  {
    $this->baseProtocolMethodsOption = $baseProtocolMethodsOption;
  }
  /**
   * @return self::BASE_PROTOCOL_METHODS_OPTION_*
   */
  public function getBaseProtocolMethodsOption()
  {
    return $this->baseProtocolMethodsOption;
  }
  /**
   * Optional. A list of MCP methods and associated parameters to match on. It
   * is recommended to use this field to match on tools, prompts and resource
   * accesses while setting the baseProtocolMethodsOption to
   * MATCH_BASE_PROTOCOL_METHODS to match on all the other MCP protocol methods.
   * Limited to 10 MCP methods per Authorization Policy.
   *
   * @param AuthzPolicyAuthzRuleToRequestOperationMCPMethod[] $methods
   */
  public function setMethods($methods)
  {
    $this->methods = $methods;
  }
  /**
   * @return AuthzPolicyAuthzRuleToRequestOperationMCPMethod[]
   */
  public function getMethods()
  {
    return $this->methods;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AuthzPolicyAuthzRuleToRequestOperationMCP::class, 'Google_Service_NetworkSecurity_AuthzPolicyAuthzRuleToRequestOperationMCP');
