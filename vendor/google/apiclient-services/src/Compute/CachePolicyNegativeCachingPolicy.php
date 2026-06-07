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

class CachePolicyNegativeCachingPolicy extends \Google\Model
{
  /**
   * The HTTP status code to define a TTL against. Only HTTP status codes 300,
   * 301, 302, 307, 308, 404, 405, 410, 421, 451 and 501 can be specified as
   * values, and you cannot specify a status code more than once.
   *
   * @var int
   */
  public $code;
  protected $ttlType = Duration::class;
  protected $ttlDataType = '';

  /**
   * The HTTP status code to define a TTL against. Only HTTP status codes 300,
   * 301, 302, 307, 308, 404, 405, 410, 421, 451 and 501 can be specified as
   * values, and you cannot specify a status code more than once.
   *
   * @param int $code
   */
  public function setCode($code)
  {
    $this->code = $code;
  }
  /**
   * @return int
   */
  public function getCode()
  {
    return $this->code;
  }
  /**
   * The TTL (in seconds) for which to cache responses with the corresponding
   * status code. The maximum allowed value is 1800s (30 minutes). Infrequently
   * accessed objects may be evicted from the cache before the defined TTL.
   *
   * @param Duration $ttl
   */
  public function setTtl(Duration $ttl)
  {
    $this->ttl = $ttl;
  }
  /**
   * @return Duration
   */
  public function getTtl()
  {
    return $this->ttl;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CachePolicyNegativeCachingPolicy::class, 'Google_Service_Compute_CachePolicyNegativeCachingPolicy');
