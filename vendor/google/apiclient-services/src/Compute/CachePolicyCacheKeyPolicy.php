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

class CachePolicyCacheKeyPolicy extends \Google\Collection
{
  protected $collection_key = 'includedQueryParameters';
  /**
   * Names of query string parameters to exclude in cache keys. All other
   * parameters will be included. Either specify `excludedQueryParameters` or
   * `includedQueryParameters`, not both. '&' and '=' will be percent encoded
   * and not treated as delimiters.
   *
   * Note: This field applies to routes that use backend services. Attempting to
   * set it on a route that points exclusively to Backend Buckets will result in
   * a configuration error. For routes that point to a Backend Bucket, use
   * `includedQueryParameters` to define which parameters should be part of the
   * cache key.
   *
   * @var string[]
   */
  public $excludedQueryParameters;
  /**
   * If true, requests to different hosts will be cached separately.
   *
   * Note: This setting is only applicable to routes that use a Backend Service.
   * It does not affect requests served by a Backend Bucket, as the host is
   * never included in a Backend Bucket's cache key. Attempting to set it on a
   * route that points exclusively to Backend Buckets will result in a
   * configuration error.
   *
   * @var bool
   */
  public $includeHost;
  /**
   * If true, http and https requests will be cached separately.
   *
   * Note: This setting is only applicable to routes that use a Backend Service.
   * It does not affect requests served by a Backend Bucket, as the protocol is
   * never included in a Backend Bucket's cache key. Attempting to set on a
   * route that points exclusively to Backend Buckets will result in a
   * configuration error.
   *
   * @var bool
   */
  public $includeProtocol;
  /**
   * If true, include query string parameters in the cache key according to
   * `includedQueryParameters` and `excludedQueryParameters`. If neither is set,
   * the entire query string will be included. If false, the query string will
   * be excluded from the cache key entirely.
   *
   * Note: This field applies to routes that use backend services. Attempting to
   * set it on a route that points exclusively to Backend Buckets will result in
   * a configuration error. For routes that point to a Backend Bucket, use
   * `includedQueryParameters` to define which parameters should be part of the
   * cache key.
   *
   * @var bool
   */
  public $includeQueryString;
  /**
   * Allows HTTP cookies (by name) to be used in the cache key. The name=value
   * pair will be used in the cache key Cloud CDN generates.
   *
   * Note: This setting is only applicable to routes that use a Backend Service.
   * It does not affect requests served by a Backend Bucket. Attempting to set
   * it on a route that points exclusively to Backend Buckets will result in a
   * configuration error. Up to 5 cookie names can be specified.
   *
   * @var string[]
   */
  public $includedCookieNames;
  /**
   * Allows HTTP request headers (by name) to be used in the cache key.
   *
   * @var string[]
   */
  public $includedHeaderNames;
  /**
   * Names of query string parameters to include in cache keys. All other
   * parameters will be excluded. Either specify `includedQueryParameters` or
   * `excludedQueryParameters`, not both. '&' and '=' will be percent encoded
   * and not treated as delimiters.
   *
   * @var string[]
   */
  public $includedQueryParameters;

