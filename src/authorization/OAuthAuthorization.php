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
    
    public function Authorize($Token)
    {
        // get public key
        $PublicKey = file_get_contents($this->PublicKeyUri);
        try {
            $Decoded = JWT::decode($Token, new Key($PublicKey, 'RS256'));
            if ($this->IsTokenRevoked($Decoded->jti)) {
                return false;
            }
            return true;
        } catch (InvalidArgumentException $e) {
            // provided key/key-array is empty or malformed.
            $this->Error = $e;
        } catch (DomainException $e) {
            // provided algorithm is unsupported OR
            // provided key is invalid OR
            // unknown error thrown in openSSL or libsodium OR
            // libsodium is required but not available.
            $this->Error = $e;
        } catch (SignatureInvalidException $e) {
            // provided JWT signature verification failed.
            $this->Error = $e;
        } catch (BeforeValidException $e) {
            // provided JWT is trying to be used before "nbf" claim OR
            // provided JWT is trying to be used before "iat" claim.
            $this->Error = $e;
        } catch (ExpiredException $e) {
            // provided JWT is trying to be used after "exp" claim.
            $this->Error = $e;
        } catch (UnexpectedValueException $e) {
            // provided JWT is malformed OR
            // provided JWT is missing an algorithm / using an unsupported algorithm OR
            // provided JWT algorithm does not match provided key OR
            // provided key ID in key/key-array is empty or invalid.
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