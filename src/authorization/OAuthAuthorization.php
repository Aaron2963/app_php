<?php

namespace Lin\AppPhp\Authorization;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * OAuth authorization
 * 
 * This class is used to authorize token with OAuth 2.0, combined with JWT.
 * Subclass should implement IsTokenRevoked method to check whether token is revoked.
 * 
 * @package Lin\AppPhp\Authorization
 * 
 */
abstract class OAuthAuthorization implements AuthorizationInterface
{
    /**
     * Public key uri
     *
     * @var string
     */
    protected $PublicKeyUri;

    /**
     * Last error
     *
     * @var \Exception
     */
    protected $Error;

    /**
     * Check whether token is revoked, this method should be implemented by subclass
     *
     * @param   string  $JTI
     * 
     * @return  bool    return true if token is revoked
     * 
     */
    abstract protected function IsTokenRevoked($JTI);
    
    /**
     * OAuthAuthorization constructor
     *
     * @param   string  $PublicKeyUri   public key uri
     * 
     */
    public function __construct($PublicKeyUri)
    {
        $this->PublicKeyUri = $PublicKeyUri;
    }
    
    /**
     * Authorize token
     *
     * @param   string      $Token
     * @param   string[]    $RequestScopes
     * 
     * @return bool
     * 
     */
    public function Authorize($Token, $RequestScopes = [])
    {
        // get public key
        $PublicKey = file_get_contents($this->PublicKeyUri);
        unset($this->Error);
        try {
            $Decoded = JWT::decode($Token, new Key($PublicKey, 'RS256'));
            if ($this->IsTokenRevoked($Decoded->jti)) {
                throw new \Exception('token is revoked');
            }
            if (empty($RequestScopes)) {
                return true;
            }
            foreach ($RequestScopes as $Scope) {
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

    /**
     * Get error
     *
     * @return \Exception
     * 
     */
    public function GetError()
    {
        return $this->Error;
    }
}