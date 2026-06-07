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

namespace Google\Service\CloudNumberRegistry;

class CustomRange extends \Google\Collection
{
  protected $collection_key = 'attributes';
  protected $attributesType = Attribute::class;
  protected $attributesDataType = 'array';
  /**
   * Optional. Description of the CustomRange.
   *
   * @var string
   */
  public $description;
  /**
   * Optional. The IPv4 CIDR range of the CustomRange.
   *
   * @var string
   */
  public $ipv4CidrRange;
  /**
   * Optional. The IPv6 CIDR range of the CustomRange.
   *
   * @var string
   */
  public $ipv6CidrRange;
  /**
   * Optional. Labels as key value pairs
   *
   * @var string[]
   */
  public $labels;
  /**
   * Required. Identifier. name of resource
   *
   * @var string
   */
  public $name;
  /**
   * Optional. The parent range of the CustomRange. Do not allow setting parent
   * range if realm is specified. Format must follow this pattern:
   * projects/{project}/locations/{location}/customRanges/{custom_range}
   *
   * @var string
   */
  public $parentRange;
  /**
   * Optional. The realm of the CustomRange. The realm must be in the same
   * project as the custom range. Do not allow setting realm if parent range is
   * specified, since the realm should be inherited from the parent range.
   * Format must follow this pattern:
   * projects/{project}/locations/{location}/realms/{realm}
   *
   * @var string
   */
  public $realm;
  /**
   * Output only. The registry book of the CustomRange. This field is inherited
   * from the realm or parent range depending on which one is specified.
   *
   * @var string
   */
  public $registryBook;

  /**
   * Optional. The attributes of the CustomRange.
   *
   * @param Attribute[] $attributes
   */
  public function setAttributes($attributes)
  {
    $this->attributes = $attributes;
  }
  /**
   * @return Attribute[]
   */
  public function getAttributes()
  {
    return $this->attributes;
  }
  /**
   * Optional. Description of the CustomRange.
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
   * Optional. The IPv4 CIDR range of the CustomRange.
   *
   * @param string $ipv4CidrRange
   */
  public function setIpv4CidrRange($ipv4CidrRange)
  {
    $this->ipv4CidrRange = $ipv4CidrRange;
  }
  /**
   * @return string
   */
  public function getIpv4CidrRange()
  {
    return $this->ipv4CidrRange;
  }
  /**
   * Optional. The IPv6 CIDR range of the CustomRange.
   *
   * @param string $ipv6CidrRange
   */
  public function setIpv6CidrRange($ipv6CidrRange)
  {
    $this->ipv6CidrRange = $ipv6CidrRange;
  }
  /**
   * @return string
   */
  public function getIpv6CidrRange()
  {
    return $this->ipv6CidrRange;
  }
  /**
   * Optional. Labels as key value pairs
   *
   * @param string[] $labels
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
  }
  /**
   * Required. Identifier. name of resource
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
   * Optional. The parent range of the CustomRange. Do not allow setting parent
   * range if realm is specified. Format must follow this pattern:
   * projects/{project}/locations/{location}/customRanges/{custom_range}
   *
   * @param string $parentRange
   */
  public function setParentRange($parentRange)
  {
    $this->parentRange = $parentRange;
  }
  /**
   * @return string
   */
  public function getParentRange()
  {
    return $this->parentRange;
  }
  /**
   * Optional. The realm of the CustomRange. The realm must be in the same
   * project as the custom range. Do not allow setting realm if parent range is
   * specified, since the realm should be inherited from the parent range.
   * Format must follow this pattern:
   * projects/{project}/locations/{location}/realms/{realm}
   *
   * @param string $realm
   */
  public function setRealm($realm)
  {
    $this->realm = $realm;
  }
  /**
   * @return string
   */
  public function getRealm()
  {
    return $this->realm;
  }
  /**
   * Output only. The registry book of the CustomRange. This field is inherited
   * from the realm or parent range depending on which one is specified.
   *
   * @param string $registryBook
   */
  public function setRegistryBook($registryBook)
  {
    $this->registryBook = $registryBook;
  }
  /**
   * @return string
   */
  public function getRegistryBook()
  {
    return $this->registryBook;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CustomRange::class, 'Google_Service_CloudNumberRegistry_CustomRange');
