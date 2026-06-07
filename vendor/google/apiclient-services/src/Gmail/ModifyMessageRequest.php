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

namespace Google\Service\Gmail;

class ModifyMessageRequest extends \Google\Collection
{
  protected $collection_key = 'removeLabelIds';
  protected $addClassificationLabelsType = ClassificationLabelValue::class;
  protected $addClassificationLabelsDataType = 'array';
  /**
   * A list of IDs of labels to add to this message. You can add up to 100
   * labels with each update.
   *
   * @var string[]
   */
  public $addLabelIds;
  /**
   * A list of Classification Label values to remove from this message.
   *
   * @var string[]
   */
  public $removeClassificationLabelIds;
  /**
   * A list IDs of labels to remove from this message. You can remove up to 100
   * labels with each update.
   *
   * @var string[]
   */
  public $removeLabelIds;

  /**
   * A list of classification label values to add. If a Classification Label
   * with the same label ID is already applied to the message, fields with
   * existing field IDs will be updated and fields with new field IDs will be
   * added. There's a limit of 20 Classification Label values per request. If
   * the message is already classified and the final total number of
   * Classification Label values exceeds the maximum allowed number of
   * Classification Label values per message, the modification fails.
   *
   * @param ClassificationLabelValue[] $addClassificationLabels
   */
  public function setAddClassificationLabels($addClassificationLabels)
  {
    $this->addClassificationLabels = $addClassificationLabels;
  }
  /**
   * @return ClassificationLabelValue[]
   */
  public function getAddClassificationLabels()
  {
    return $this->addClassificationLabels;
  }
  /**
   * A list of IDs of labels to add to this message. You can add up to 100
   * labels with each update.
   *
   * @param string[] $addLabelIds
   */
  public function setAddLabelIds($addLabelIds)
  {
    $this->addLabelIds = $addLabelIds;
  }
  /**
   * @return string[]
   */
  public function getAddLabelIds()
  {
    return $this->addLabelIds;
  }
  /**
   * A list of Classification Label values to remove from this message.
   *
   * @param string[] $removeClassificationLabelIds
   */
  public function setRemoveClassificationLabelIds($removeClassificationLabelIds)
  {
    $this->removeClassificationLabelIds = $removeClassificationLabelIds;
  }
  /**
   * @return string[]
   */
  public function getRemoveClassificationLabelIds()
  {
    return $this->removeClassificationLabelIds;
  }
  /**
   * A list IDs of labels to remove from this message. You can remove up to 100
   * labels with each update.
   *
   * @param string[] $removeLabelIds
   */
  public function setRemoveLabelIds($removeLabelIds)
  {
    $this->removeLabelIds = $removeLabelIds;
  }
  /**
   * @return string[]
   */
  public function getRemoveLabelIds()
  {
    return $this->removeLabelIds;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ModifyMessageRequest::class, 'Google_Service_Gmail_ModifyMessageRequest');
