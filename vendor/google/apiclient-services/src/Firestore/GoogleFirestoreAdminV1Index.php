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

namespace Google\Service\Firestore;

class GoogleFirestoreAdminV1Index extends \Google\Collection
{
  protected $collection_key = 'fields';
  /**
   * @var string
   */
  public $apiScope;
  /**
   * @var string
   */
  public $density;
  protected $fieldsType = GoogleFirestoreAdminV1IndexField::class;
  protected $fieldsDataType = 'array';
  /**
   * @var bool
   */
  public $multikey;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $queryScope;
  /**
   * @var string
   */
  public $state;

  /**
   * @param string
   */
  public function setApiScope($apiScope)
  {
    $this->apiScope = $apiScope;
  }
  /**
   * @return string
   */
  public function getApiScope()
  {
    return $this->apiScope;
  }
  /**
   * @param string
   */
  public function setDensity($density)
  {
    $this->density = $density;
  }
  /**
   * @return string
   */
  public function getDensity()
  {
    return $this->density;
  }
  /**
   * @param GoogleFirestoreAdminV1IndexField[]
   */
  public function setFields($fields)
  {
    $this->fields = $fields;
  }
  /**
   * @return GoogleFirestoreAdminV1IndexField[]
   */
  public function getFields()
  {
    return $this->fields;
  }
  /**
   * @param bool
   */
  public function setMultikey($multikey)
  {
    $this->multikey = $multikey;
  }
  /**
   * @return bool
   */
  public function getMultikey()
  {
    return $this->multikey;
  }
  /**
   * @param string
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
   * @param string
   */
  public function setQueryScope($queryScope)
  {
    $this->queryScope = $queryScope;
  }
  /**
   * @return string
   */
  public function getQueryScope()
  {
    return $this->queryScope;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleFirestoreAdminV1Index::class, 'Google_Service_Firestore_GoogleFirestoreAdminV1Index');
