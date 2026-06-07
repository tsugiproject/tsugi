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

class GenerateQueryRequest extends \Google\Collection
{
  protected $collection_key = 'schemas';
  /**
   * Required. The natural language description of the desired query. Example:
   * "Find all users who signed up in the last 7 days."
   *
   * @var string
   */
  public $prompt;
  protected $schemasType = Schema::class;
  protected $schemasDataType = 'array';

  /**
   * Required. The natural language description of the desired query. Example:
   * "Find all users who signed up in the last 7 days."
   *
   * @param string $prompt
   */
  public function setPrompt($prompt)
  {
    $this->prompt = $prompt;
  }
  /**
   * @return string
   */
  public function getPrompt()
  {
    return $this->prompt;
  }
  /**
   * Optional. The user's locally defined FDC Schema(s). If not defined, the
   * backend will fetch the user's deployed schema.
   *
   * @param Schema[] $schemas
   */
  public function setSchemas($schemas)
  {
    $this->schemas = $schemas;
  }
  /**
   * @return Schema[]
   */
  public function getSchemas()
  {
    return $this->schemas;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GenerateQueryRequest::class, 'Google_Service_FirebaseDataConnect_GenerateQueryRequest');
