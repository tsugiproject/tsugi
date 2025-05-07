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

namespace Google\Service\CloudCommercePartnerProcurementService;

class Account extends \Google\Collection
{
  protected $collection_key = 'approvals';
  protected $approvalsType = Approval::class;
  protected $approvalsDataType = 'array';
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var array[]
   */
  public $inputProperties;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $provider;
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param Approval[]
   */
  public function setApprovals($approvals)
  {
    $this->approvals = $approvals;
  }
  /**
   * @return Approval[]
   */
  public function getApprovals()
  {
    return $this->approvals;
  }
  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param array[]
   */
  public function setInputProperties($inputProperties)
  {
    $this->inputProperties = $inputProperties;
  }
  /**
   * @return array[]
   */
  public function getInputProperties()
  {
    return $this->inputProperties;
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
  public function setProvider($provider)
  {
    $this->provider = $provider;
  }
  /**
   * @return string
   */
  public function getProvider()
  {
    return $this->provider;
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
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Account::class, 'Google_Service_CloudCommercePartnerProcurementService_Account');
