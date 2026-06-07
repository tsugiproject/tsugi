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

class CachePolicy extends \Google\Collection
{
  /**
   * Automatically cache static content, including common image formats, media
   * (video and audio), and web assets (JavaScript and CSS). Requests and
   * responses that are marked as uncacheable, as well as dynamic content
   * (including HTML), will not be cached.
   */
  public const CACHE_MODE_CACHE_ALL_STATIC = 'CACHE_ALL_STATIC';
  /**
   * Cache all content, ignoring any "private", "no-store" or "no-cache"
   * directives in Cache-Control response headers. Warning: this may result in
   * Cloud CDN caching private, per-user (user identifiable) content.
   */
  public const CACHE_MODE_FORCE_CACHE_ALL = 'FORCE_CACHE_ALL';
  /**
   * Requires the origin to set valid caching headers to cache content.
   * Responses without these headers will not be cached at the edge, and will
   * require a full trip to the origin on every request, potentially impacting
   * performance and increasing load on the origin server.
   */
  public const CACHE_MODE_USE_ORIGIN_HEADERS = 'USE_ORIGIN_HEADERS';
  protected $collection_key = 'negativeCachingPolicy';
  /**
   * Bypass the cache when the specified request headers are matched by name,
   * e.g. Pragma or Authorization headers. Values are case-insensitive. Up to 5
   * header names can be specified. The cache is bypassed for all `cacheMode`
   * values.
   *
   * @var string[]
   */
  public $cacheBypassRequestHeaderNames;
  protected $cacheKeyPolicyType = CachePolicyCacheKeyPolicy::class;
  protected $cacheKeyPolicyDataType = '';
  /**
   * Specifies the cache setting for all responses from this route. If not
   * specified, Cloud CDN uses `CACHE_ALL_STATIC` mode.
   *
   * @var string
   */
  public $cacheMode;
  protected $clientTtlType = Duration::class;
  protected $clientTtlDataType = '';
  protected $defaultTtlType = Duration::class;
  protected $defaultTtlDataType = '';
  protected $maxTtlType = Duration::class;
  protected $maxTtlDataType = '';
  /**
   * Negative caching allows per-status code TTLs to be set, in order to apply
   * fine-grained caching for common errors or redirects. This can reduce the
   * load on your origin and improve end-user experience by reducing response
   * latency. When the `cacheMode` is set to `CACHE_ALL_STATIC` or
   * `USE_ORIGIN_HEADERS`, negative caching applies to responses with the
   * specified response code that lack any Cache-Control, Expires, or Pragma:
   * no-cache directives. When the `cacheMode` is set to `FORCE_CACHE_ALL`,
   * negative caching applies to all responses with the specified response code,
   * and overrides any caching headers. By default, Cloud CDN applies the
   * following TTLs to these HTTP status codes:
   *
   * * 300 (Multiple Choice), 301, 308 (Permanent Redirects): 10m * 404 (Not
   * Found), 410 (Gone), 451 (Unavailable For Legal Reasons): 120s * 405 (Method
   * Not Found), 501 (Not Implemented): 60s
   *
   * These defaults can be overridden in `negativeCachingPolicy`. If not
   * specified, Cloud CDN applies negative caching by default.
   *
   * @var bool
   */
  public $negativeCaching;
  protected $negativeCachingPolicyType = CachePolicyNegativeCachingPolicy::class;
  protected $negativeCachingPolicyDataType = 'array';
  /**
   * If true then Cloud CDN will combine multiple concurrent cache fill requests
   * into a small number of requests to the origin. If not specified, Cloud CDN
   * applies request coalescing by default.
   *
   * @var bool
   */
  public $requestCoalescing;
  protected $serveWhileStaleType = Duration::class;
  protected $serveWhileStaleDataType = '';

