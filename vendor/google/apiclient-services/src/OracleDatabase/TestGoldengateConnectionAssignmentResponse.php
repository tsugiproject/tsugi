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

namespace Google\Service\OracleDatabase;

class TestGoldengateConnectionAssignmentResponse extends \Google\Collection
{
  /**
   * Result type is unspecified.
   */
  public const RESULT_TYPE_RESULT_TYPE_UNSPECIFIED = 'RESULT_TYPE_UNSPECIFIED';
  /**
   * Test connection succeeded.
   */
  public const RESULT_TYPE_SUCCEEDED = 'SUCCEEDED';
  /**
   * Test connection failed.
   */
  public const RESULT_TYPE_FAILED = 'FAILED';
  /**
   * Test connection timed out.
   */
  public const RESULT_TYPE_TIMED_OUT = 'TIMED_OUT';
  protected $collection_key = 'errors';
  protected $errorType = TestConnectionAssignmentError::class;
  protected $errorDataType = '';
  protected $errorsType = TestConnectionAssignmentError::class;
  protected $errorsDataType = 'array';
  /**
   * Type of the result i.e. Success, Failure or Timeout.
   *
   * @var string
   */
  public $resultType;

  /**
   * Error details if test connection failed.
   *
   * @param TestConnectionAssignmentError $error
   */
  public function setError(TestConnectionAssignmentError $error)
  {
    $this->error = $error;
  }
  /**
   * @return TestConnectionAssignmentError
   */
  public function getError()
  {
    return $this->error;
  }
  /**
   * List of test connection assignment error objects.
   *
   * @param TestConnectionAssignmentError[] $errors
   */
  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  /**
   * @return TestConnectionAssignmentError[]
   */
  public function getErrors()
  {
    return $this->errors;
  }
  /**
   * Type of the result i.e. Success, Failure or Timeout.
   *
   * Accepted values: RESULT_TYPE_UNSPECIFIED, SUCCEEDED, FAILED, TIMED_OUT
   *
   * @param self::RESULT_TYPE_* $resultType
   */
  public function setResultType($resultType)
  {
    $this->resultType = $resultType;
  }
  /**
   * @return self::RESULT_TYPE_*
   */
  public function getResultType()
  {
    return $this->resultType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TestGoldengateConnectionAssignmentResponse::class, 'Google_Service_OracleDatabase_TestGoldengateConnectionAssignmentResponse');
