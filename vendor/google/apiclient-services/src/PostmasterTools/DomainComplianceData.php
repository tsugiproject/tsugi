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

namespace Google\Service\PostmasterTools;

class DomainComplianceData extends \Google\Collection
{
  protected $collection_key = 'rowData';
  /**
   * Domain that this data is for.
   *
   * @var string
   */
  public $domainId;
  protected $honorUnsubscribeVerdictType = HonorUnsubscribeVerdict::class;
  protected $honorUnsubscribeVerdictDataType = '';
  protected $oneClickUnsubscribeVerdictType = OneClickUnsubscribeVerdict::class;
  protected $oneClickUnsubscribeVerdictDataType = '';
  protected $rowDataType = ComplianceRowData::class;
  protected $rowDataDataType = 'array';

  /**
   * Domain that this data is for.
   *
   * @param string $domainId
   */
  public function setDomainId($domainId)
  {
    $this->domainId = $domainId;
  }
  /**
   * @return string
   */
  public function getDomainId()
  {
    return $this->domainId;
  }
  /**
   * Unsubscribe honoring compliance verdict.
   *
   * @param HonorUnsubscribeVerdict $honorUnsubscribeVerdict
   */
  public function setHonorUnsubscribeVerdict(HonorUnsubscribeVerdict $honorUnsubscribeVerdict)
  {
    $this->honorUnsubscribeVerdict = $honorUnsubscribeVerdict;
  }
  /**
   * @return HonorUnsubscribeVerdict
   */
  public function getHonorUnsubscribeVerdict()
  {
    return $this->honorUnsubscribeVerdict;
  }
  /**
   * One-click unsubscribe compliance verdict.
   *
   * @param OneClickUnsubscribeVerdict $oneClickUnsubscribeVerdict
   */
  public function setOneClickUnsubscribeVerdict(OneClickUnsubscribeVerdict $oneClickUnsubscribeVerdict)
  {
    $this->oneClickUnsubscribeVerdict = $oneClickUnsubscribeVerdict;
  }
  /**
   * @return OneClickUnsubscribeVerdict
   */
  public function getOneClickUnsubscribeVerdict()
  {
    return $this->oneClickUnsubscribeVerdict;
  }
  /**
   * Data for each of the rows of the table. Each message contains all the data
   * that backs a single row.
   *
   * @param ComplianceRowData[] $rowData
   */
  public function setRowData($rowData)
  {
    $this->rowData = $rowData;
  }
  /**
   * @return ComplianceRowData[]
   */
  public function getRowData()
  {
    return $this->rowData;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DomainComplianceData::class, 'Google_Service_PostmasterTools_DomainComplianceData');
