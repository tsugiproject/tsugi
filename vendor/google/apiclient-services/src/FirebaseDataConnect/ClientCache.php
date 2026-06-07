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

namespace Google\Service\FirebaseDataConnect;

class ClientCache extends \Google\Model
{
  /**
   * Optional. A field that, if true, means that responses served by this
   * connector will include entityIds in GraphQL response extensions. This helps
   * the client SDK cache responses in an improved way, known as "normalized
   * caching", if caching is enabled on the client. Each entityId is a stable
   * key based on primary key values. Therefore, this field should only be set
   * to true if the primary keys of accessed tables do not contain sensitive
   * information.
   *
   * @var bool
   */
  public $entityIdIncluded;
  /**
   * Optional. A field that, if true, enables stricter validation on the
   * connector source code to make sure the operation response shapes are
   * suitable for client-side caching. This can include additional errors and
   * warnings. For example, using the same alias for different fields is
   * disallowed, as it may cause conflicts or confusion with normalized caching.
   * (This field is off by default for compatibility, but enabling it is highly
   * recommended to catch common caching pitfalls.)
   *
   * @var bool
   */
  public $strictValidationEnabled;

  /**
   * Optional. A field that, if true, means that responses served by this
   * connector will include entityIds in GraphQL response extensions. This helps
   * the client SDK cache responses in an improved way, known as "normalized
   * caching", if caching is enabled on the client. Each entityId is a stable
   * key based on primary key values. Therefore, this field should only be set
   * to true if the primary keys of accessed tables do not contain sensitive
   * information.
   *
   * @param bool $entityIdIncluded
   */
  public function setEntityIdIncluded($entityIdIncluded)
  {
    $this->entityIdIncluded = $entityIdIncluded;
  }
  /**
   * @return bool
   */
  public function getEntityIdIncluded()
  {
    return $this->entityIdIncluded;
  }
  /**
   * Optional. A field that, if true, enables stricter validation on the
   * connector source code to make sure the operation response shapes are
   * suitable for client-side caching. This can include additional errors and
   * warnings. For example, using the same alias for different fields is
   * disallowed, as it may cause conflicts or confusion with normalized caching.
   * (This field is off by default for compatibility, but enabling it is highly
   * recommended to catch common caching pitfalls.)
   *
   * @param bool $strictValidationEnabled
   */
  public function setStrictValidationEnabled($strictValidationEnabled)
  {
    $this->strictValidationEnabled = $strictValidationEnabled;
  }
  /**
   * @return bool
   */
  public function getStrictValidationEnabled()
  {
    return $this->strictValidationEnabled;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ClientCache::class, 'Google_Service_FirebaseDataConnect_ClientCache');
