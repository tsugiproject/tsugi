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

class ExecuteMutationResponse extends \Google\Collection
{
  protected $collection_key = 'errors';
  /**
   * @var array[]
   */
  public $data;
  protected $errorsType = GraphqlError::class;
  protected $errorsDataType = 'array';

  /**
   * @param array[]
   */
  public function setData($data)
  {
    $this->data = $data;
  }
  /**
   * @return array[]
   */
  public function getData()
  {
    return $this->data;
  }
  /**
   * @param GraphqlError[]
   */
  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  /**
   * @return GraphqlError[]
   */
  public function getErrors()
  {
    return $this->errors;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ExecuteMutationResponse::class, 'Google_Service_FirebaseDataConnect_ExecuteMutationResponse');
