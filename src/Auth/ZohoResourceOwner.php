<?php

namespace MelbaCh\LaravelZoho\Auth;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class ZohoResourceOwner implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $organization;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->organization = $response['org'][0];
    }

    public function getId()
    {
        return $this->organization['id'];
    }

    public function getName()
    {
        return $this->organization['company_name'];
    }

    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->organization;
    }
}
