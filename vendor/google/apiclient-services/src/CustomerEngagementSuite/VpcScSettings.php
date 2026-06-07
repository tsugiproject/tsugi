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

class VpcScSettings extends \Google\Collection
{
  protected $collection_key = 'allowedOrigins';
  /**
   * Optional. The allowed HTTP(s) origins that OpenAPI tools in the App are
   * able to directly call when VPC Service Controls are enabled. These strings
   * must match the origin exactly, including the port if specified. For
   * example, "https://example.com" or "https://example.com:443". This list does
   * not yet apply to Python tools that may make direct HTTP calls.
   *
   * @var string[]
   */
  public $allowedOrigins;

  /**
   * Optional. The allowed HTTP(s) origins that OpenAPI tools in the App are
   * able to directly call when VPC Service Controls are enabled. These strings
   * must match the origin exactly, including the port if specified. For
   * example, "https://example.com" or "https://example.com:443". This list does
   * not yet apply to Python tools that may make direct HTTP calls.
   *
   * @param string[] $allowedOrigins
   */
  public function setAllowedOrigins($allowedOrigins)
  {
    $this->allowedOrigins = $allowedOrigins;
  }
  /**
   * @return string[]
   */
  public function getAllowedOrigins()
  {
    return $this->allowedOrigins;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(VpcScSettings::class, 'Google_Service_CustomerEngagementSuite_VpcScSettings');
