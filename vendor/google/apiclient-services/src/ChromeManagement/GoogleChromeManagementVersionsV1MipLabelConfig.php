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

namespace Google\Service\ChromeManagement;

class GoogleChromeManagementVersionsV1MipLabelConfig extends \Google\Collection
{
  protected $collection_key = 'domains';
  /**
   * Optional. Domain can be used optionally for the corner case where one
   * Dasher customer ID maps to multiple Microsoft tenant ID. Each domain can be
   * verified with at most one Microsoft tenant.
   *
   * @var string[]
   */
  public $domains;
  /**
   * Required. Microsoft tenant ID.
   *
   * @var string
   */
  public $microsoftTenantId;

  /**
   * Optional. Domain can be used optionally for the corner case where one
   * Dasher customer ID maps to multiple Microsoft tenant ID. Each domain can be
   * verified with at most one Microsoft tenant.
   *
   * @param string[] $domains
   */
  public function setDomains($domains)
  {
    $this->domains = $domains;
  }
  /**
   * @return string[]
   */
  public function getDomains()
  {
    return $this->domains;
  }
  /**
   * Required. Microsoft tenant ID.
   *
   * @param string $microsoftTenantId
   */
  public function setMicrosoftTenantId($microsoftTenantId)
  {
    $this->microsoftTenantId = $microsoftTenantId;
  }
  /**
   * @return string
   */
  public function getMicrosoftTenantId()
  {
    return $this->microsoftTenantId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleChromeManagementVersionsV1MipLabelConfig::class, 'Google_Service_ChromeManagement_GoogleChromeManagementVersionsV1MipLabelConfig');
