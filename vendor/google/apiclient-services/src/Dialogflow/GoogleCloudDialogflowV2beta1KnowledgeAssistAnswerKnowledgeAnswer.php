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

namespace Google\Service\Dialogflow;

class GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswer extends \Google\Model
{
  /**
   * @var string
   */
  public $answerText;
  protected $eventSourceType = GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerEventSource::class;
  protected $eventSourceDataType = '';
  protected $faqSourceType = GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerFaqSource::class;
  protected $faqSourceDataType = '';
  protected $generativeSourceType = GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerGenerativeSource::class;
  protected $generativeSourceDataType = '';
  protected $playbookSourceType = GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerGenerativeSource::class;
  protected $playbookSourceDataType = '';

  /**
   * @param string $answerText
   */
  public function setAnswerText($answerText)
  {
    $this->answerText = $answerText;
  }
  /**
   * @return string
   */
  public function getAnswerText()
  {
    return $this->answerText;
  }
  /**
   * @param GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerEventSource $eventSource
   */
  public function setEventSource(GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerEventSource $eventSource)
  {
    $this->eventSource = $eventSource;
  }
  /**
   * @return GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerEventSource
   */
  public function getEventSource()
  {
    return $this->eventSource;
  }
  /**
   * @param GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerFaqSource $faqSource
   */
  public function setFaqSource(GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerFaqSource $faqSource)
  {
    $this->faqSource = $faqSource;
  }
  /**
   * @return GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerFaqSource
   */
  public function getFaqSource()
  {
    return $this->faqSource;
  }
  /**
   * @param GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerGenerativeSource $generativeSource
   */
  public function setGenerativeSource(GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerGenerativeSource $generativeSource)
  {
    $this->generativeSource = $generativeSource;
  }
  /**
   * @return GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerGenerativeSource
   */
  public function getGenerativeSource()
  {
    return $this->generativeSource;
  }
  /**
   * @param GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerGenerativeSource $playbookSource
   */
  public function setPlaybookSource(GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerGenerativeSource $playbookSource)
  {
    $this->playbookSource = $playbookSource;
  }
  /**
   * @return GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswerGenerativeSource
   */
  public function getPlaybookSource()
  {
    return $this->playbookSource;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswer::class, 'Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1KnowledgeAssistAnswerKnowledgeAnswer');
