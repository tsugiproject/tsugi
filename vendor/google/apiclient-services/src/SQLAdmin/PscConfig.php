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

namespace Google\Service\SQLAdmin;

class PscConfig extends \Google\Collection
{
  protected $collection_key = 'pscAutoConnections';
  /**
   * Optional. The list of consumer projects that are allow-listed for PSC
   * connections to this instance. This instance can be connected to with PSC
   * from any network in these projects. Each consumer project in this list may
   * be represented by a project number (numeric) or by a project id
   * (alphanumeric).
   *
   * @var string[]
   */
  public $allowedConsumerProjects;
  /**
   * Optional. The network attachment of the consumer network that the Private
   * Service Connect enabled Cloud SQL instance is authorized to connect via PSC
   * interface. format: projects/PROJECT/regions/REGION/networkAttachments/ID
   *
   * @var string
   */
  public $networkAttachmentUri;
  protected $pscAutoConnectionsType = PscAutoConnectionConfig::class;
  protected $pscAutoConnectionsDataType = 'array';
  /**
   * Optional. Indicates whether PSC DNS automation is enabled for this
   * instance. When enabled, Cloud SQL provisions a universal DNS record across
   * all networks configured with Private Service Connect (PSC) auto-
   * connections. This will default to true for new instances when Private
   * Service Connect is enabled.
   *
   * @var bool
   */
  public $pscAutoDnsEnabled;
  /**
   * Whether PSC connectivity is enabled for this instance.
   *
   * @var bool
   */
  public $pscEnabled;
  /**
   * Optional. Indicates whether PSC write endpoint DNS automation is enabled
   * for this instance. When enabled, Cloud SQL provisions a universal global
   * DNS record across all networks configured with Private Service Connect
   * (PSC) auto-connections that always points to the cluster primary instance.
   * This feature is only supported for Enterprise Plus edition. This will
   * default to true for new Enterprise Plus instances when
   * `psc_auto_dns_enabled` is enabled.
   *
   * @var bool
   */
  public $pscWriteEndpointDnsEnabled;

  /**
   * Optional. The list of consumer projects that are allow-listed for PSC
   * connections to this instance. This instance can be connected to with PSC
   * from any network in these projects. Each consumer project in this list may
   * be represented by a project number (numeric) or by a project id
   * (alphanumeric).
   *
   * @param string[] $allowedConsumerProjects
   */
  public function setAllowedConsumerProjects($allowedConsumerProjects)
  {
    $this->allowedConsumerProjects = $allowedConsumerProjects;
  }
  /**
   * @return string[]
   */
  public function getAllowedConsumerProjects()
  {
    return $this->allowedConsumerProjects;
  }
  /**
   * Optional. The network attachment of the consumer network that the Private
   * Service Connect enabled Cloud SQL instance is authorized to connect via PSC
   * interface. format: projects/PROJECT/regions/REGION/networkAttachments/ID
   *
   * @param string $networkAttachmentUri
   */
  public function setNetworkAttachmentUri($networkAttachmentUri)
  {
    $this->networkAttachmentUri = $networkAttachmentUri;
  }
  /**
   * @return string
   */
  public function getNetworkAttachmentUri()
  {
    return $this->networkAttachmentUri;
  }
  /**
   * Optional. The list of settings for requested Private Service Connect
   * consumer endpoints that can be used to connect to this Cloud SQL instance.
   *
   * @param PscAutoConnectionConfig[] $pscAutoConnections
   */
  public function setPscAutoConnections($pscAutoConnections)
  {
    $this->pscAutoConnections = $pscAutoConnections;
  }
  /**
   * @return PscAutoConnectionConfig[]
   */
  public function getPscAutoConnections()
  {
    return $this->pscAutoConnections;
  }
  /**
   * Optional. Indicates whether PSC DNS automation is enabled for this
   * instance. When enabled, Cloud SQL provisions a universal DNS record across
   * all networks configured with Private Service Connect (PSC) auto-
   * connections. This will default to true for new instances when Private
   * Service Connect is enabled.
   *
   * @param bool $pscAutoDnsEnabled
   */
  public function setPscAutoDnsEnabled($pscAutoDnsEnabled)
  {
    $this->pscAutoDnsEnabled = $pscAutoDnsEnabled;
  }
  /**
   * @return bool
   */
  public function getPscAutoDnsEnabled()
  {
    return $this->pscAutoDnsEnabled;
  }
  /**
   * Whether PSC connectivity is enabled for this instance.
   *
   * @param bool $pscEnabled
   */
  public function setPscEnabled($pscEnabled)
  {
    $this->pscEnabled = $pscEnabled;
  }
  /**
   * @return bool
   */
  public function getPscEnabled()
  {
    return $this->pscEnabled;
  }
  /**
   * Optional. Indicates whether PSC write endpoint DNS automation is enabled
   * for this instance. When enabled, Cloud SQL provisions a universal global
   * DNS record across all networks configured with Private Service Connect
   * (PSC) auto-connections that always points to the cluster primary instance.
   * This feature is only supported for Enterprise Plus edition. This will
   * default to true for new Enterprise Plus instances when
   * `psc_auto_dns_enabled` is enabled.
   *
   * @param bool $pscWriteEndpointDnsEnabled
   */
  public function setPscWriteEndpointDnsEnabled($pscWriteEndpointDnsEnabled)
  {
    $this->pscWriteEndpointDnsEnabled = $pscWriteEndpointDnsEnabled;
  }
  /**
   * @return bool
   */
  public function getPscWriteEndpointDnsEnabled()
  {
    return $this->pscWriteEndpointDnsEnabled;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PscConfig::class, 'Google_Service_SQLAdmin_PscConfig');
