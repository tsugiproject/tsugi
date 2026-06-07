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

namespace Google\Service\Container;

class InitScript extends \Google\Collection
{
  protected $collection_key = 'args';
  /**
   * Optional. The optional arguments line to be passed to the init script.
   *
   * @var string[]
   */
  public $args;
  /**
   * The resource name of the secret manager secret hosting the init script.
   * Both global and regional secrets are supported with format below: Global
   * secret: projects/{project}/secrets/{secret}/versions/{version} Regional
   * secret:
   * projects/{project}/locations/{location}/secrets/{secret}/versions/{version}
   * Example: projects/1234567890/secrets/script_1/versions/1. Accept version
   * number only, not support version alias. User can't configure both
   * gcp_secret_manager_secret_uri and gcs_uri.
   *
   * @var string
   */
  public $gcpSecretManagerSecretUri;
  /**
   * The generation of the init script stored in Gloud Storage. This is the
   * required field to identify the version of the init script. User can get the
   * genetaion from `gcloud storage objects describe
   * gs://BUCKET_NAME/OBJECT_NAME --format="value(generation)"` or from the
   * "Version history" tab of the object in the Cloud Console UI.
   *
   * @var string
   */
  public $gcsGeneration;
  /**
   * The Cloud Storage URI for storing the init script. Format:
   * gs://BUCKET_NAME/OBJECT_NAME The service account on the node pool must have
   * read access to the object. User can't configure both gcs_uri and
   * gcp_secret_manager_secret_uri.
   *
   * @var string
   */
  public $gcsUri;

  /**
   * Optional. The optional arguments line to be passed to the init script.
   *
   * @param string[] $args
   */
  public function setArgs($args)
  {
    $this->args = $args;
  }
  /**
   * @return string[]
   */
  public function getArgs()
  {
    return $this->args;
  }
  /**
   * The resource name of the secret manager secret hosting the init script.
   * Both global and regional secrets are supported with format below: Global
   * secret: projects/{project}/secrets/{secret}/versions/{version} Regional
   * secret:
   * projects/{project}/locations/{location}/secrets/{secret}/versions/{version}
   * Example: projects/1234567890/secrets/script_1/versions/1. Accept version
   * number only, not support version alias. User can't configure both
   * gcp_secret_manager_secret_uri and gcs_uri.
   *
   * @param string $gcpSecretManagerSecretUri
   */
  public function setGcpSecretManagerSecretUri($gcpSecretManagerSecretUri)
  {
    $this->gcpSecretManagerSecretUri = $gcpSecretManagerSecretUri;
  }
  /**
   * @return string
   */
  public function getGcpSecretManagerSecretUri()
  {
    return $this->gcpSecretManagerSecretUri;
  }
  /**
   * The generation of the init script stored in Gloud Storage. This is the
   * required field to identify the version of the init script. User can get the
   * genetaion from `gcloud storage objects describe
   * gs://BUCKET_NAME/OBJECT_NAME --format="value(generation)"` or from the
   * "Version history" tab of the object in the Cloud Console UI.
   *
   * @param string $gcsGeneration
   */
  public function setGcsGeneration($gcsGeneration)
  {
    $this->gcsGeneration = $gcsGeneration;
  }
  /**
   * @return string
   */
  public function getGcsGeneration()
  {
    return $this->gcsGeneration;
  }
  /**
   * The Cloud Storage URI for storing the init script. Format:
   * gs://BUCKET_NAME/OBJECT_NAME The service account on the node pool must have
   * read access to the object. User can't configure both gcs_uri and
   * gcp_secret_manager_secret_uri.
   *
   * @param string $gcsUri
   */
  public function setGcsUri($gcsUri)
  {
    $this->gcsUri = $gcsUri;
  }
  /**
   * @return string
   */
  public function getGcsUri()
  {
    return $this->gcsUri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(InitScript::class, 'Google_Service_Container_InitScript');
