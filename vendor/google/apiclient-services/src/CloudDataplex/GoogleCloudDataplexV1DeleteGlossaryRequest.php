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

namespace Google\Service\CloudDataplex;

class GoogleCloudDataplexV1DeleteGlossaryRequest extends \Google\Model
{
  /**
   * Optional. The etag of the Glossary. If this is provided, it must match the
   * server's etag. If the etag is provided and does not match the server-
   * computed etag, the request must fail with a ABORTED error code.
   *
   * @var string
   */
  public $etag;
  /**
   * Required. The name of the Glossary to delete. Format: projects/{project_id_
   * or_number}/locations/{location_id}/glossaries/{glossary_id}
   *
   * @var string
   */
  public $name;

  /**
   * Optional. The etag of the Glossary. If this is provided, it must match the
   * server's etag. If the etag is provided and does not match the server-
   * computed etag, the request must fail with a ABORTED error code.
   *
   * @param string $etag
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * Required. The name of the Glossary to delete. Format: projects/{project_id_
   * or_number}/locations/{location_id}/glossaries/{glossary_id}
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1DeleteGlossaryRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DeleteGlossaryRequest');
