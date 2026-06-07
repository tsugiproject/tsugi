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

namespace Google\Service\PostmasterTools;

class DomainComplianceStatus extends \Google\Model
{
  protected $complianceDataType = DomainComplianceData::class;
  protected $complianceDataDataType = '';
  /**
   * Identifier. The resource name of the domain's compliance status. Format:
   * `domains/{domain_id}/complianceStatus`.
   *
   * @var string
   */
  public $name;
  protected $subdomainComplianceDataType = DomainComplianceData::class;
  protected $subdomainComplianceDataDataType = '';

  /**
   * Compliance data for the registrable domain part of the domain in `name`.
   * For example, if `name` is `domains/example.com/complianceStatus`, this
   * field contains compliance data for `example.com`.
   *
   * @param DomainComplianceData $complianceData
   */
  public function setComplianceData(DomainComplianceData $complianceData)
  {
    $this->complianceData = $complianceData;
  }
  /**
   * @return DomainComplianceData
   */
  public function getComplianceData()
  {
    return $this->complianceData;
  }
  /**
   * Identifier. The resource name of the domain's compliance status. Format:
   * `domains/{domain_id}/complianceStatus`.
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
   * Compliance data calculated specifically for the subdomain in `name`. This
   * field is only populated if the domain in `name` is a subdomain that differs
   * from its registrable domain (e.g., `sub.example.com`), and if compliance
   * data is available for that specific subdomain.
   *
   * @param DomainComplianceData $subdomainComplianceData
   */
  public function setSubdomainComplianceData(DomainComplianceData $subdomainComplianceData)
  {
    $this->subdomainComplianceData = $subdomainComplianceData;
  }
  /**
   * @return DomainComplianceData
   */
  public function getSubdomainComplianceData()
  {
    return $this->subdomainComplianceData;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DomainComplianceStatus::class, 'Google_Service_PostmasterTools_DomainComplianceStatus');
