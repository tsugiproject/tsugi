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

class McpToolset extends \Google\Collection
{
  protected $collection_key = 'toolOverrides';
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
   * Required. The address of the MCP server, for example,
   * "https://example.com/mcp/". If the server is built with the MCP SDK, the
   * url should be suffixed with "/mcp/". Only Streamable HTTP transport based
   * servers are supported. See https://modelcontextprotocol.io/specification/20
   * 25-03-26/basic/transports#streamable-http for more details.
   *
   * @var string
   */
  public $serverAddress;
  protected $serviceDirectoryConfigType = ServiceDirectoryConfig::class;
  protected $serviceDirectoryConfigDataType = '';
  protected $tlsConfigType = TlsConfig::class;
  protected $tlsConfigDataType = '';
  protected $toolOverridesType = McpToolOverride::class;
  protected $toolOverridesDataType = 'array';

  /**
   * Optional. Authentication information required to access tools and execute a
   * tool against the MCP server. For bearer token authentication, the token
   * applies only to tool execution, not to listing tools. This requires that
   * tools can be listed without authentication.
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
   * Required. The address of the MCP server, for example,
   * "https://example.com/mcp/". If the server is built with the MCP SDK, the
   * url should be suffixed with "/mcp/". Only Streamable HTTP transport based
   * servers are supported. See https://modelcontextprotocol.io/specification/20
   * 25-03-26/basic/transports#streamable-http for more details.
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
  /**
   * Optional. Overrides for individual tools within this toolset. This allows
   * overriding specific details like descriptions, names, or pinning the tools'
   * states so they aren't fully dynamic.
   *
   * @param McpToolOverride[] $toolOverrides
   */
  public function setToolOverrides($toolOverrides)
  {
    $this->toolOverrides = $toolOverrides;
  }
  /**
   * @return McpToolOverride[]
   */
  public function getToolOverrides()
  {
    return $this->toolOverrides;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(McpToolset::class, 'Google_Service_CustomerEngagementSuite_McpToolset');
