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

class McpTool extends \Google\Model
{
  /**
   * Default state.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * The tool is available and actively offered by the server.
   */
  public const STATE_ACTIVE = 'ACTIVE';
  /**
   * The tool is configured or pinned, but currently not offered by the server.
   */
  public const STATE_INACTIVE = 'INACTIVE';
  /**
   * The tool exists on the server, but does not match the version on the
   * server.
   */
  public const STATE_STALE = 'STALE';
  protected $apiAuthenticationType = ApiAuthentication::class;
  protected $apiAuthenticationDataType = '';
  /**
   * Optional. The custom headers to send in the request to the MCP server. The
   * values must be in the format `$context.variables.` and can be set in the
   * session variables. See https://docs.cloud.google.com/customer-engagement-
   * ai/conversational-agents/ps/tool/open-api#openapi-injection for more
   * details.
   *
   * @var string[]
   */
  public $customHeaders;
  /**
   * Optional. The description of the MCP tool.
   *
   * @var string
   */
  public $description;
  protected $inputSchemaType = Schema::class;
  protected $inputSchemaDataType = '';
  /**
   * Required. The name of the MCP tool.
   *
   * @var string
   */
  public $name;
  /**
   * Optional. The name override of the MCP tool. This is populated if the name
   * was overridden by a Toolset override.
   *
   * @var string
   */
  public $nameOverride;
  protected $outputSchemaType = Schema::class;
  protected $outputSchemaDataType = '';
  /**
   * Required. The server address of the MCP server, e.g.,
   * "https://example.com/mcp/". If the server is built with the MCP SDK, the
   * url should be suffixed with "/mcp/". Only Streamable HTTP transport based
   * servers are supported. This is the same as the server_address in the
   * McpToolset. See https://modelcontextprotocol.io/specification/2025-03-
   * 26/basic/transports#streamable-http for more details.
   *
   * @var string
   */
  public $serverAddress;
  protected $serviceDirectoryConfigType = ServiceDirectoryConfig::class;
  protected $serviceDirectoryConfigDataType = '';
  /**
   * Output only. The dynamic availability state of the tool on the external
   * server.
   *
   * @var string
   */
  public $state;
  protected $tlsConfigType = TlsConfig::class;
  protected $tlsConfigDataType = '';

  /**
   * Optional. Authentication information required to execute the tool against
   * the MCP server. For bearer token authentication, the token applies only to
   * tool execution, not to listing tools. This requires that tools can be
   * listed without authentication.
   *
   * @param ApiAuthentication $apiAuthentication
   */
  public function setApiAuthentication(ApiAuthentication $apiAuthentication)
  {
    $this->apiAuthentication = $apiAuthentication;
  }
  /**
   * @return ApiAuthentication
   */
  public function getApiAuthentication()
  {
    return $this->apiAuthentication;
  }
  /**
   * Optional. The custom headers to send in the request to the MCP server. The
   * values must be in the format `$context.variables.` and can be set in the
   * session variables. See https://docs.cloud.google.com/customer-engagement-
   * ai/conversational-agents/ps/tool/open-api#openapi-injection for more
   * details.
   *
   * @param string[] $customHeaders
   */
  public function setCustomHeaders($customHeaders)
  {
    $this->customHeaders = $customHeaders;
  }
  /**
   * @return string[]
   */
  public function getCustomHeaders()
  {
    return $this->customHeaders;
  }
  /**
   * Optional. The description of the MCP tool.
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
   * Optional. The schema of the input arguments of the MCP tool.
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
   * Required. The name of the MCP tool.
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
   * Optional. The name override of the MCP tool. This is populated if the name
   * was overridden by a Toolset override.
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
   * Optional. The schema of the output arguments of the MCP tool.
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
  /**
   * Required. The server address of the MCP server, e.g.,
   * "https://example.com/mcp/". If the server is built with the MCP SDK, the
   * url should be suffixed with "/mcp/". Only Streamable HTTP transport based
   * servers are supported. This is the same as the server_address in the
   * McpToolset. See https://modelcontextprotocol.io/specification/2025-03-
   * 26/basic/transports#streamable-http for more details.
   *
   * @param string $serverAddress
   */
  public function setServerAddress($serverAddress)
  {
    $this->serverAddress = $serverAddress;
  }
  /**
   * @return string
   */
  public function getServerAddress()
  {
    return $this->serverAddress;
  }
  /**
   * Optional. Service Directory configuration for VPC-SC, used to resolve
   * service names within a perimeter.
   *
   * @param ServiceDirectoryConfig $serviceDirectoryConfig
   */
  public function setServiceDirectoryConfig(ServiceDirectoryConfig $serviceDirectoryConfig)
  {
    $this->serviceDirectoryConfig = $serviceDirectoryConfig;
  }
  /**
   * @return ServiceDirectoryConfig
   */
  public function getServiceDirectoryConfig()
  {
    return $this->serviceDirectoryConfig;
  }
  /**
   * Output only. The dynamic availability state of the tool on the external
   * server.
   *
   * Accepted values: STATE_UNSPECIFIED, ACTIVE, INACTIVE, STALE
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * Optional. The TLS configuration. Includes the custom server certificates
   * that the client should trust.
   *
   * @param TlsConfig $tlsConfig
   */
  public function setTlsConfig(TlsConfig $tlsConfig)
  {
    $this->tlsConfig = $tlsConfig;
  }
  /**
   * @return TlsConfig
   */
  public function getTlsConfig()
  {
    return $this->tlsConfig;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(McpTool::class, 'Google_Service_CustomerEngagementSuite_McpTool');
