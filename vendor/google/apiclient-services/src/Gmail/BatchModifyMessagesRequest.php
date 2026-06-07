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

class BatchModifyMessagesRequest extends \Google\Collection
{
  protected $collection_key = 'removeLabelIds';
  protected $addClassificationLabelsType = ClassificationLabelValue::class;
  protected $addClassificationLabelsDataType = 'array';
  /**
   * A list of label IDs to add to messages.
   *
   * @var string[]
   */
  public $addLabelIds;
  /**
   * The IDs of the messages to modify. There is a limit of 1000 ids per
   * request.
   *
   * @var string[]
   */
  public $ids;
  /**
   * A list of Classification Label values to remove from messages.
   *
   * @var string[]
   */
  public $removeClassificationLabelIds;
  /**
   * A list of label IDs to remove from messages.
   *
   * @var string[]
   */
  public $removeLabelIds;

  /**
   * A list of Classification Label values to add. If a Classification Label
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
   * A list of label IDs to add to messages.
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
   * The IDs of the messages to modify. There is a limit of 1000 ids per
   * request.
   *
   * @param string[] $ids
   */
  public function setIds($ids)
  {
    $this->ids = $ids;
  }
  /**
   * @return string[]
   */
  public function getIds()
  {
    return $this->ids;
  }
  /**
   * A list of Classification Label values to remove from messages.
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
   * A list of label IDs to remove from messages.
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
class_alias(BatchModifyMessagesRequest::class, 'Google_Service_Gmail_BatchModifyMessagesRequest');
