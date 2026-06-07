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

namespace Google\Service\SecurityCommandCenter;

class HttpResponse extends \Google\Model
{
  /**
   * The http path for which response code was returned by web application, for
   * example, "https://test-app.a.run.app/test".
   *
   * @var string
   */
  public $path;
  /**
   * The http response code returned by the web application, for example, 200.
   *
   * @var string
   */
  public $statusCode;

  /**
   * The http path for which response code was returned by web application, for
   * example, "https://test-app.a.run.app/test".
   *
   * @param string $path
   */
  public function setPath($path)
  {
    $this->path = $path;
  }
  /**
   * @return string
   */
  public function getPath()
  {
    return $this->path;
  }
  /**
   * The http response code returned by the web application, for example, 200.
   *
   * @param string $statusCode
   */
  public function setStatusCode($statusCode)
  {
    $this->statusCode = $statusCode;
  }
  /**
   * @return string
   */
  public function getStatusCode()
  {
    return $this->statusCode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HttpResponse::class, 'Google_Service_SecurityCommandCenter_HttpResponse');