  /**
   * Bypass the cache when the specified request headers are matched by name,
   * e.g. Pragma or Authorization headers. Values are case-insensitive. Up to 5
   * header names can be specified. The cache is bypassed for all `cacheMode`
   * values.
   *
   * @param string[] $cacheBypassRequestHeaderNames
   */
  public function setCacheBypassRequestHeaderNames($cacheBypassRequestHeaderNames)
  {
    $this->cacheBypassRequestHeaderNames = $cacheBypassRequestHeaderNames;
  }
  /**
   * @return string[]
   */
  public function getCacheBypassRequestHeaderNames()
  {
    return $this->cacheBypassRequestHeaderNames;
  }
  /**
   * The cache key configuration. If not specified, the default behavior depends
   * on the backend type: for Backend Services, the complete request URI is
   * used; for Backend Buckets, the request URI is used without the protocol or
   * host, and only query parameters known to Cloud Storage are included.
   *
   * @param CachePolicyCacheKeyPolicy $cacheKeyPolicy
   */
  public function setCacheKeyPolicy(CachePolicyCacheKeyPolicy $cacheKeyPolicy)
  {
    $this->cacheKeyPolicy = $cacheKeyPolicy;
  }
  /**
   * @return CachePolicyCacheKeyPolicy
   */
  public function getCacheKeyPolicy()
  {
    return $this->cacheKeyPolicy;
  }
  /**
   * Specifies the cache setting for all responses from this route. If not
   * specified, Cloud CDN uses `CACHE_ALL_STATIC` mode.
   *
   * Accepted values: CACHE_ALL_STATIC, FORCE_CACHE_ALL, USE_ORIGIN_HEADERS
   *
   * @param self::CACHE_MODE_* $cacheMode
   */
  public function setCacheMode($cacheMode)
  {
    $this->cacheMode = $cacheMode;
  }
  /**
   * @return self::CACHE_MODE_*
   */
  public function getCacheMode()
  {
    return $this->cacheMode;
  }
  /**
   * Specifies a separate client (e.g. browser client) maximum TTL for cached
   * content. This is used to clamp the max-age (or Expires) value sent to the
   * client. With `FORCE_CACHE_ALL`, the lesser of `clientTtl` and `defaultTtl`
   * is used for the response max-age directive, along with a "public"
   * directive. For cacheable content in `CACHE_ALL_STATIC` mode, `clientTtl`
   * clamps the max-age from the origin (if specified), or else sets the
   * response max-age directive to the lesser of the `clientTtl` and
   * `defaultTtl`, and also ensures a "public" cache-control directive is
   * present. The maximum allowed value is 31,622,400s (1 year). If not
   * specified, Cloud CDN uses 3600s (1 hour) for `CACHE_ALL_STATIC` mode.
   * Cannot exceed `maxTtl`. Cannot be specified when `cacheMode` is
   * `USE_ORIGIN_HEADERS`.
   *
   * @param Duration $clientTtl
   */
  public function setClientTtl(Duration $clientTtl)
  {
    $this->clientTtl = $clientTtl;
  }
  /**
   * @return Duration
   */
  public function getClientTtl()
  {
    return $this->clientTtl;
  }
  /**
   * Specifies the default TTL for cached content for responses that do not have
   * an existing valid TTL (max-age or s-maxage). Setting a TTL of "0" means
   * "always revalidate". The value of `defaultTtl` cannot be set to a value
   * greater than that of `maxTtl`. When the `cacheMode` is set to
   * `FORCE_CACHE_ALL`, the `defaultTtl` will overwrite the TTL set in all
   * responses. The maximum allowed value is 31,622,400s (1 year). Infrequently
   * accessed objects may be evicted from the cache before the defined TTL. If
   * not specified, Cloud CDN uses 3600s (1 hour) for `CACHE_ALL_STATIC` and
   * `FORCE_CACHE_ALL` modes. Cannot be specified when `cacheMode` is
   * `USE_ORIGIN_HEADERS`.
   *
   * @param Duration $defaultTtl
   */
  public function setDefaultTtl(Duration $defaultTtl)
  {
    $this->defaultTtl = $defaultTtl;
  }
  /**
   * @return Duration
   */
  public function getDefaultTtl()
  {
    return $this->defaultTtl;
  }
  /**
   * Specifies the maximum allowed TTL for cached content. Cache directives that
   * attempt to set a max-age or s-maxage higher than this, or an Expires header
   * more than `maxTtl` seconds in the future will be capped at the value of
   * `maxTtl`, as if it were the value of an s-maxage Cache-Control directive.
   * Headers sent to the client will not be modified. Setting a TTL of "0" means
   * "always revalidate". The maximum allowed value is 31,622,400s (1 year).
   * Infrequently accessed objects may be evicted from the cache before the
   * defined TTL. If not specified, Cloud CDN uses 86400s (1 day) for
   * `CACHE_ALL_STATIC` mode. Can be specified only for `CACHE_ALL_STATIC` cache
   * mode.
   *
   * @param Duration $maxTtl
   */
  public function setMaxTtl(Duration $maxTtl)
  {
    $this->maxTtl = $maxTtl;
  }
  /**
   * @return Duration
   */
  public function getMaxTtl()
  {
    return $this->maxTtl;
  }
  /**
   * Negative caching allows per-status code TTLs to be set, in order to apply
   * fine-grained caching for common errors or redirects. This can reduce the
   * load on your origin and improve end-user experience by reducing response
   * latency. When the `cacheMode` is set to `CACHE_ALL_STATIC` or
   * `USE_ORIGIN_HEADERS`, negative caching applies to responses with the
   * specified response code that lack any Cache-Control, Expires, or Pragma:
   * no-cache directives. When the `cacheMode` is set to `FORCE_CACHE_ALL`,
   * negative caching applies to all responses with the specified response code,
   * and overrides any caching headers. By default, Cloud CDN applies the
   * following TTLs to these HTTP status codes:
   *
   * * 300 (Multiple Choice), 301, 308 (Permanent Redirects): 10m * 404 (Not
   * Found), 410 (Gone), 451 (Unavailable For Legal Reasons): 120s * 405 (Method
   * Not Found), 501 (Not Implemented): 60s
   *
   * These defaults can be overridden in `negativeCachingPolicy`. If not
   * specified, Cloud CDN applies negative caching by default.
   *
   * @param bool $negativeCaching
   */
  public function setNegativeCaching($negativeCaching)
  {
    $this->negativeCaching = $negativeCaching;
  }
  /**
   * @return bool
   */
  public function getNegativeCaching()
  {
    return $this->negativeCaching;
  }
  /**
   * Sets a cache TTL for the specified HTTP status code. `negativeCaching` must
   * be enabled to configure `negativeCachingPolicy`. Omitting the policy and
   * leaving `negativeCaching` enabled will use Cloud CDN's default cache TTLs.
   * Note that when specifying an explicit `negativeCachingPolicy`, you should
   * take care to specify a cache TTL for all response codes that you wish to
   * cache. Cloud CDN will not apply any default negative caching when a policy
   * exists.
   *
   * @param CachePolicyNegativeCachingPolicy[] $negativeCachingPolicy
   */
  public function setNegativeCachingPolicy($negativeCachingPolicy)
  {
    $this->negativeCachingPolicy = $negativeCachingPolicy;
  }
  /**
   * @return CachePolicyNegativeCachingPolicy[]
   */
  public function getNegativeCachingPolicy()
  {
    return $this->negativeCachingPolicy;
  }
  /**
   * If true then Cloud CDN will combine multiple concurrent cache fill requests
   * into a small number of requests to the origin. If not specified, Cloud CDN
   * applies request coalescing by default.
   *
   * @param bool $requestCoalescing
   */
  public function setRequestCoalescing($requestCoalescing)
  {
    $this->requestCoalescing = $requestCoalescing;
  }
  /**
   * @return bool
   */
  public function getRequestCoalescing()
  {
    return $this->requestCoalescing;
  }
  /**
   * Serve existing content from the cache (if available) when revalidating
   * content with the origin, or when an error is encountered when refreshing
   * the cache. This setting defines the default "max-stale" duration for any
   * cached responses that do not specify a max-stale directive. Stale responses
   * that exceed the TTL configured here will not be served. The default limit
   * (max-stale) is 86400s (1 day), which will allow stale content to be served
   * up to this limit beyond the max-age (or s-maxage) of a cached response. The
   * maximum allowed value is 604800 (1 week). Set this to zero (0) to disable
   * serve-while-stale.
   *
   * @param Duration $serveWhileStale
   */
  public function setServeWhileStale(Duration $serveWhileStale)
  {
    $this->serveWhileStale = $serveWhileStale;
  }
  /**
   * @return Duration
   */
  public function getServeWhileStale()
  {
    return $this->serveWhileStale;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CachePolicy::class, 'Google_Service_Compute_CachePolicy');
