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

class GenerateQueryResponse extends \Google\Model
{
  protected $partType = Part::class;
  protected $partDataType = '';
  protected $statusType = GenerationStatus::class;
  protected $statusDataType = '';

  /**
   * Required. The content from the current conversational turn.
   *
   * @param Part $part
   */
  public function setPart(Part $part)
  {
    $this->part = $part;
  }
  /**
   * @return Part
   */
  public function getPart()
  {
    return $this->part;
  }
  /**
   * Essential for providing responsive UI feedback (e.g., a spinner or
   * "Analyzing schema..." step).
   *
   * @param GenerationStatus $status
   */
  public function setStatus(GenerationStatus $status)
  {
    $this->status = $status;
  }
  /**
   * @return GenerationStatus
   */
  public function getStatus()
  {
    return $this->status;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GenerateQueryResponse::class, 'Google_Service_FirebaseDataConnect_GenerateQueryResponse');
