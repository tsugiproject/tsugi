<?php

use phpseclib\Crypt\RSA;
use phpseclib\File\X509;

class JOSE_JWS_Test extends JOSE_TestCase {
    var $plain_jwt;
    var $rsa_keys;

    function setUp() {
        parent::setUp();
        $this->plain_jwt = new JOSE_JWT(array(
            'foo' => 'bar'
        ));
    }

    function testToJSON() {
        $expected = '{"protected":"eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9","payload":"eyJmb28iOiJiYXIifQ","signature":"GzzxRgDHjgBjDkbMsKaFhWnQ43xKlh8T7Ce34b9ye4afuIfE2EglIlK1itGRx1PtH7UOcwtXVWElJ0lHuuTl6hCUL5SDOMJxiPfr5SkTZFWy2SlSYNtdRfra6NPeEa3-a_15dUYv41QY14TCl5HaP7jeMLeqcTlMcjra9fDPMWUciSyWay6025wUiSQBmWW-19GNZQnRHxXNX3lCVMEQMASYT-6QqBvoiJ6vezIt08RghgGdMH1iGY_Gnb7ISuA-lvKk6fcQvQ3MN5Cx0CeqXlXP8NQQF0OwkUgTjNGsKmCG6jKlLZLeXJb72KVK1yR-6jp7OQqqzrovIP7lp-FwIw"}';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($this->rsa_keys['private'], 'RS256');
        $this->assertEquals($expected, sprintf('%s', $jws->toJSON()));
    }

    function testToJSONWithGeneralSyntax() {
        $expected = '{"payload":"eyJmb28iOiJiYXIifQ","signatures":{"protected":"eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9","signature":"GzzxRgDHjgBjDkbMsKaFhWnQ43xKlh8T7Ce34b9ye4afuIfE2EglIlK1itGRx1PtH7UOcwtXVWElJ0lHuuTl6hCUL5SDOMJxiPfr5SkTZFWy2SlSYNtdRfra6NPeEa3-a_15dUYv41QY14TCl5HaP7jeMLeqcTlMcjra9fDPMWUciSyWay6025wUiSQBmWW-19GNZQnRHxXNX3lCVMEQMASYT-6QqBvoiJ6vezIt08RghgGdMH1iGY_Gnb7ISuA-lvKk6fcQvQ3MN5Cx0CeqXlXP8NQQF0OwkUgTjNGsKmCG6jKlLZLeXJb72KVK1yR-6jp7OQqqzrovIP7lp-FwIw"}}';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($this->rsa_keys['private'], 'RS256');
        $this->assertEquals($expected, sprintf('%s', $jws->toJSON('general-syntax')));
    }

