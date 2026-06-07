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

namespace Google\Service\CloudRun;

class GoogleCloudRunV2InlinedSource extends \Google\Collection
{
  protected $collection_key = 'sources';
  protected $sourcesType = GoogleCloudRunV2SourceFile::class;
  protected $sourcesDataType = 'array';

  /**
   * Required. Input only. The source code.
   *
   * @param GoogleCloudRunV2SourceFile[] $sources
   */
  public function setSources($sources)
  {
    $this->sources = $sources;
  }
  /**
   * @return GoogleCloudRunV2SourceFile[]
   */
  public function getSources()
  {
    return $this->sources;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRunV2InlinedSource::class, 'Google_Service_CloudRun_GoogleCloudRunV2InlinedSource');
