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

namespace Google\Service\CloudIAP;

class IapResource extends \Google\Model
{
  /**
   * @var array[]
   */
  public $expectedNextState;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $name;
  protected $nextStateOfTagsType = NextStateOfTags::class;
  protected $nextStateOfTagsDataType = '';
  /**
   * @var string
   */
  public $service;
  /**
   * @var string
   */
  public $type;

  /**
   * @param array[]
   */
  public function setExpectedNextState($expectedNextState)
  {
    $this->expectedNextState = $expectedNextState;
  }
  /**
   * @return array[]
   */
  public function getExpectedNextState()
  {
    return $this->expectedNextState;
  }
  /**
   * @param string[]
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
   * @param NextStateOfTags
   */
  public function setNextStateOfTags(NextStateOfTags $nextStateOfTags)
  {
    $this->nextStateOfTags = $nextStateOfTags;
  }
  /**
   * @return NextStateOfTags
   */
  public function getNextStateOfTags()
  {
    return $this->nextStateOfTags;
  }
  /**
   * @param string
   */
  public function setService($service)
  {
    $this->service = $service;
  }
  /**
   * @return string
   */
  public function getService()
  {
    return $this->service;
  }
  /**
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(IapResource::class, 'Google_Service_CloudIAP_IapResource');
