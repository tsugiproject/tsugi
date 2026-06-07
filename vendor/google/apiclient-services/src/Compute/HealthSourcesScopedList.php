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

namespace Google\Service\Compute;

class HealthSourcesScopedList extends \Google\Collection
{
  protected $collection_key = 'healthSources';
  protected $healthSourcesType = HealthSource::class;
  protected $healthSourcesDataType = 'array';
  protected $warningType = HealthSourcesScopedListWarning::class;
  protected $warningDataType = '';

  /**
   * A list of HealthSources contained in this scope.
   *
   * @param HealthSource[] $healthSources
   */
  public function setHealthSources($healthSources)
  {
    $this->healthSources = $healthSources;
  }
  /**
   * @return HealthSource[]
   */
  public function getHealthSources()
  {
    return $this->healthSources;
  }
  /**
   * Informational warning which replaces the list of health sources when the
   * list is empty.
   *
   * @param HealthSourcesScopedListWarning $warning
   */
  public function setWarning(HealthSourcesScopedListWarning $warning)
  {
    $this->warning = $warning;
  }
  /**
   * @return HealthSourcesScopedListWarning
   */
  public function getWarning()
  {
    return $this->warning;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HealthSourcesScopedList::class, 'Google_Service_Compute_HealthSourcesScopedList');
