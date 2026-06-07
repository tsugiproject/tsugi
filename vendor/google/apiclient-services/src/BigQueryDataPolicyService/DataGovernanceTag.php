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

namespace Google\Service\BigQueryDataPolicyService;

class DataGovernanceTag extends \Google\Model
{
  /**
   * Optional. Tag keys are globally unique. Tag key is expected to be in the
   * namespaced format, for example `parent-id/pii` where `parent-id` is the ID
   * of the parent organization or project resource for this tag key.
   *
   * @var string
   */
  public $key;
  /**
   * Optional. Specifies the tag value as the short name, for example
   * `sensitive`.
   *
   * @var string
   */
  public $value;

  /**
   * Optional. Tag keys are globally unique. Tag key is expected to be in the
   * namespaced format, for example `parent-id/pii` where `parent-id` is the ID
   * of the parent organization or project resource for this tag key.
   *
   * @param string $key
   */
  public function setKey($key)
  {
    $this->key = $key;
  }
  /**
   * @return string
   */
  public function getKey()
  {
    return $this->key;
  }
  /**
   * Optional. Specifies the tag value as the short name, for example
   * `sensitive`.
   *
   * @param string $value
   */
  public function setValue($value)
  {
    $this->value = $value;
  }
  /**
   * @return string
   */
  public function getValue()
  {
    return $this->value;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DataGovernanceTag::class, 'Google_Service_BigQueryDataPolicyService_DataGovernanceTag');
