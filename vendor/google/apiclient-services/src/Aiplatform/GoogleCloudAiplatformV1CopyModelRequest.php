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

class GoogleCloudAiplatformV1CopyModelRequest extends \Google\Model
{
  /**
   * Optional. The user-provided custom service account to use to do the copy
   * model. If empty, [Vertex AI Service Agent](https://cloud.google.com/vertex-
   * ai/docs/general/access-control#service-agents) will be used to access
   * resources needed to upload the model. This account must belong to the
   * destination project where the model is copied to, i.e., the project
   * specified in the `parent` field of this request and have the Vertex AI
   * Service Agent role in the source project. Requires the user copying the
   * Model to have the `iam.serviceAccounts.actAs` permission on this service
   * account.
   *
   * @var string
   */
  public $customServiceAccount;
  protected $encryptionSpecType = GoogleCloudAiplatformV1EncryptionSpec::class;
  protected $encryptionSpecDataType = '';
  /**
   * Optional. Copy source_model into a new Model with this ID. The ID will
   * become the final component of the model resource name. This value may be up
   * to 63 characters, and valid characters are `[a-z0-9_-]`. The first
   * character cannot be a number or hyphen.
   *
   * @var string
   */
  public $modelId;
  /**
   * Optional. Specify this field to copy source_model into this existing Model
   * as a new version. Format:
   * `projects/{project}/locations/{location}/models/{model}`
   *
   * @var string
   */
  public $parentModel;
  /**
   * Required. The resource name of the Model to copy. That Model must be in the
   * same Project. Format:
   * `projects/{project}/locations/{location}/models/{model}`
   *
   * @var string
   */
  public $sourceModel;

  /**
   * Optional. The user-provided custom service account to use to do the copy
   * model. If empty, [Vertex AI Service Agent](https://cloud.google.com/vertex-
   * ai/docs/general/access-control#service-agents) will be used to access
   * resources needed to upload the model. This account must belong to the
   * destination project where the model is copied to, i.e., the project
   * specified in the `parent` field of this request and have the Vertex AI
   * Service Agent role in the source project. Requires the user copying the
   * Model to have the `iam.serviceAccounts.actAs` permission on this service
   * account.
   *
   * @param string $customServiceAccount
   */
  public function setCustomServiceAccount($customServiceAccount)
  {
    $this->customServiceAccount = $customServiceAccount;
  }
  /**
   * @return string
   */
  public function getCustomServiceAccount()
  {
    return $this->customServiceAccount;
  }
  /**
   * Customer-managed encryption key options. If this is set, then the Model
   * copy will be encrypted with the provided encryption key.
   *
   * @param GoogleCloudAiplatformV1EncryptionSpec $encryptionSpec
   */
  public function setEncryptionSpec(GoogleCloudAiplatformV1EncryptionSpec $encryptionSpec)
  {
    $this->encryptionSpec = $encryptionSpec;
  }
  /**
   * @return GoogleCloudAiplatformV1EncryptionSpec
   */
  public function getEncryptionSpec()
  {
    return $this->encryptionSpec;
  }
  /**
   * Optional. Copy source_model into a new Model with this ID. The ID will
   * become the final component of the model resource name. This value may be up
   * to 63 characters, and valid characters are `[a-z0-9_-]`. The first
   * character cannot be a number or hyphen.
   *
   * @param string $modelId
   */
  public function setModelId($modelId)
  {
    $this->modelId = $modelId;
  }
  /**
   * @return string
   */
  public function getModelId()
  {
    return $this->modelId;
  }
  /**
   * Optional. Specify this field to copy source_model into this existing Model
   * as a new version. Format:
   * `projects/{project}/locations/{location}/models/{model}`
   *
   * @param string $parentModel
   */
  public function setParentModel($parentModel)
  {
    $this->parentModel = $parentModel;
  }
  /**
   * @return string
   */
  public function getParentModel()
  {
    return $this->parentModel;
  }
  /**
   * Required. The resource name of the Model to copy. That Model must be in the
   * same Project. Format:
   * `projects/{project}/locations/{location}/models/{model}`
   *
   * @param string $sourceModel
   */
  public function setSourceModel($sourceModel)
  {
    $this->sourceModel = $sourceModel;
  }
  /**
   * @return string
   */
  public function getSourceModel()
  {
    return $this->sourceModel;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1CopyModelRequest::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1CopyModelRequest');
