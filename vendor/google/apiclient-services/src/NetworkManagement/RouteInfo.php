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

namespace Google\Service\NetworkManagement;

class RouteInfo extends \Google\Collection
{
  protected $collection_key = 'srcPortRanges';
  /**
   * @var string
   */
  public $advertisedRouteNextHopUri;
  /**
   * @var string
   */
  public $advertisedRouteSourceRouterUri;
  /**
   * @var string
   */
  public $destIpRange;
  /**
   * @var string[]
   */
  public $destPortRanges;
  /**
   * @var string
   */
  public $displayName;
  /**
   * @var string[]
   */
  public $instanceTags;
  /**
   * @var string
   */
  public $nccHubRouteUri;
  /**
   * @var string
   */
  public $nccHubUri;
  /**
   * @var string
   */
  public $nccSpokeUri;
  /**
   * @var string
   */
  public $networkUri;
  /**
   * @var string
   */
  public $nextHop;
  /**
   * @var string
   */
  public $nextHopNetworkUri;
  /**
   * @var string
   */
  public $nextHopType;
  /**
   * @var string
   */
  public $nextHopUri;
  /**
   * @var string
   */
  public $originatingRouteDisplayName;
  /**
   * @var string
   */
  public $originatingRouteUri;
  /**
   * @var int
   */
  public $priority;
  /**
   * @var string[]
   */
  public $protocols;
  /**
   * @var string
   */
  public $region;
  /**
   * @var string
   */
  public $routeScope;
  /**
   * @var string
   */
  public $routeType;
  /**
   * @var string
   */
  public $srcIpRange;
  /**
   * @var string[]
   */
  public $srcPortRanges;
  /**
   * @var string
   */
  public $uri;

  /**
   * @param string
   */
  public function setAdvertisedRouteNextHopUri($advertisedRouteNextHopUri)
  {
    $this->advertisedRouteNextHopUri = $advertisedRouteNextHopUri;
  }
  /**
   * @return string
   */
  public function getAdvertisedRouteNextHopUri()
  {
    return $this->advertisedRouteNextHopUri;
  }
  /**
   * @param string
   */
  public function setAdvertisedRouteSourceRouterUri($advertisedRouteSourceRouterUri)
  {
    $this->advertisedRouteSourceRouterUri = $advertisedRouteSourceRouterUri;
  }
  /**
   * @return string
   */
  public function getAdvertisedRouteSourceRouterUri()
  {
    return $this->advertisedRouteSourceRouterUri;
  }
  /**
   * @param string
   */
  public function setDestIpRange($destIpRange)
  {
    $this->destIpRange = $destIpRange;
  }
  /**
   * @return string
   */
  public function getDestIpRange()
  {
    return $this->destIpRange;
  }
  /**
   * @param string[]
   */
  public function setDestPortRanges($destPortRanges)
  {
    $this->destPortRanges = $destPortRanges;
  }
  /**
   * @return string[]
   */
  public function getDestPortRanges()
  {
    return $this->destPortRanges;
  }
  /**
   * @param string
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * @param string[]
   */
  public function setInstanceTags($instanceTags)
  {
    $this->instanceTags = $instanceTags;
  }
  /**
   * @return string[]
   */
  public function getInstanceTags()
  {
    return $this->instanceTags;
  }
  /**
   * @param string
   */
  public function setNccHubRouteUri($nccHubRouteUri)
  {
    $this->nccHubRouteUri = $nccHubRouteUri;
  }
  /**
   * @return string
   */
  public function getNccHubRouteUri()
  {
    return $this->nccHubRouteUri;
  }
  /**
   * @param string
   */
  public function setNccHubUri($nccHubUri)
  {
    $this->nccHubUri = $nccHubUri;
  }
  /**
   * @return string
   */
  public function getNccHubUri()
  {
    return $this->nccHubUri;
  }
  /**
   * @param string
   */
  public function setNccSpokeUri($nccSpokeUri)
  {
    $this->nccSpokeUri = $nccSpokeUri;
  }
  /**
   * @return string
   */
  public function getNccSpokeUri()
  {
    return $this->nccSpokeUri;
  }
  /**
   * @param string
   */
  public function setNetworkUri($networkUri)
  {
    $this->networkUri = $networkUri;
  }
  /**
   * @return string
   */
  public function getNetworkUri()
  {
    return $this->networkUri;
  }
  /**
   * @param string
   */
  public function setNextHop($nextHop)
  {
    $this->nextHop = $nextHop;
  }
  /**
   * @return string
   */
  public function getNextHop()
  {
    return $this->nextHop;
  }
  /**
   * @param string
   */
  public function setNextHopNetworkUri($nextHopNetworkUri)
  {
    $this->nextHopNetworkUri = $nextHopNetworkUri;
  }
  /**
   * @return string
   */
  public function getNextHopNetworkUri()
  {
    return $this->nextHopNetworkUri;
  }
  /**
   * @param string
   */
  public function setNextHopType($nextHopType)
  {
    $this->nextHopType = $nextHopType;
  }
  /**
   * @return string
   */
  public function getNextHopType()
  {
    return $this->nextHopType;
  }
  /**
   * @param string
   */
  public function setNextHopUri($nextHopUri)
  {
    $this->nextHopUri = $nextHopUri;
  }
  /**
   * @return string
   */
  public function getNextHopUri()
  {
    return $this->nextHopUri;
  }
  /**
   * @param string
   */
  public function setOriginatingRouteDisplayName($originatingRouteDisplayName)
  {
    $this->originatingRouteDisplayName = $originatingRouteDisplayName;
  }
  /**
   * @return string
   */
  public function getOriginatingRouteDisplayName()
  {
    return $this->originatingRouteDisplayName;
  }
  /**
   * @param string
   */
  public function setOriginatingRouteUri($originatingRouteUri)
  {
    $this->originatingRouteUri = $originatingRouteUri;
  }
  /**
   * @return string
   */
  public function getOriginatingRouteUri()
  {
    return $this->originatingRouteUri;
  }
  /**
   * @param int
   */
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  /**
   * @return int
   */
  public function getPriority()
  {
    return $this->priority;
  }
  /**
   * @param string[]
   */
  public function setProtocols($protocols)
  {
    $this->protocols = $protocols;
  }
  /**
   * @return string[]
   */
  public function getProtocols()
  {
    return $this->protocols;
  }
  /**
   * @param string
   */
  public function setRegion($region)
  {
    $this->region = $region;
  }
  /**
   * @return string
   */
  public function getRegion()
  {
    return $this->region;
  }
  /**
   * @param string
   */
  public function setRouteScope($routeScope)
  {
    $this->routeScope = $routeScope;
  }
  /**
   * @return string
   */
  public function getRouteScope()
  {
    return $this->routeScope;
  }
  /**
   * @param string
   */
  public function setRouteType($routeType)
  {
    $this->routeType = $routeType;
  }
  /**
   * @return string
   */
  public function getRouteType()
  {
    return $this->routeType;
  }
  /**
   * @param string
   */
  public function setSrcIpRange($srcIpRange)
  {
    $this->srcIpRange = $srcIpRange;
  }
  /**
   * @return string
   */
  public function getSrcIpRange()
  {
    return $this->srcIpRange;
  }
  /**
   * @param string[]
   */
  public function setSrcPortRanges($srcPortRanges)
  {
    $this->srcPortRanges = $srcPortRanges;
  }
  /**
   * @return string[]
   */
  public function getSrcPortRanges()
  {
    return $this->srcPortRanges;
  }
  /**
   * @param string
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RouteInfo::class, 'Google_Service_NetworkManagement_RouteInfo');
