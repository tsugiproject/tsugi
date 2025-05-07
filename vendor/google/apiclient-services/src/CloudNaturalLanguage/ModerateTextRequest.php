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

namespace Google\Service\CloudNaturalLanguage;

class ModerateTextRequest extends \Google\Model
{
  protected $documentType = Document::class;
  protected $documentDataType = '';
  /**
   * @var string
   */
  public $modelVersion;

  /**
   * @param Document
   */
  public function setDocument(Document $document)
  {
    $this->document = $document;
  }
  /**
   * @return Document
   */
  public function getDocument()
  {
    return $this->document;
  }
  /**
   * @param string
   */
  public function setModelVersion($modelVersion)
  {
    $this->modelVersion = $modelVersion;
  }
  /**
   * @return string
   */
  public function getModelVersion()
  {
    return $this->modelVersion;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ModerateTextRequest::class, 'Google_Service_CloudNaturalLanguage_ModerateTextRequest');