  /**
   * Names of query string parameters to exclude in cache keys. All other
   * parameters will be included. Either specify `excludedQueryParameters` or
   * `includedQueryParameters`, not both. '&' and '=' will be percent encoded
   * and not treated as delimiters.
   *
   * Note: This field applies to routes that use backend services. Attempting to
   * set it on a route that points exclusively to Backend Buckets will result in
   * a configuration error. For routes that point to a Backend Bucket, use
   * `includedQueryParameters` to define which parameters should be part of the
   * cache key.
   *
   * @param string[] $excludedQueryParameters
   */
  public function setExcludedQueryParameters($excludedQueryParameters)
  {
    $this->excludedQueryParameters = $excludedQueryParameters;
  }
  /**
   * @return string[]
   */
  public function getExcludedQueryParameters()
  {
    return $this->excludedQueryParameters;
  }
  /**
   * If true, requests to different hosts will be cached separately.
   *
   * Note: This setting is only applicable to routes that use a Backend Service.
   * It does not affect requests served by a Backend Bucket, as the host is
   * never included in a Backend Bucket's cache key. Attempting to set it on a
   * route that points exclusively to Backend Buckets will result in a
   * configuration error.
   *
   * @param bool $includeHost
   */
  public function setIncludeHost($includeHost)
  {
    $this->includeHost = $includeHost;
  }
  /**
   * @return bool
   */
  public function getIncludeHost()
  {
    return $this->includeHost;
  }
  /**
   * If true, http and https requests will be cached separately.
   *
   * Note: This setting is only applicable to routes that use a Backend Service.
   * It does not affect requests served by a Backend Bucket, as the protocol is
   * never included in a Backend Bucket's cache key. Attempting to set on a
   * route that points exclusively to Backend Buckets will result in a
   * configuration error.
   *
   * @param bool $includeProtocol
   */
  public function setIncludeProtocol($includeProtocol)
  {
    $this->includeProtocol = $includeProtocol;
  }
  /**
   * @return bool
   */
  public function getIncludeProtocol()
  {
    return $this->includeProtocol;
  }
  /**
   * If true, include query string parameters in the cache key according to
   * `includedQueryParameters` and `excludedQueryParameters`. If neither is set,
   * the entire query string will be included. If false, the query string will
   * be excluded from the cache key entirely.
   *
   * Note: This field applies to routes that use backend services. Attempting to
   * set it on a route that points exclusively to Backend Buckets will result in
   * a configuration error. For routes that point to a Backend Bucket, use
   * `includedQueryParameters` to define which parameters should be part of the
   * cache key.
   *
   * @param bool $includeQueryString
   */
  public function setIncludeQueryString($includeQueryString)
  {
    $this->includeQueryString = $includeQueryString;
  }
  /**
   * @return bool
   */
  public function getIncludeQueryString()
  {
    return $this->includeQueryString;
  }
  /**
   * Allows HTTP cookies (by name) to be used in the cache key. The name=value
   * pair will be used in the cache key Cloud CDN generates.
   *
   * Note: This setting is only applicable to routes that use a Backend Service.
   * It does not affect requests served by a Backend Bucket. Attempting to set
   * it on a route that points exclusively to Backend Buckets will result in a
   * configuration error. Up to 5 cookie names can be specified.
   *
   * @param string[] $includedCookieNames
   */
  public function setIncludedCookieNames($includedCookieNames)
  {
    $this->includedCookieNames = $includedCookieNames;
  }
  /**
   * @return string[]
   */
  public function getIncludedCookieNames()
  {
    return $this->includedCookieNames;
  }
  /**
   * Allows HTTP request headers (by name) to be used in the cache key.
   *
   * @param string[] $includedHeaderNames
   */
  public function setIncludedHeaderNames($includedHeaderNames)
  {
    $this->includedHeaderNames = $includedHeaderNames;
  }
  /**
   * @return string[]
   */
  public function getIncludedHeaderNames()
  {
    return $this->includedHeaderNames;
  }
  /**
   * Names of query string parameters to include in cache keys. All other
   * parameters will be excluded. Either specify `includedQueryParameters` or
   * `excludedQueryParameters`, not both. '&' and '=' will be percent encoded
   * and not treated as delimiters.
   *
   * @param string[] $includedQueryParameters
   */
  public function setIncludedQueryParameters($includedQueryParameters)
  {
    $this->includedQueryParameters = $includedQueryParameters;
  }
  /**
   * @return string[]
   */
  public function getIncludedQueryParameters()
  {
    return $this->includedQueryParameters;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CachePolicyCacheKeyPolicy::class, 'Google_Service_Compute_CachePolicyCacheKeyPolicy');
