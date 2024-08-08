<?php

namespace Lin\AppPhp\Tests;

use Lin\AppPhp\Authorization\OAuthAuthorization;
use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;

class OAuthAuthorizationTest extends TestCase
{
    /**
     * @covers OAuthAuthorization::Authorize, OAuthAuthorization::IsTokenRevoked, OAuthAuthorization::__construct, OAuthAuthorization::GetError
     */
    public function testAuthorize()
    {
        //generate a JWT
        $Key = file_get_contents(__DIR__ . '/asset/private.key');
        $Payload = [
            'iss' => 'http://example.com',
            'sub' => '1234567890',
            'aud' => 'http://example.org',
            'exp' => time() + 3600,
            'iat' => time(),
            'jti' => 'PASS',
            'scopes' => ['doc:read', 'doc:write']
        ];
        $PassToken = JWT::encode($Payload, $Key, 'RS256');
        $Payload['jti'] = 'FAIL';
        $FailToken = JWT::encode($Payload, $Key, 'RS256');

        //create an instance of OAuthAuthorization
        $Authorization = new class(__DIR__ . '/asset/public.key') extends OAuthAuthorization {
            protected function IsTokenRevoked($JTI)
            {
                return $JTI == 'FAIL';
            }
        };

        //test if the instance is created
        $this->assertInstanceOf(OAuthAuthorization::class, $Authorization);

        //test if authorization works on valid token
        $this->assertTrue($Authorization->Authorize($PassToken, ['doc:read']));
        $this->assertNull($Authorization->GetError());

        //test if authorization works on invalid token (out of scopes)
        $this->assertFalse($Authorization->Authorize($PassToken, ['doc:delete']));
        $this->assertEquals('token does not have required scopes', $Authorization->GetError()->getMessage());

        //test if authorization works without giving scopes
        $this->assertTrue($Authorization->Authorize($PassToken));
        $this->assertNull($Authorization->GetError());

        //test if authorization works on invalid token (revoked)
        $this->assertFalse($Authorization->Authorize($FailToken));
        $this->assertEquals('token is revoked', $Authorization->GetError()->getMessage());
    }
}
