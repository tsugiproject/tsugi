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

class McpToolDefinition extends \Google\Model
{
  /**
   * Output only. The description of the MCP tool. This can be overridden by
   * `description_override` in `McpToolOverride`.
   *
   * @var string
   */
  public $description;
  protected $inputSchemaType = Schema::class;
  protected $inputSchemaDataType = '';
  protected $outputSchemaType = Schema::class;
  protected $outputSchemaDataType = '';

  /**
   * Output only. The description of the MCP tool. This can be overridden by
   * `description_override` in `McpToolOverride`.
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
   * Output only. The schema of the input arguments of the MCP tool.
   *
   * @param Schema $inputSchema
   */
  public function setInputSchema(Schema $inputSchema)
  {
    $this->inputSchema = $inputSchema;
  }
  /**
   * @return Schema
   */
  public function getInputSchema()
  {
    return $this->inputSchema;
  }
  /**
   * Output only. The schema of the output arguments of the MCP tool.
   *
   * @param Schema $outputSchema
   */
  public function setOutputSchema(Schema $outputSchema)
  {
    $this->outputSchema = $outputSchema;
  }
  /**
   * @return Schema
   */
  public function getOutputSchema()
  {
    return $this->outputSchema;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(McpToolDefinition::class, 'Google_Service_CustomerEngagementSuite_McpToolDefinition');
