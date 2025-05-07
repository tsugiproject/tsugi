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

namespace Google\Service\Css;

class ItemLevelIssue extends \Google\Collection
{
  protected $collection_key = 'applicableCountries';
  /**
   * @var string[]
   */
  public $applicableCountries;
  /**
   * @var string
   */
  public $attribute;
  /**
   * @var string
   */
  public $code;
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $destination;
  /**
   * @var string
   */
  public $detail;
  /**
   * @var string
   */
  public $documentation;
  /**
   * @var string
   */
  public $resolution;
  /**
   * @var string
   */
  public $servability;

  /**
   * @param string[]
   */
  public function setApplicableCountries($applicableCountries)
  {
    $this->applicableCountries = $applicableCountries;
  }
  /**
   * @return string[]
   */
  public function getApplicableCountries()
  {
    return $this->applicableCountries;
  }
  /**
   * @param string
   */
  public function setAttribute($attribute)
  {
    $this->attribute = $attribute;
  }
  /**
   * @return string
   */
  public function getAttribute()
  {
    return $this->attribute;
  }
  /**
   * @param string
   */
  public function setCode($code)
  {
    $this->code = $code;
  }
  /**
   * @return string
   */
  public function getCode()
  {
    return $this->code;
  }
  /**
   * @param string
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
   * @param string
   */
  public function setDestination($destination)
  {
    $this->destination = $destination;
  }
  /**
   * @return string
   */
  public function getDestination()
  {
    return $this->destination;
  }
  /**
   * @param string
   */
  public function setDetail($detail)
  {
    $this->detail = $detail;
  }
  /**
   * @return string
   */
  public function getDetail()
  {
    return $this->detail;
  }
  /**
   * @param string
   */
  public function setDocumentation($documentation)
  {
    $this->documentation = $documentation;
  }
  /**
   * @return string
   */
  public function getDocumentation()
  {
    return $this->documentation;
  }
  /**
   * @param string
   */
  public function setResolution($resolution)
  {
    $this->resolution = $resolution;
  }
  /**
   * @return string
   */
  public function getResolution()
  {
    return $this->resolution;
  }
  /**
   * @param string
   */
  public function setServability($servability)
  {
    $this->servability = $servability;
  }
  /**
   * @return string
   */
  public function getServability()
  {
    return $this->servability;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ItemLevelIssue::class, 'Google_Service_Css_ItemLevelIssue');
