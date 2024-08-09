<?php

namespace Lin\AppPhp\Authorization;

interface AuthorizationInterface
{
    /**
     * Authorize token
     *
     * @param   string      $Token
     * @param   string[]    $RequestScopes
     * 
     * @return bool
     * 
     */
    public function Authorize($Token, $RequestScopes = []): bool;
}