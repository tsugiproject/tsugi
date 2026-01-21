<?php

require_once "src/Core/Keyset.php";

class KeysetTest extends \PHPUnit\Framework\TestCase
{
   
/*
{"kty":"RSA","alg":"RS256","e":"AQAB","n":"vESXFmlzHz-nhZXTkjo29SBpamCzkd7SnpMXgdFEWjLfDeOu0D3JivEEUQ4U67xUBMY9voiJsG2oydMXjgkmGliUIVg-rhyKdBUJu5v6F659FwCj60A8J8qcstIkZfBn3yyOPVwp1FHEUSNvtbDLSRIHFPv-kh8gYyvqz130hE37qAVcaNME7lkbDmH1vbxi3D3A8AxKtiHs8oS41ui2MuSAN9MDb7NjAlFkf2iXlSVxAW5xSek4nHGr4BJKe_13vhLOvRUCTN8h8z-SLORWabxoNIkzuAab0NtfO_Qh0rgoWFC9T69jJPAPsXMDCn5oQ3xh_vhG0vltSSIzHsZ8pw","kid":"b1ee8ab854fdeea74bcd71dde9851f91f60e197396ba7ae562060d16ae1bcd49","use":"sig"}
*/
    public function testBuildJwk() {
	$pubkey = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvESXFmlzHz+nhZXTkjo2 9SBpamCzkd7SnpMXgdFEWjLfDeOu0D3JivEEUQ4U67xUBMY9voiJsG2oydMXjgkm GliUIVg+rhyKdBUJu5v6F659FwCj60A8J8qcstIkZfBn3yyOPVwp1FHEUSNvtbDL SRIHFPv+kh8gYyvqz130hE37qAVcaNME7lkbDmH1vbxi3D3A8AxKtiHs8oS41ui2 MuSAN9MDb7NjAlFkf2iXlSVxAW5xSek4nHGr4BJKe/13vhLOvRUCTN8h8z+SLORW abxoNIkzuAab0NtfO/Qh0rgoWFC9T69jJPAPsXMDCn5oQ3xh/vhG0vltSSIzHsZ8 pwIDAQAB
-----END PUBLIC KEY-----";

    	$jwk = \Tsugi\Core\Keyset::build_jwk($pubkey);
        $jwkstr = json_encode($jwk);

	$this->assertStringContainsString('"AQAB"', $jwkstr);
	$this->assertStringContainsString('"vESXFmlzHz-nhZXTkjo', $jwkstr);
    }

}
