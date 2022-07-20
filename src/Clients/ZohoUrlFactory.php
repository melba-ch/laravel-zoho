<?php

namespace MelbaCh\LaravelZoho\Clients;

use MelbaCh\LaravelZoho\Repositories\ConfigRepository;
use MelbaCh\LaravelZoho\ZohoModules;
use Str;

class ZohoUrlFactory
{
    protected ConfigRepository $config;

    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->config = $configRepository;
    }

    public function api(ZohoModules $module, string $url, array $parameters = [])
    {
        if (Str::startsWith($url, '/')) {
            $url = Str::replaceFirst('/', '', $url);
        }

        if ($module === ZohoModules::BOOKS) {
            $url = $this->books($url);
        } else {
            $url = Str::finish($this->baseApiUrl($module), '/') . $url;
        }

        foreach ($parameters as $parameter => $value) {
            $url = $this->addParameterToUrlQuery($url, $parameter, $value);
        }

        return $url;
    }

    public function web(ZohoModules $module, string $url, array $parameters = [])
    {
        if (Str::startsWith($url, '/')) {
            $url = Str::replaceFirst('/', '', $url);
        }

        if (config('zoho.sandbox', false)) {
            $url = Str::finish($this->baseWebUrlSandbox($module), '/') . $url;
        } else {
            $url = Str::finish($this->baseWebUrl($module), '/') . $url;
        }


        foreach ($parameters as $parameter => $value) {
            $url = $this->addParameterToUrlQuery($url, $parameter, $value);
        }

        return $url;
    }

    /**
     * @param string $type
     * @return string
     * @internal
     */
    public function oauthApiUrl(string $type): string
    {
        $region = $this->config->region() ?? 'US';

        return [
            'authorization_url'       => [
                'EU' => 'https://accounts.zoho.eu/oauth/v2/auth',
                'US' => 'https://accounts.zoho.com/oauth/v2/auth',
                'IN' => 'https://accounts.zoho.in/oauth/v2/auth',
                'AU' => 'https://accounts.zoho.com.au/oauth/v2/auth',
                'CN' => 'https://accounts.zoho.com.cn/oauth/v2/auth',
            ],
            'access_token_url'        => [
                'EU' => 'https://accounts.zoho.eu/oauth/v2/token',
                'US' => 'https://accounts.zoho.com/oauth/v2/token',
                'IN' => 'https://accounts.zoho.in/oauth/v2/token',
                'AU' => 'https://accounts.zoho.com.au/oauth/v2/token',
                'CN' => 'https://accounts.zoho.com.cn/oauth/v2/token',
            ],
            'revoke_access_token_url' => [
                'EU' => 'https://accounts.zoho.eu/oauth/v2/token/revoke',
                'US' => 'https://accounts.zoho.com/oauth/v2/token/revoke',
                'IN' => 'https://accounts.zoho.in/oauth/v2/token/revoke',
                'AU' => 'https://accounts.zoho.com.au/oauth/v2/token/revoke',
                'CN' => 'https://accounts.zoho.com.cn/oauth/v2/token/revoke',
            ],
        ][$type][$region];
    }

    protected function books(string $url)
    {
        $url = Str::finish($this->baseApiUrl(ZohoModules::BOOKS), '/') . $url;
        return $this->addParameterToUrlQuery($url, 'organization_id', $this->config->currentOrganizationId());
    }

    protected function addParameterToUrlQuery(string $url, string $parameter, $value): string
    {
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $params);
        } else {
            $params = [];
        }

        $params[$parameter] = $value;

        $urlParts['query'] = http_build_query($params);

        return $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . '?' . $urlParts['query'];
    }

    protected function baseApiUrl(ZohoModules $module): string
    {
        $region = $this->config->region() ?? 'US';

        return [
            ZohoModules::BOOKS->value   => [
                'EU' => 'https://books.zoho.eu/api/v3',
                'US' => 'https://books.zoho.com/api/v3',
                'IN' => 'https://books.zoho.in/api/v3',
                'AU' => 'https://books.zoho.com.au/api/v3',
                'CN' => 'https://books.zoho.com.cn/api/v3',
            ],
            ZohoModules::CRM->value     => [
                'EU' => 'https://www.zohoapis.eu/crm/v3',
                'US' => 'https://www.zohoapis.com/crm/v3',
                'IN' => 'https://www.zohoapis.in/crm/v3',
                'AU' => 'https://www.zohoapis.com.au/crm/v3',
                'CN' => 'https://www.zohoapis.com.cn/crm/v3',
            ],
            ZohoModules::RECRUIT->value => [
                'EU' => 'https://recruit.zoho.eu/recruit/v2',
                'US' => 'https://recruit.zoho.com/recruit/v2',
                'IN' => 'https://recruit.zoho.in/recruit/v2',
                'AU' => 'https://recruit.zoho.com.au/recruit/v2',
                'CN' => 'https://recruit.zoho.com.cn/recruit/v2',
            ],
        ][$module->value][$region];
    }

    protected function baseWebUrlSandbox(ZohoModules $module): string
    {
        $region = $this->config->region() ?? 'US';
        $organization = $this->config->currentOrganizationId();

        return [
            ZohoModules::BOOKS->value   => [
                // Not implemented yet
            ],
            ZohoModules::CRM->value     => [
                'EU' => "https://crmsandbox.zoho.eu/crm/{$organization}",
                'US' => "https://crmsandbox.zoho.com/crm/{$organization}",
                'IN' => "https://crmsandbox.zoho.in/crm/{$organization}",
                'AU' => "https://crmsandbox.zoho.com.eu/crm/{$organization}",
                'CN' => "https://crmsandbox.zoho.com.cn/crm/{$organization}",
            ],
            ZohoModules::RECRUIT->value => [
                // Not implemented yet
            ],
        ][$module->value][$region];
    }

    protected function baseWebUrl(ZohoModules $module): string
    {
        $region = $this->config->region() ?? 'US';
        $organization = $this->config->currentOrganizationId();

        return [
            ZohoModules::BOOKS->value   => [
                'EU' => 'https://books.zoho.eu/app#',
                'US' => 'https://books.zoho.com/app#',
                'IN' => 'https://books.zoho.in/app#',
                'AU' => 'https://books.zoho.com.au/app#',
                'CN' => 'https://books.zoho.com.cn/app#',
            ],
            ZohoModules::CRM->value     => [
                'EU' => "https://crm.zoho.eu/crm/{$organization}",
                'US' => "https://crm.zoho.com/crm/{$organization}",
                'IN' => "https://crm.zoho.in/crm/{$organization}",
                'AU' => "https://crm.zoho.com.eu/crm/{$organization}",
                'CN' => "https://crm.zoho.com.cn/crm/{$organization}",
            ],
            ZohoModules::RECRUIT->value => [
                'EU' => "https://recruit.zoho.eu/recruit/{$organization}",
                'US' => "https://recruit.zoho.com/recruit/{$organization}",
                'IN' => "https://recruit.zoho.in/recruit/{$organization}",
                'AU' => "https://recruit.zoho.com.eu/recruit/{$organization}",
                'CN' => "https://recruit.zoho.com.cn/recruit/{$organization}",
            ],
        ][$module->value][$region];
    }
}