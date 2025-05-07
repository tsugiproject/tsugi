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

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1AnswerStep extends \Google\Collection
{
  protected $collection_key = 'actions';
  protected $actionsType = GoogleCloudDiscoveryengineV1AnswerStepAction::class;
  protected $actionsDataType = 'array';
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $thought;

  /**
   * @param GoogleCloudDiscoveryengineV1AnswerStepAction[]
   */
  public function setActions($actions)
  {
    $this->actions = $actions;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1AnswerStepAction[]
   */
  public function getActions()
  {
    return $this->actions;
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
  /**
   * @param string
   */
  public function setThought($thought)
  {
    $this->thought = $thought;
  }
  /**
   * @return string
   */
  public function getThought()
  {
    return $this->thought;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1AnswerStep::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1AnswerStep');
