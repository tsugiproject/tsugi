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

namespace Google\Service\GoogleHealthAPI;

class HttpResponse extends \Google\Collection
{
  protected $collection_key = 'headers';
  /**
   * The HTTP response body. If the body is not expected, it should be empty.
   *
   * @var string
   */
  public $body;
  protected $headersType = HttpHeader::class;
  protected $headersDataType = 'array';
  /**
   * The HTTP reason phrase, such as "OK" or "Not Found".
   *
   * @var string
   */
  public $reason;
  /**
   * The HTTP status code, such as 200 or 404.
   *
   * @var int
   */
  public $status;

  /**
   * The HTTP response body. If the body is not expected, it should be empty.
   *
   * @param string $body
   */
  public function setBody($body)
  {
    $this->body = $body;
  }
  /**
   * @return string
   */
  public function getBody()
  {
    return $this->body;
  }
  /**
   * The HTTP response headers. The ordering of the headers is significant.
   * Multiple headers with the same key may present for the response.
   *
   * @param HttpHeader[] $headers
   */
  public function setHeaders($headers)
  {
    $this->headers = $headers;
  }
  /**
   * @return HttpHeader[]
   */
  public function getHeaders()
  {
    return $this->headers;
  }
  /**
   * The HTTP reason phrase, such as "OK" or "Not Found".
   *
   * @param string $reason
   */
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  /**
   * @return string
   */
  public function getReason()
  {
    return $this->reason;
  }
  /**
   * The HTTP status code, such as 200 or 404.
   *
   * @param int $status
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }
  /**
   * @return int
   */
  public function getStatus()
  {
    return $this->status;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HttpResponse::class, 'Google_Service_GoogleHealthAPI_HttpResponse');