    function testSignHS256() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.jBKXM6zRu0nP2tYgNTgFxRDwKoiEbNl1P6GyXEHIwEw';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign('shared-secret', 'HS256');
        $this->assertEquals($expected, $jws->toString());
    }

    function testSignHS384() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzM4NCJ9.eyJmb28iOiJiYXIifQ.EoHJwaBtAB7OQzhInUDK5QBrKqhYX8OodiAgusI3fOJsueTm6aOpKvngGj3afGQo';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign('shared-secret', 'HS384');
        $this->assertEquals($expected, $jws->toString());
    }

    function testSignHS512() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJmb28iOiJiYXIifQ.eLwaujbDB1c19eOGpxwMksVHCkE5XLA4eps80ZDPAE8_FdQOMQvC6lF0mtAHljAai9XHEDWMXUz1NCeovs8ZVQ';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign('shared-secret', 'HS512');
        $this->assertEquals($expected, $jws->toString());
    }

    function testSignRS256() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJmb28iOiJiYXIifQ.GzzxRgDHjgBjDkbMsKaFhWnQ43xKlh8T7Ce34b9ye4afuIfE2EglIlK1itGRx1PtH7UOcwtXVWElJ0lHuuTl6hCUL5SDOMJxiPfr5SkTZFWy2SlSYNtdRfra6NPeEa3-a_15dUYv41QY14TCl5HaP7jeMLeqcTlMcjra9fDPMWUciSyWay6025wUiSQBmWW-19GNZQnRHxXNX3lCVMEQMASYT-6QqBvoiJ6vezIt08RghgGdMH1iGY_Gnb7ISuA-lvKk6fcQvQ3MN5Cx0CeqXlXP8NQQF0OwkUgTjNGsKmCG6jKlLZLeXJb72KVK1yR-6jp7OQqqzrovIP7lp-FwIw';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($this->rsa_keys['private'], 'RS256');
        $this->assertEquals($expected, $jws->toString());
    }

    function testSignRS384() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzM4NCJ9.eyJmb28iOiJiYXIifQ.Odg4nlRTnH1mI1JQEJEQCB1mmqDPFn-Gf5Te8IfLzu7sGDrvZdvGe6HutsDO3mXi7FLtQcI2i0KEQxj8fDUV4vfR1fbfyGQaz02qnt3HKEOgRGwFH1l57ayGChZftXhSCpbt9sMwTg1lsZ_egThQWG0ZErkibmXIt5ZxNwITaXX4oU3k12eH492IsScz_tIaf9NCwIQlAPodiVQL7WMQgej0o4LuZKk6ZgBsDJz_Ms2_iONxzGPWOT76iLOwYT8QaEsLX6d8_WsZ4wnfaxHVlg-zNM0Lhisi_F0_tFeueDOZPJnQp_InV7iYzP4adWOItzG_Qz_-EaNGTz4RJtxqAQ';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($this->rsa_keys['private'], 'RS384');
        $this->assertEquals($expected, $jws->toString());
    }

    function testSignRS512() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzUxMiJ9.eyJmb28iOiJiYXIifQ.uQAVgGt1oy8FlMaAx8UDnVwzuDuJsqYIDHm8cKRKqLqcZ0zUmQHgfonBA09r5CiqG5EGTaX58G6_hAFAmf-aRtJrm_cN-68xrliMXVH3m6vZdRKhbtYqCozjbmEH8nPwBFtlri15vhR5lWTT_x3VsZOHhuhbAFzyshIcYAxNDVkUssPWpDag26fRcPsIJ-Oozvp9ld1uOnu9BNSOCWF4DXUTRBfUx55pl1ihwgHrFt36eHdtQ90vJXflsJvLoHuKf4LKt0dOpsPYeJp74V1X06DFlVqL9JGAS3iSLZ_tK_MpZheJqIr5iPl4qWc4k6gSbeomXR1opKqWmbje5JiZmw';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($this->rsa_keys['private'], 'RS512');
        $this->assertEquals($expected, $jws->toString());
    }

    function testSignPS256() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJQUzI1NiJ9.eyJmb28iOiJiYXIifQ.x5S_So_vmBcuROQ1uqlZTCc5YWk9xMR4SyCrVwxylgewFVK-WIsiDyMvSuBeojNXk693f775HeO1h8VJIkuXN3wupPKn2OHFPvQMPdcygLxM7aGV8gG9Ocv-HHWAK_i3UQpek-2CjEDSGFBUQqvKKxqx7NrbB-xt4dBn6JeMEs5wqpADUDQWr5zC33OEwamZktPF10FS2HVRtLuS4X9J53x2kLIFxxqPq_pyvUvlfehniyzupyVMbhHPe9-kiibLVSN0dVX9w0UyNoNQ1ZxWXfMZ3gVsWIeaaXCseW8TD7Pm_7I6Y8_sALje08USJ4Sdj4ExpvJqqrnY2cCHIAGAQA';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($this->rsa_keys['private'], 'PS256');
        # NOTE: RSA-PSS generates different signature each time
        $expected_segments = explode('.', $expected);
        $given_segments = explode('.', $jws->toString());
        $this->assertEquals($expected_segments[0], $given_segments[0]);
        $this->assertEquals($expected_segments[1], $given_segments[1]);
    }

    function testSignPS384() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJQUzM4NCJ9.eyJmb28iOiJiYXIifQ.vvJPhzH_aZiiFaaAPOfshgaRmiqRSmyUmvL02uZGyWNtjJuA_zEJsuvs18JzOkDgHCG5MsrfhWkJKsl9Pm2DLWo2D7b8NBKpHE1oedTptOOnk8wGWUU2vBXYmuoWcKzDrH0Bl697NTTNv72AeoMWzaOXqYTx_qcOZxlscGINm0-lqttSk-gnzqbOxSAacv_YeibofxvFNw3Q3eaP36f1glYOWHOQSSWoqe0cW0F8hxcLeEr4FPRwAaFnOfG0wDsYZ8huvEun4uopEitJugC8oYiE-iax-QbbwboIiYeZtDBG51uydkOEjKi3WexFjayiQSCgj_343mUdq1wzV9dt2w';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($this->rsa_keys['private'], 'PS384');
        # NOTE: RSA-PSS generates different signature each time
        $expected_segments = explode('.', $expected);
        $given_segments = explode('.', $jws->toString());
        $this->assertEquals($expected_segments[0], $given_segments[0]);
        $this->assertEquals($expected_segments[1], $given_segments[1]);
    }

    function testSignPS512() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJQUzUxMiJ9.eyJmb28iOiJiYXIifQ.g8x8IDQuG6-TMHowGvtFSurHrxccbP9ihmRIrwtccYxO1tkBSoU1Sgl8Cf5fj4u2E24vIQIc6feaTHIt--T2gdxvvSf2W0dhfP7GH4bajiOuL7lz2QcjypvxXdhoZM3PAGyWLYK76ZJ2RCalEvApZrWGsBud-h8Gnvd69wotm6hay8ZIbm7KEy0uuRnLF9r95uKxhMH5HVWQiPi4sw3FJgUlrBL4PeLTiRbrmVmCxuD-VTAZnxUZQkyrSwF0i4YPx9erptGQY6tndB6f_7oM8aDmj4xp3EjWIhOmJ4PfIZhBTeNpQW9eKto9Q2St_rruMlhrrFdaB7w8240pMKFkqw';
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($this->rsa_keys['private'], 'PS512');
        # NOTE: RSA-PSS generates different signature each time
        $expected_segments = explode('.', $expected);
        $given_segments = explode('.', $jws->toString());
        $this->assertEquals($expected_segments[0], $given_segments[0]);
        $this->assertEquals($expected_segments[1], $given_segments[1]);
    }

    function testSignRS256WithInvalidPrivateKey() {
        $jws = new JOSE_JWS($this->plain_jwt);
        $this->setExpectedException('JOSE_Exception');
        $jws = $jws->sign('invalid pem', 'RS256');
    }

    function testSignES256() {
        $jws = new JOSE_JWS($this->plain_jwt);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jws = $jws->sign('es key should be here', 'ES256');
    }

    function testSignWithCryptRSA() {
        $expected = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJmb28iOiJiYXIifQ.GzzxRgDHjgBjDkbMsKaFhWnQ43xKlh8T7Ce34b9ye4afuIfE2EglIlK1itGRx1PtH7UOcwtXVWElJ0lHuuTl6hCUL5SDOMJxiPfr5SkTZFWy2SlSYNtdRfra6NPeEa3-a_15dUYv41QY14TCl5HaP7jeMLeqcTlMcjra9fDPMWUciSyWay6025wUiSQBmWW-19GNZQnRHxXNX3lCVMEQMASYT-6QqBvoiJ6vezIt08RghgGdMH1iGY_Gnb7ISuA-lvKk6fcQvQ3MN5Cx0CeqXlXP8NQQF0OwkUgTjNGsKmCG6jKlLZLeXJb72KVK1yR-6jp7OQqqzrovIP7lp-FwIw';
        $key = new RSA();
        $key->loadKey($this->rsa_keys['private']);
        $jws = new JOSE_JWS($this->plain_jwt);
        $jws = $jws->sign($key, 'RS256');
        $this->assertEquals($expected, $jws->toString());
    }

    function testSignWithJWK() {
        $key = new RSA();
        $key->loadKey($this->rsa_keys['private']);
        $jwk = JOSE_JWK::encode($key);
        $jws = new JOSE_JWS($this->plain_jwt);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jws->sign($jwk, 'RS256');
    }

    function testSignUnknowAlg() {
        $jws = new JOSE_JWS($this->plain_jwt);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jws = $jws->sign('secret', 'AES256');
    }

    function testVerifyHS256() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.jBKXM6zRu0nP2tYgNTgFxRDwKoiEbNl1P6GyXEHIwEw';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify('shared-secret', 'HS256'));
    }

    function testVerifyHS256_without_explicit_alg() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJmb28iOiJiYXIifQ.jBKXM6zRu0nP2tYgNTgFxRDwKoiEbNl1P6GyXEHIwEw';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jws->verify('shared-secret');
    }

    function testVerifyHS384() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzM4NCJ9.eyJmb28iOiJiYXIifQ.EoHJwaBtAB7OQzhInUDK5QBrKqhYX8OodiAgusI3fOJsueTm6aOpKvngGj3afGQo';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify('shared-secret', 'HS384'));
    }

    function testVerifyHS512() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJmb28iOiJiYXIifQ.eLwaujbDB1c19eOGpxwMksVHCkE5XLA4eps80ZDPAE8_FdQOMQvC6lF0mtAHljAai9XHEDWMXUz1NCeovs8ZVQ';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify('shared-secret', 'HS512'));
    }

    function testVerifyRS256() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJmb28iOiJiYXIifQ.GzzxRgDHjgBjDkbMsKaFhWnQ43xKlh8T7Ce34b9ye4afuIfE2EglIlK1itGRx1PtH7UOcwtXVWElJ0lHuuTl6hCUL5SDOMJxiPfr5SkTZFWy2SlSYNtdRfra6NPeEa3-a_15dUYv41QY14TCl5HaP7jeMLeqcTlMcjra9fDPMWUciSyWay6025wUiSQBmWW-19GNZQnRHxXNX3lCVMEQMASYT-6QqBvoiJ6vezIt08RghgGdMH1iGY_Gnb7ISuA-lvKk6fcQvQ3MN5Cx0CeqXlXP8NQQF0OwkUgTjNGsKmCG6jKlLZLeXJb72KVK1yR-6jp7OQqqzrovIP7lp-FwIw';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($this->rsa_keys['public']));
    }

    function testVerifyRS384() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzM4NCJ9.eyJmb28iOiJiYXIifQ.Odg4nlRTnH1mI1JQEJEQCB1mmqDPFn-Gf5Te8IfLzu7sGDrvZdvGe6HutsDO3mXi7FLtQcI2i0KEQxj8fDUV4vfR1fbfyGQaz02qnt3HKEOgRGwFH1l57ayGChZftXhSCpbt9sMwTg1lsZ_egThQWG0ZErkibmXIt5ZxNwITaXX4oU3k12eH492IsScz_tIaf9NCwIQlAPodiVQL7WMQgej0o4LuZKk6ZgBsDJz_Ms2_iONxzGPWOT76iLOwYT8QaEsLX6d8_WsZ4wnfaxHVlg-zNM0Lhisi_F0_tFeueDOZPJnQp_InV7iYzP4adWOItzG_Qz_-EaNGTz4RJtxqAQ';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($this->rsa_keys['public']));
    }

    function testVerifyRS512() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzUxMiJ9.eyJmb28iOiJiYXIifQ.uQAVgGt1oy8FlMaAx8UDnVwzuDuJsqYIDHm8cKRKqLqcZ0zUmQHgfonBA09r5CiqG5EGTaX58G6_hAFAmf-aRtJrm_cN-68xrliMXVH3m6vZdRKhbtYqCozjbmEH8nPwBFtlri15vhR5lWTT_x3VsZOHhuhbAFzyshIcYAxNDVkUssPWpDag26fRcPsIJ-Oozvp9ld1uOnu9BNSOCWF4DXUTRBfUx55pl1ihwgHrFt36eHdtQ90vJXflsJvLoHuKf4LKt0dOpsPYeJp74V1X06DFlVqL9JGAS3iSLZ_tK_MpZheJqIr5iPl4qWc4k6gSbeomXR1opKqWmbje5JiZmw';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($this->rsa_keys['public']));
    }

    function testVerifyPS256() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJQUzI1NiJ9.eyJmb28iOiJiYXIifQ.x5S_So_vmBcuROQ1uqlZTCc5YWk9xMR4SyCrVwxylgewFVK-WIsiDyMvSuBeojNXk693f775HeO1h8VJIkuXN3wupPKn2OHFPvQMPdcygLxM7aGV8gG9Ocv-HHWAK_i3UQpek-2CjEDSGFBUQqvKKxqx7NrbB-xt4dBn6JeMEs5wqpADUDQWr5zC33OEwamZktPF10FS2HVRtLuS4X9J53x2kLIFxxqPq_pyvUvlfehniyzupyVMbhHPe9-kiibLVSN0dVX9w0UyNoNQ1ZxWXfMZ3gVsWIeaaXCseW8TD7Pm_7I6Y8_sALje08USJ4Sdj4ExpvJqqrnY2cCHIAGAQA';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($this->rsa_keys['public']));
    }

    function testVerifyPS384() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJQUzM4NCJ9.eyJmb28iOiJiYXIifQ.vvJPhzH_aZiiFaaAPOfshgaRmiqRSmyUmvL02uZGyWNtjJuA_zEJsuvs18JzOkDgHCG5MsrfhWkJKsl9Pm2DLWo2D7b8NBKpHE1oedTptOOnk8wGWUU2vBXYmuoWcKzDrH0Bl697NTTNv72AeoMWzaOXqYTx_qcOZxlscGINm0-lqttSk-gnzqbOxSAacv_YeibofxvFNw3Q3eaP36f1glYOWHOQSSWoqe0cW0F8hxcLeEr4FPRwAaFnOfG0wDsYZ8huvEun4uopEitJugC8oYiE-iax-QbbwboIiYeZtDBG51uydkOEjKi3WexFjayiQSCgj_343mUdq1wzV9dt2w';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($this->rsa_keys['public']));
    }

    function testVerifyPS512() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJQUzUxMiJ9.eyJmb28iOiJiYXIifQ.g8x8IDQuG6-TMHowGvtFSurHrxccbP9ihmRIrwtccYxO1tkBSoU1Sgl8Cf5fj4u2E24vIQIc6feaTHIt--T2gdxvvSf2W0dhfP7GH4bajiOuL7lz2QcjypvxXdhoZM3PAGyWLYK76ZJ2RCalEvApZrWGsBud-h8Gnvd69wotm6hay8ZIbm7KEy0uuRnLF9r95uKxhMH5HVWQiPi4sw3FJgUlrBL4PeLTiRbrmVmCxuD-VTAZnxUZQkyrSwF0i4YPx9erptGQY6tndB6f_7oM8aDmj4xp3EjWIhOmJ4PfIZhBTeNpQW9eKto9Q2St_rruMlhrrFdaB7w8240pMKFkqw';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($this->rsa_keys['public']));
    }

    function testVerifyES256() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJpc3MiOiJqb2UiLCJleHAiOjEzMDA4MTkzODAsImh0dHA6Ly9leGFtcGxlLmNvbS9pc19yb290Ijp0cnVlfQ.MEQCIDh9M3Id8pPd9fp6kgtirYpAirRCU-H0IbaeruLOYWc_AiBhbsswHCIlY5yqWDsOU_sy3lMnyXlrYoQLcejPxL-nDg';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jws = $jws->verify('es key should be here');
    }

    function testVerifyUnknowAlg() {
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJ1bmtub3duIn0.eyJmb28iOiJiYXIifQ.';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jws = $jws->verify('no key works');
    }

    function testVerifyWithCryptRSA() {
        $key = new RSA();
        $key->loadKey($this->rsa_keys['public']);
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJmb28iOiJiYXIifQ.GzzxRgDHjgBjDkbMsKaFhWnQ43xKlh8T7Ce34b9ye4afuIfE2EglIlK1itGRx1PtH7UOcwtXVWElJ0lHuuTl6hCUL5SDOMJxiPfr5SkTZFWy2SlSYNtdRfra6NPeEa3-a_15dUYv41QY14TCl5HaP7jeMLeqcTlMcjra9fDPMWUciSyWay6025wUiSQBmWW-19GNZQnRHxXNX3lCVMEQMASYT-6QqBvoiJ6vezIt08RghgGdMH1iGY_Gnb7ISuA-lvKk6fcQvQ3MN5Cx0CeqXlXP8NQQF0OwkUgTjNGsKmCG6jKlLZLeXJb72KVK1yR-6jp7OQqqzrovIP7lp-FwIw';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($key));
    }

    function testVerifyWithJWK() {
        $key = new RSA();
        $key->loadKey($this->rsa_keys['public']);
        $jwk = JOSE_JWK::encode($key);
        $input = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJmb28iOiJiYXIifQ.GzzxRgDHjgBjDkbMsKaFhWnQ43xKlh8T7Ce34b9ye4afuIfE2EglIlK1itGRx1PtH7UOcwtXVWElJ0lHuuTl6hCUL5SDOMJxiPfr5SkTZFWy2SlSYNtdRfra6NPeEa3-a_15dUYv41QY14TCl5HaP7jeMLeqcTlMcjra9fDPMWUciSyWay6025wUiSQBmWW-19GNZQnRHxXNX3lCVMEQMASYT-6QqBvoiJ6vezIt08RghgGdMH1iGY_Gnb7ISuA-lvKk6fcQvQ3MN5Cx0CeqXlXP8NQQF0OwkUgTjNGsKmCG6jKlLZLeXJb72KVK1yR-6jp7OQqqzrovIP7lp-FwIw';
        $jwt = JOSE_JWT::decode($input);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($jwk));
    }

    function testVerifyWithGoogleIDToken() {
        $id_token_string = file_get_contents($this->fixture_dir . 'google.jwt');
        $cert_string = file_get_contents($this->fixture_dir . 'google.crt');
        $x509 = new X509();
        $x509->loadX509($cert_string);
        $public_key = $x509->getPublicKey()->getPublicKey();
        $jwt = JOSE_JWT::decode($id_token_string);
        $jws = new JOSE_JWS($jwt);
        $this->assertInstanceOf('JOSE_JWS', $jws->verify($public_key));
    }

    function testVerifyMalformedJWS_HS256_to_none() {
        $malformed_jwt = JOSE_JWT::decode($this->plain_jwt->toString());
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $malformed_jwt->verify('secret');
    }

    function testVerifyMalformedJWS_RS256_to_HS256_without_explicit_alg() {
        $malformed_jwt = JOSE_JWT::decode(
            $this->plain_jwt->sign($this->rsa_keys['public'], 'HS256')->toString()
        );
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $malformed_jwt->verify($this->rsa_keys['public']);
    }

    function testVerifyMalformedJWS_RS256_to_HS256_with_explicit_alg() {
        $malformed_jwt = JOSE_JWT::decode(
            $this->plain_jwt->sign($this->rsa_keys['public'], 'HS256')->toString()
        );
        $this->setExpectedException('PHPUnit_Framework_Error_Notice', 'Invalid signature');
        $malformed_jwt->verify($this->rsa_keys['public'], 'RS256');
    }
}