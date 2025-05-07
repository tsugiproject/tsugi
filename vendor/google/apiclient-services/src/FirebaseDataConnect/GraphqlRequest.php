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

class GraphqlRequest extends \Google\Model
{
  protected $extensionsType = GraphqlRequestExtensions::class;
  protected $extensionsDataType = '';
  /**
   * @var string
   */
  public $operationName;
  /**
   * @var string
   */
  public $query;
  /**
   * @var array[]
   */
  public $variables;

  /**
   * @param GraphqlRequestExtensions
   */
  public function setExtensions(GraphqlRequestExtensions $extensions)
  {
    $this->extensions = $extensions;
  }
  /**
   * @return GraphqlRequestExtensions
   */
  public function getExtensions()
  {
    return $this->extensions;
  }
  /**
   * @param string
   */
  public function setOperationName($operationName)
  {
    $this->operationName = $operationName;
  }
  /**
   * @return string
   */
  public function getOperationName()
  {
    return $this->operationName;
  }
  /**
   * @param string
   */
  public function setQuery($query)
  {
    $this->query = $query;
  }
  /**
   * @return string
   */
  public function getQuery()
  {
    return $this->query;
  }
  /**
   * @param array[]
   */
  public function setVariables($variables)
  {
    $this->variables = $variables;
  }
  /**
   * @return array[]
   */
  public function getVariables()
  {
    return $this->variables;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GraphqlRequest::class, 'Google_Service_FirebaseDataConnect_GraphqlRequest');
