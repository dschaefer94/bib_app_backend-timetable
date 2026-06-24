<?php
namespace App\Security;

class TokenClaimResolver
{
    private string $defaultClass;

    public function __construct(string $defaultClass = 'Dummyklasse')
    {
        $this->defaultClass = $defaultClass;
    }

    /**
     * Resolve the class name from decoded JWT claims.
     */
    public function resolveClassFromClaims(array $claims): string
    {
        if (isset($claims['klasse']) && is_string($claims['klasse']) && $claims['klasse'] !== '') {
            return $claims['klasse'];
        }

        if (isset($claims['attributes']['klasse']) && is_array($claims['attributes']['klasse']) && !empty($claims['attributes']['klasse'][0])) {
            return $claims['attributes']['klasse'][0];
        }

        if (isset($claims['custom:klasse']) && is_string($claims['custom:klasse']) && $claims['custom:klasse'] !== '') {
            return $claims['custom:klasse'];
        }

        return $this->defaultClass;
    }
}

