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

namespace Google\Service\ServiceNetworking;

class GoSettings extends \Google\Model
{
  protected $commonType = CommonLanguageSettings::class;
  protected $commonDataType = '';
  /**
   * @var string[]
   */
  public $renamedServices;

  /**
   * @param CommonLanguageSettings
   */
  public function setCommon(CommonLanguageSettings $common)
  {
    $this->common = $common;
  }
  /**
   * @return CommonLanguageSettings
   */
  public function getCommon()
  {
    return $this->common;
  }
  /**
   * @param string[]
   */
  public function setRenamedServices($renamedServices)
  {
    $this->renamedServices = $renamedServices;
  }
  /**
   * @return string[]
   */
  public function getRenamedServices()
  {
    return $this->renamedServices;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoSettings::class, 'Google_Service_ServiceNetworking_GoSettings');
