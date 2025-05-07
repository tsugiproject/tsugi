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

namespace Google\Service\Meet;

class Participant extends \Google\Model
{
  protected $anonymousUserType = AnonymousUser::class;
  protected $anonymousUserDataType = '';
  /**
   * @var string
   */
  public $earliestStartTime;
  /**
   * @var string
   */
  public $latestEndTime;
  /**
   * @var string
   */
  public $name;
  protected $phoneUserType = PhoneUser::class;
  protected $phoneUserDataType = '';
  protected $signedinUserType = SignedinUser::class;
  protected $signedinUserDataType = '';

  /**
   * @param AnonymousUser
   */
  public function setAnonymousUser(AnonymousUser $anonymousUser)
  {
    $this->anonymousUser = $anonymousUser;
  }
  /**
   * @return AnonymousUser
   */
  public function getAnonymousUser()
  {
    return $this->anonymousUser;
  }
  /**
   * @param string
   */
  public function setEarliestStartTime($earliestStartTime)
  {
    $this->earliestStartTime = $earliestStartTime;
  }
  /**
   * @return string
   */
  public function getEarliestStartTime()
  {
    return $this->earliestStartTime;
  }
  /**
   * @param string
   */
  public function setLatestEndTime($latestEndTime)
  {
    $this->latestEndTime = $latestEndTime;
  }
  /**
   * @return string
   */
  public function getLatestEndTime()
  {
    return $this->latestEndTime;
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
   * @param PhoneUser
   */
  public function setPhoneUser(PhoneUser $phoneUser)
  {
    $this->phoneUser = $phoneUser;
  }
  /**
   * @return PhoneUser
   */
  public function getPhoneUser()
  {
    return $this->phoneUser;
  }
  /**
   * @param SignedinUser
   */
  public function setSignedinUser(SignedinUser $signedinUser)
  {
    $this->signedinUser = $signedinUser;
  }
  /**
   * @return SignedinUser
   */
  public function getSignedinUser()
  {
    return $this->signedinUser;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Participant::class, 'Google_Service_Meet_Participant');
