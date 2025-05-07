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

namespace Google\Service\Document;

class GoogleCloudDocumentaiV1ProcessorVersionGenAiModelInfoFoundationGenAiModelInfo extends \Google\Model
{
  /**
   * @var bool
   */
  public $finetuningAllowed;
  /**
   * @var int
   */
  public $minTrainLabeledDocuments;

  /**
   * @param bool
   */
  public function setFinetuningAllowed($finetuningAllowed)
  {
    $this->finetuningAllowed = $finetuningAllowed;
  }
  /**
   * @return bool
   */
  public function getFinetuningAllowed()
  {
    return $this->finetuningAllowed;
  }
  /**
   * @param int
   */
  public function setMinTrainLabeledDocuments($minTrainLabeledDocuments)
  {
    $this->minTrainLabeledDocuments = $minTrainLabeledDocuments;
  }
  /**
   * @return int
   */
  public function getMinTrainLabeledDocuments()
  {
    return $this->minTrainLabeledDocuments;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDocumentaiV1ProcessorVersionGenAiModelInfoFoundationGenAiModelInfo::class, 'Google_Service_Document_GoogleCloudDocumentaiV1ProcessorVersionGenAiModelInfoFoundationGenAiModelInfo');
