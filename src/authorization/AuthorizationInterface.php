<?php

namespace Lin\AppPhp\Authorization;

interface AuthorizationInterface
{
    /**
     * Authorize token
     *
     * @param   string      $Token
     * @param   string[]    $ResourceScopes
     * 
     * @return bool
     * 
     */
    public function Authorize($Token, $ResourceScopes = []);
}