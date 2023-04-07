<?php

namespace Lin\AppPhp\Authorization;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

class OAuthAuthorization implements AuthorizationInterface
{
    protected $PublicKeyUri;
    protected $Error;
    
    public function __construct($PublicKeyUri)
    {
        $this->PublicKeyUri = $PublicKeyUri;
    }
    
    public function Authorize($Token, $ResourceScopes = [])
    {
        // get public key
        $PublicKey = file_get_contents($this->PublicKeyUri);
        try {
            $Decoded = JWT::decode($Token, new Key($PublicKey, 'RS256'));
            if ($this->IsTokenRevoked($Decoded->jti)) {
                throw new \Exception('token is revoked');
            }
            if (empty($ResourceScopes)) {
                return true;
            }
            foreach ($ResourceScopes as $Scope) {
                if (in_array($Scope, $Decoded->scopes)) {
                    return true;
                }
            }
            throw new \Exception('token does not have required scopes');
        } catch (\Exception $e) {
            $this->Error = $e;
        }
        return false;
    }

    protected function IsTokenRevoked($JTI)
    {
        // TODO: connect to database and check if token is revoked
        return false;
    }

    public function GetError()
    {
        return $this->Error;
    }
}