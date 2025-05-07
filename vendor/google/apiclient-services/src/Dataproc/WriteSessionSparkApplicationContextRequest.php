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

namespace Google\Service\Dataproc;

class WriteSessionSparkApplicationContextRequest extends \Google\Collection
{
  protected $collection_key = 'sparkWrapperObjects';
  /**
   * @var string
   */
  public $parent;
  protected $sparkWrapperObjectsType = SparkWrapperObject::class;
  protected $sparkWrapperObjectsDataType = 'array';

  /**
   * @param string
   */
  public function setParent($parent)
  {
    $this->parent = $parent;
  }
  /**
   * @return string
   */
  public function getParent()
  {
    return $this->parent;
  }
  /**
   * @param SparkWrapperObject[]
   */
  public function setSparkWrapperObjects($sparkWrapperObjects)
  {
    $this->sparkWrapperObjects = $sparkWrapperObjects;
  }
  /**
   * @return SparkWrapperObject[]
   */
  public function getSparkWrapperObjects()
  {
    return $this->sparkWrapperObjects;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(WriteSessionSparkApplicationContextRequest::class, 'Google_Service_Dataproc_WriteSessionSparkApplicationContextRequest');
