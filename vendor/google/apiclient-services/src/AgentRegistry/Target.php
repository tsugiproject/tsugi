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

class Target extends \Google\Model
{
  /**
   * The identifier of the target Agent, MCP Server, or Endpoint. Format: *
   * `urn:agent:{publisher}:{namespace}:{name}` *
   * `urn:mcp:{publisher}:{namespace}:{name}` *
   * `urn:endpoint:{publisher}:{namespace}:{name}`
   *
   * @var string
   */
  public $identifier;

  /**
   * The identifier of the target Agent, MCP Server, or Endpoint. Format: *
   * `urn:agent:{publisher}:{namespace}:{name}` *
   * `urn:mcp:{publisher}:{namespace}:{name}` *
   * `urn:endpoint:{publisher}:{namespace}:{name}`
   *
   * @param string $identifier
   */
  public function setIdentifier($identifier)
  {
    $this->identifier = $identifier;
  }
  /**
   * @return string
   */
  public function getIdentifier()
  {
    return $this->identifier;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Target::class, 'Google_Service_AgentRegistry_Target');
