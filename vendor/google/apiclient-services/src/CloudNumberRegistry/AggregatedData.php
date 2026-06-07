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

class AggregatedData extends \Google\Model
{
  /**
   * Output only. Number of custom ranges in the RegistryBook.
   *
   * @var int
   */
  public $customRangesCount;
  /**
   * Output only. Number of custom realms in the RegistryBook.
   *
   * @var int
   */
  public $customRealmsCount;
  /**
   * Output only. Number of discovered ranges in the RegistryBook.
   *
   * @var int
   */
  public $discoveredRangesCount;
  /**
   * Output only. Number of discovered realms in the RegistryBook.
   *
   * @var int
   */
  public $discoveredRealmsCount;
  /**
   * Output only. Number of scopes unique to the RegistryBook.
   *
   * @var int
   */
  public $uniqueScopesCount;

  /**
   * Output only. Number of custom ranges in the RegistryBook.
   *
   * @param int $customRangesCount
   */
  public function setCustomRangesCount($customRangesCount)
  {
    $this->customRangesCount = $customRangesCount;
  }
  /**
   * @return int
   */
  public function getCustomRangesCount()
  {
    return $this->customRangesCount;
  }
  /**
   * Output only. Number of custom realms in the RegistryBook.
   *
   * @param int $customRealmsCount
   */
  public function setCustomRealmsCount($customRealmsCount)
  {
    $this->customRealmsCount = $customRealmsCount;
  }
  /**
   * @return int
   */
  public function getCustomRealmsCount()
  {
    return $this->customRealmsCount;
  }
  /**
   * Output only. Number of discovered ranges in the RegistryBook.
   *
   * @param int $discoveredRangesCount
   */
  public function setDiscoveredRangesCount($discoveredRangesCount)
  {
    $this->discoveredRangesCount = $discoveredRangesCount;
  }
  /**
   * @return int
   */
  public function getDiscoveredRangesCount()
  {
    return $this->discoveredRangesCount;
  }
  /**
   * Output only. Number of discovered realms in the RegistryBook.
   *
   * @param int $discoveredRealmsCount
   */
  public function setDiscoveredRealmsCount($discoveredRealmsCount)
  {
    $this->discoveredRealmsCount = $discoveredRealmsCount;
  }
  /**
   * @return int
   */
  public function getDiscoveredRealmsCount()
  {
    return $this->discoveredRealmsCount;
  }
  /**
   * Output only. Number of scopes unique to the RegistryBook.
   *
   * @param int $uniqueScopesCount
   */
  public function setUniqueScopesCount($uniqueScopesCount)
  {
    $this->uniqueScopesCount = $uniqueScopesCount;
  }
  /**
   * @return int
   */
  public function getUniqueScopesCount()
  {
    return $this->uniqueScopesCount;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AggregatedData::class, 'Google_Service_CloudNumberRegistry_AggregatedData');
