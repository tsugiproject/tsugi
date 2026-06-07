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

class GoogleCloudAiplatformV1RagManagedDbConfig extends \Google\Model
{
  protected $basicType = GoogleCloudAiplatformV1RagManagedDbConfigBasic::class;
  protected $basicDataType = '';
  protected $scaledType = GoogleCloudAiplatformV1RagManagedDbConfigScaled::class;
  protected $scaledDataType = '';
  protected $serverlessType = GoogleCloudAiplatformV1RagManagedDbConfigServerless::class;
  protected $serverlessDataType = '';
  protected $spannerType = GoogleCloudAiplatformV1RagManagedDbConfigSpanner::class;
  protected $spannerDataType = '';
  protected $unprovisionedType = GoogleCloudAiplatformV1RagManagedDbConfigUnprovisioned::class;
  protected $unprovisionedDataType = '';

  /**
   * Deprecated: Use `mode` instead to set the tier under Spanner. Sets the
   * RagManagedDb to the Basic tier.
   *
   * @deprecated
   * @param GoogleCloudAiplatformV1RagManagedDbConfigBasic $basic
   */
  public function setBasic(GoogleCloudAiplatformV1RagManagedDbConfigBasic $basic)
  {
    $this->basic = $basic;
  }
  /**
   * @deprecated
   * @return GoogleCloudAiplatformV1RagManagedDbConfigBasic
   */
  public function getBasic()
  {
    return $this->basic;
  }
  /**
   * Deprecated: Use `mode` instead to set the tier under Spanner. Sets the
   * RagManagedDb to the Scaled tier.
   *
   * @deprecated
   * @param GoogleCloudAiplatformV1RagManagedDbConfigScaled $scaled
   */
  public function setScaled(GoogleCloudAiplatformV1RagManagedDbConfigScaled $scaled)
  {
    $this->scaled = $scaled;
  }
  /**
   * @deprecated
   * @return GoogleCloudAiplatformV1RagManagedDbConfigScaled
   */
  public function getScaled()
  {
    return $this->scaled;
  }
  /**
   * Sets the backend to be the serverless mode offered by RAG Engine.
   *
   * @param GoogleCloudAiplatformV1RagManagedDbConfigServerless $serverless
   */
  public function setServerless(GoogleCloudAiplatformV1RagManagedDbConfigServerless $serverless)
  {
    $this->serverless = $serverless;
  }
  /**
   * @return GoogleCloudAiplatformV1RagManagedDbConfigServerless
   */
  public function getServerless()
  {
    return $this->serverless;
  }
  /**
   * Sets the RAG Engine backend to be RagManagedDb, built on top of Spanner.
   * NOTE: This is the default mode (w/ Basic Tier) if not explicitly chosen.
   *
   * @param GoogleCloudAiplatformV1RagManagedDbConfigSpanner $spanner
   */
  public function setSpanner(GoogleCloudAiplatformV1RagManagedDbConfigSpanner $spanner)
  {
    $this->spanner = $spanner;
  }
  /**
   * @return GoogleCloudAiplatformV1RagManagedDbConfigSpanner
   */
  public function getSpanner()
  {
    return $this->spanner;
  }
  /**
   * Deprecated: Use `mode` instead to set the tier under Spanner. Sets the
   * RagManagedDb to the Unprovisioned tier.
   *
   * @deprecated
   * @param GoogleCloudAiplatformV1RagManagedDbConfigUnprovisioned $unprovisioned
   */
  public function setUnprovisioned(GoogleCloudAiplatformV1RagManagedDbConfigUnprovisioned $unprovisioned)
  {
    $this->unprovisioned = $unprovisioned;
  }
  /**
   * @deprecated
   * @return GoogleCloudAiplatformV1RagManagedDbConfigUnprovisioned
   */
  public function getUnprovisioned()
  {
    return $this->unprovisioned;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1RagManagedDbConfig::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1RagManagedDbConfig');
