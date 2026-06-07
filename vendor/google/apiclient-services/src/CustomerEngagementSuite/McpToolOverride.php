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

class McpToolOverride extends \Google\Model
{
  /**
   * Optional. If present, this tool uses this description instead of the
   * original description from the server.
   *
   * @var string
   */
  public $descriptionOverride;
  /**
   * Optional. If present, this tool uses this name in the Agent instead of the
   * original name. This is primarily used as an alias if the MCP server offers
   * poorly named tools.
   *
   * @var string
   */
  public $nameOverride;
  protected $snapshotType = McpToolDefinition::class;
  protected $snapshotDataType = '';
  /**
   * Required. The original name of the tool as it is emitted by the MCP server.
   *
   * @var string
   */
  public $tool;

  /**
   * Optional. If present, this tool uses this description instead of the
   * original description from the server.
   *
   * @param string $descriptionOverride
   */
  public function setDescriptionOverride($descriptionOverride)
  {
    $this->descriptionOverride = $descriptionOverride;
  }
  /**
   * @return string
   */
  public function getDescriptionOverride()
  {
    return $this->descriptionOverride;
  }
  /**
   * Optional. If present, this tool uses this name in the Agent instead of the
   * original name. This is primarily used as an alias if the MCP server offers
   * poorly named tools.
   *
   * @param string $nameOverride
   */
  public function setNameOverride($nameOverride)
  {
    $this->nameOverride = $nameOverride;
  }
  /**
   * @return string
   */
  public function getNameOverride()
  {
    return $this->nameOverride;
  }
  /**
   * Output only. If present, this tool is "Pinned" and uses the snapshot values
   * as fallbacks if the server becomes temporarily unavailable or if no
   * Override is present.
   *
   * @param McpToolDefinition $snapshot
   */
  public function setSnapshot(McpToolDefinition $snapshot)
  {
    $this->snapshot = $snapshot;
  }
  /**
   * @return McpToolDefinition
   */
  public function getSnapshot()
  {
    return $this->snapshot;
  }
  /**
   * Required. The original name of the tool as it is emitted by the MCP server.
   *
   * @param string $tool
   */
  public function setTool($tool)
  {
    $this->tool = $tool;
  }
  /**
   * @return string
   */
  public function getTool()
  {
    return $this->tool;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(McpToolOverride::class, 'Google_Service_CustomerEngagementSuite_McpToolOverride');
