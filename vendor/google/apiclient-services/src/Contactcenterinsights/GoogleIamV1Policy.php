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

namespace Google\Service\Contactcenterinsights;

class GoogleIamV1Policy extends \Google\Collection
{
  protected $collection_key = 'bindings';
  protected $auditConfigsType = GoogleIamV1AuditConfig::class;
  protected $auditConfigsDataType = 'array';
  protected $bindingsType = GoogleIamV1Binding::class;
  protected $bindingsDataType = 'array';
  /**
   * @var string
   */
  public $etag;
  /**
   * @var int
   */
  public $version;

  /**
   * @param GoogleIamV1AuditConfig[]
   */
  public function setAuditConfigs($auditConfigs)
  {
    $this->auditConfigs = $auditConfigs;
  }
  /**
   * @return GoogleIamV1AuditConfig[]
   */
  public function getAuditConfigs()
  {
    return $this->auditConfigs;
  }
  /**
   * @param GoogleIamV1Binding[]
   */
  public function setBindings($bindings)
  {
    $this->bindings = $bindings;
  }
  /**
   * @return GoogleIamV1Binding[]
   */
  public function getBindings()
  {
    return $this->bindings;
  }
  /**
   * @param string
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * @param int
   */
  public function setVersion($version)
  {
    $this->version = $version;
  }
  /**
   * @return int
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleIamV1Policy::class, 'Google_Service_Contactcenterinsights_GoogleIamV1Policy');
