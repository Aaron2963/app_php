<?php

namespace Lin\AppPhp\Tests\Authorization;

use Lin\AppPhp\Authorization\OAuthAuthorization;
use PHPUnit\Framework\TestCase;
use Firebase\JWT\JWT;

class SampleAuth extends OAuthAuthorization
{
    protected function IsTokenRevoked($JTI)
    {
        return $JTI !== 'abc123';
    }
}

/**
 * @covers \Lin\AppPhp\Authorization\OAuthAuthorization
 */
class OAuthAuthorizationTest extends TestCase
{
    private $publicKeyUri = __DIR__ . '/asset/public.key';

    /**
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::Authorize
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::IsTokenRevoked
     */
    public function testAuthorizeValidToken()
    {
        $authorization = $this->getMockForAbstractClass(OAuthAuthorization::class, [$this->publicKeyUri]);
        $authorization->expects($this->once())
            ->method('IsTokenRevoked')
            ->with('abc123')
            ->willReturn(false);

        $token = $this->generateValidToken(['scopes' => ['read', 'write']]);
        $this->assertTrue($authorization->Authorize($token, ['read', 'write']));
    }

    /**
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::Authorize
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::IsTokenRevoked
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::GetError
     */
    public function testAuthorizeTokenWithMissingScopesThrowsException()
    {
        $authorization = $this->getMockForAbstractClass(OAuthAuthorization::class, [$this->publicKeyUri]);
        $authorization->expects($this->once())
            ->method('IsTokenRevoked')
            ->with('abc123')
            ->willReturn(false);

        $token = $this->generateValidToken(['scopes' => ['read']]);
        $this->assertFalse($authorization->Authorize($token, ['delete']));
        $this->assertInstanceOf(\Exception::class, $authorization->GetError());
        $this->assertStringContainsString('token does not have required scopes', $authorization->GetError()->getMessage());
    }

    /**
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::Authorize
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::IsTokenRevoked
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::GetError
     */
    public function testAuthorizeRevokedTokenThrowsException()
    {
        $authorization = $this->getMockForAbstractClass(OAuthAuthorization::class, [$this->publicKeyUri]);
        $authorization->expects($this->once())
            ->method('IsTokenRevoked')
            ->with('abc123')
            ->willReturn(true);

        $token = $this->generateValidToken(['scopes' => ['read', 'write']]);
        $this->assertFalse($authorization->Authorize($token, ['read', 'write']));
        $this->assertInstanceOf(\Exception::class, $authorization->GetError());
        $this->assertStringContainsString('token is revoked', $authorization->GetError()->getMessage());
    }

    /**
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::Authorize
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::IsTokenRevoked
     * @covers \Lin\AppPhp\Authorization\OAuthAuthorization::GetError
     */
    public function testAuthorizeTokenWithoutScopes()
    {
        $authorization = new SampleAuth($this->publicKeyUri);

        $token = $this->generateValidToken();
        $this->assertTrue($authorization->Authorize($token));
        $this->assertNull($authorization->GetError());
    }

    private function generateValidToken(array $payload = [])
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = array_merge([
            'iss' => 'https://example.com',
            'sub' => '1234567890',
            'iat' => time(),
            'exp' => time() + 3600,
            'jti' => 'abc123',
        ], $payload);

        $secret = file_get_contents(__DIR__ . '/asset/private.key');

        return JWT::encode($payload, $secret, 'RS256', null, $header);
    }
}