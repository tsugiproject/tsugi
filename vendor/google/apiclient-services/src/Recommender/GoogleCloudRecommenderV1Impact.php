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

namespace Google\Service\Recommender;

class GoogleCloudRecommenderV1Impact extends \Google\Collection
{
  protected $collection_key = 'impactComponents';
  /**
   * @var string
   */
  public $category;
  protected $costProjectionType = GoogleCloudRecommenderV1CostProjection::class;
  protected $costProjectionDataType = '';
  protected $impactComponentsType = GoogleCloudRecommenderV1Impact::class;
  protected $impactComponentsDataType = 'array';
  protected $reliabilityProjectionType = GoogleCloudRecommenderV1ReliabilityProjection::class;
  protected $reliabilityProjectionDataType = '';
  protected $securityProjectionType = GoogleCloudRecommenderV1SecurityProjection::class;
  protected $securityProjectionDataType = '';
  /**
   * @var string
   */
  public $service;
  protected $sustainabilityProjectionType = GoogleCloudRecommenderV1SustainabilityProjection::class;
  protected $sustainabilityProjectionDataType = '';

  /**
   * @param string
   */
  public function setCategory($category)
  {
    $this->category = $category;
  }
  /**
   * @return string
   */
  public function getCategory()
  {
    return $this->category;
  }
  /**
   * @param GoogleCloudRecommenderV1CostProjection
   */
  public function setCostProjection(GoogleCloudRecommenderV1CostProjection $costProjection)
  {
    $this->costProjection = $costProjection;
  }
  /**
   * @return GoogleCloudRecommenderV1CostProjection
   */
  public function getCostProjection()
  {
    return $this->costProjection;
  }
  /**
   * @param GoogleCloudRecommenderV1Impact[]
   */
  public function setImpactComponents($impactComponents)
  {
    $this->impactComponents = $impactComponents;
  }
  /**
   * @return GoogleCloudRecommenderV1Impact[]
   */
  public function getImpactComponents()
  {
    return $this->impactComponents;
  }
  /**
   * @param GoogleCloudRecommenderV1ReliabilityProjection
   */
  public function setReliabilityProjection(GoogleCloudRecommenderV1ReliabilityProjection $reliabilityProjection)
  {
    $this->reliabilityProjection = $reliabilityProjection;
  }
  /**
   * @return GoogleCloudRecommenderV1ReliabilityProjection
   */
  public function getReliabilityProjection()
  {
    return $this->reliabilityProjection;
  }
  /**
   * @param GoogleCloudRecommenderV1SecurityProjection
   */
  public function setSecurityProjection(GoogleCloudRecommenderV1SecurityProjection $securityProjection)
  {
    $this->securityProjection = $securityProjection;
  }
  /**
   * @return GoogleCloudRecommenderV1SecurityProjection
   */
  public function getSecurityProjection()
  {
    return $this->securityProjection;
  }
  /**
   * @param string
   */
  public function setService($service)
  {
    $this->service = $service;
  }
  /**
   * @return string
   */
  public function getService()
  {
    return $this->service;
  }
  /**
   * @param GoogleCloudRecommenderV1SustainabilityProjection
   */
  public function setSustainabilityProjection(GoogleCloudRecommenderV1SustainabilityProjection $sustainabilityProjection)
  {
    $this->sustainabilityProjection = $sustainabilityProjection;
  }
  /**
   * @return GoogleCloudRecommenderV1SustainabilityProjection
   */
  public function getSustainabilityProjection()
  {
    return $this->sustainabilityProjection;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRecommenderV1Impact::class, 'Google_Service_Recommender_GoogleCloudRecommenderV1Impact');
