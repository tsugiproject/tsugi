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

namespace Google\Service\CloudDomains;

class EmailForwarding extends \Google\Model
{
  /**
   * @var string
   */
  public $alias;
  /**
   * @var string
   */
  public $targetEmailAddress;

  /**
   * @param string
   */
  public function setAlias($alias)
  {
    $this->alias = $alias;
  }
  /**
   * @return string
   */
  public function getAlias()
  {
    return $this->alias;
  }
  /**
   * @param string
   */
  public function setTargetEmailAddress($targetEmailAddress)
  {
    $this->targetEmailAddress = $targetEmailAddress;
  }
  /**
   * @return string
   */
  public function getTargetEmailAddress()
  {
    return $this->targetEmailAddress;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EmailForwarding::class, 'Google_Service_CloudDomains_EmailForwarding');
