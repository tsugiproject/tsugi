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

namespace Google\Service\CertificateAuthorityService\Resource;

use Google\Service\CertificateAuthorityService\Certificate;
use Google\Service\CertificateAuthorityService\ListCertificatesResponse;
use Google\Service\CertificateAuthorityService\RevokeCertificateRequest;

/**
 * The "certificates" collection of methods.
 * Typical usage is:
 *  <code>
 *   $privatecaService = new Google\Service\CertificateAuthorityService(...);
 *   $certificates = $privatecaService->projects_locations_caPools_certificates;
 *  </code>
 */
class ProjectsLocationsCaPoolsCertificates extends \Google\Service\Resource
{
  /**
   * Create a new Certificate in a given Project, Location from a particular
   * CaPool. (certificates.create)
   *
   * @param string $parent Required. The resource name of the CaPool associated
   * with the Certificate, in the format `projects/locations/caPools`.
   * @param Certificate $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string certificateId Optional. It must be unique within a location
   * and match the regular expression `[a-zA-Z0-9_-]{1,63}`. This field is
   * required when using a CertificateAuthority in the Enterprise
   * CertificateAuthority.tier, but is optional and its value is ignored
   * otherwise.
   * @opt_param string issuingCertificateAuthorityId Optional. The resource ID of
   * the CertificateAuthority that should issue the certificate. This optional
   * field will ignore the load-balancing scheme of the Pool and directly issue
   * the certificate from the CA with the specified ID, contained in the same
   * CaPool referenced by `parent`. Per-CA quota rules apply. If left empty, a
   * CertificateAuthority will be chosen from the CaPool by the service. For
   * example, to issue a Certificate from a Certificate Authority with resource
   * name "projects/my-project/locations/us-central1/caPools/my-
   * pool/certificateAuthorities/my-ca", you can set the parent to "projects/my-
   * project/locations/us-central1/caPools/my-pool" and the
   * issuing_certificate_authority_id to "my-ca".
   * @opt_param string requestId Optional. An ID to identify requests. Specify a
   * unique request ID so that if you must retry your request, the server will
   * know to ignore the request if it has already been completed. The server will
   * guarantee that for at least 60 minutes since the first request. For example,
   * consider a situation where you make an initial request and the request times
   * out. If you make the request again with the same request ID, the server can
   * check if original operation with the same request ID was received, and if so,
   * will ignore the second request. This prevents clients from accidentally
   * creating duplicate commitments. The request ID must be a valid UUID with the
   * exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @opt_param bool validateOnly Optional. If this is true, no Certificate
   * resource will be persisted regardless of the CaPool's tier, and the returned
   * Certificate will not contain the pem_certificate field.
   * @return Certificate
   * @throws \Google\Service\Exception
   */
  public function create($parent, Certificate $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Certificate::class);
  }
  /**
   * Returns a Certificate. (certificates.get)
   *
   * @param string $name Required. The name of the Certificate to get.
   * @param array $optParams Optional parameters.
   * @return Certificate
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Certificate::class);
  }
  /**
   * Lists Certificates. (certificates.listProjectsLocationsCaPoolsCertificates)
   *
   * @param string $parent Required. The resource name of the location associated
   * with the Certificates, in the format `projects/locations/caPools`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Only include resources that match the
   * filter in the response. For details on supported filters and syntax, see
   * [Certificates Filtering documentation](https://cloud.google.com/certificate-
   * authority-service/docs/sorting-filtering-certificates#filtering_support).
   * @opt_param string orderBy Optional. Specify how the results should be sorted.
   * For details on supported fields and syntax, see [Certificates Sorting
   * documentation](https://cloud.google.com/certificate-authority-
   * service/docs/sorting-filtering-certificates#sorting_support).
   * @opt_param int pageSize Optional. Limit on the number of Certificates to
   * include in the response. Further Certificates can subsequently be obtained by
   * including the ListCertificatesResponse.next_page_token in a subsequent
   * request. If unspecified, the server will pick an appropriate default.
   * @opt_param string pageToken Optional. Pagination token, returned earlier via
   * ListCertificatesResponse.next_page_token.
   * @return ListCertificatesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsCaPoolsCertificates($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListCertificatesResponse::class);
  }
  /**
   * Update a Certificate. Currently, the only field you can update is the labels
   * field. (certificates.patch)
   *
   * @param string $name Identifier. The resource name for this Certificate in the
   * format `projects/locations/caPools/certificates`.
   * @param Certificate $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId Optional. An ID to identify requests. Specify a
   * unique request ID so that if you must retry your request, the server will
   * know to ignore the request if it has already been completed. The server will
   * guarantee that for at least 60 minutes since the first request. For example,
   * consider a situation where you make an initial request and the request times
   * out. If you make the request again with the same request ID, the server can
   * check if original operation with the same request ID was received, and if so,
   * will ignore the second request. This prevents clients from accidentally
   * creating duplicate commitments. The request ID must be a valid UUID with the
   * exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @opt_param string updateMask Required. A list of fields to be updated in this
   * request.
   * @return Certificate
   * @throws \Google\Service\Exception
   */
  public function patch($name, Certificate $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Certificate::class);
  }
  /**
   * Revoke a Certificate. (certificates.revoke)
   *
   * @param string $name Required. The resource name for this Certificate in the
   * format `projects/locations/caPools/certificates`.
   * @param RevokeCertificateRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Certificate
   * @throws \Google\Service\Exception
   */
  public function revoke($name, RevokeCertificateRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('revoke', [$params], Certificate::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsCaPoolsCertificates::class, 'Google_Service_CertificateAuthorityService_Resource_ProjectsLocationsCaPoolsCertificates');
