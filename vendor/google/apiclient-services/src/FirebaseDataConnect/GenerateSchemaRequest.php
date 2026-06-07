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

class GenerateSchemaRequest extends \Google\Model
{
  /**
   * Required. The natural language description of the data model to generate.
   * Example: "A blog system with Users, Posts, and Comments. Users can have
   * multiple posts."
   *
   * @var string
   */
  public $prompt;

  /**
   * Required. The natural language description of the data model to generate.
   * Example: "A blog system with Users, Posts, and Comments. Users can have
   * multiple posts."
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GenerateSchemaRequest::class, 'Google_Service_FirebaseDataConnect_GenerateSchemaRequest');
