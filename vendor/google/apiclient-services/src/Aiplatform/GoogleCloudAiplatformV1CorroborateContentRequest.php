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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1CorroborateContentRequest extends \Google\Collection
{
  protected $collection_key = 'facts';
  protected $contentType = GoogleCloudAiplatformV1Content::class;
  protected $contentDataType = '';
  protected $factsType = GoogleCloudAiplatformV1Fact::class;
  protected $factsDataType = 'array';
  protected $parametersType = GoogleCloudAiplatformV1CorroborateContentRequestParameters::class;
  protected $parametersDataType = '';

  /**
   * @param GoogleCloudAiplatformV1Content
   */
  public function setContent(GoogleCloudAiplatformV1Content $content)
  {
    $this->content = $content;
  }
  /**
   * @return GoogleCloudAiplatformV1Content
   */
  public function getContent()
  {
    return $this->content;
  }
  /**
   * @param GoogleCloudAiplatformV1Fact[]
   */
  public function setFacts($facts)
  {
    $this->facts = $facts;
  }
  /**
   * @return GoogleCloudAiplatformV1Fact[]
   */
  public function getFacts()
  {
    return $this->facts;
  }
  /**
   * @param GoogleCloudAiplatformV1CorroborateContentRequestParameters
   */
  public function setParameters(GoogleCloudAiplatformV1CorroborateContentRequestParameters $parameters)
  {
    $this->parameters = $parameters;
  }
  /**
   * @return GoogleCloudAiplatformV1CorroborateContentRequestParameters
   */
  public function getParameters()
  {
    return $this->parameters;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1CorroborateContentRequest::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1CorroborateContentRequest');
