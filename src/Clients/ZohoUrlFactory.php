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

    public function api(string $module, string $url, array $parameters = [])
    {
        return $this->make($module, $url, $parameters);
    }

    public function make(string $module, string $url, array $parameters = []): string
    {
        if (Str::startsWith($url, '/')) {
            $url = Str::replaceFirst('/', '', $url);
        }

        if ($module === ZohoModules::Books) {
            $url = $this->books($url);
        } else {
            $url = $this->default($module, $url);
        }

        foreach ($parameters as $parameter => $value) {
            $url = $this->addParameterToUrlQuery($url, $parameter, $value);
        }

        return $url;
    }

    public function web(string $module, string $url, array $parameters = [])
    {
        if (Str::startsWith($url, '/')) {
            $url = Str::replaceFirst('/', '', $url);
        }

        $url = Str::finish($this->baseWebUrl($module), '/') . $url;

        foreach ($parameters as $parameter => $value) {
            $url = $this->addParameterToUrlQuery($url, $parameter, $value);
        }

        return $url;
    }


    protected function default(string $module, string $url)
    {
        return Str::finish($this->baseApiUrl($module), '/') . $url;
    }

    protected function books(string $url)
    {
        $url = Str::finish($this->baseApiUrl(ZohoModules::Books), '/') . $url;
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

    public function baseApiUrl(string $module): string
    {
        $region = $this->config->region() ?? 'US';

        return [
            ZohoModules::Books   => [
                'EU' => 'https://books.zoho.eu/api/v3',
                'US' => 'https://books.zoho.com/api/v3',
                'IN' => 'https://books.zoho.in/api/v3',
                'AU' => 'https://books.zoho.com.au/api/v3',
                'CN' => 'https://books.zoho.com.cn/api/v3',
            ],
            ZohoModules::Crm     => [
                'EU' => 'https://www.zohoapis.eu/crm/v2',
                'US' => 'https://www.zohoapis.com/crm/v2',
                'IN' => 'https://www.zohoapis.in/crm/v2',
                'AU' => 'https://www.zohoapis.com.au/crm/v2',
                'CN' => 'https://www.zohoapis.com.cn/crm/v2',
            ],
            ZohoModules::Recruit => [
                'EU' => 'https://recruit.zoho.eu/recruit/v2',
                'US' => 'https://recruit.zoho.com/recruit/v2',
                'IN' => 'https://recruit.zoho.in/recruit/v2',
                'AU' => 'https://recruit.zoho.com.au/recruit/v2',
                'CN' => 'https://recruit.zoho.com.cn/recruit/v2',
            ],
        ][$module][$region];
    }

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

    public function baseWebUrl(string $module): string
    {
        $region = $this->config->region() ?? 'US';
        $organization = $this->config->currentOrganizationId();

        return [
            ZohoModules::Books   => [
                'EU' => 'https://books.zoho.eu/app#',
                'US' => 'https://books.zoho.com/app#',
                'IN' => 'https://books.zoho.in/app#',
                'AU' => 'https://books.zoho.com.au/app#',
                'CN' => 'https://books.zoho.com.cn/app#',
            ],
            ZohoModules::Crm     => [
                'EU' => "https://crm.zoho.eu/crm/{$organization}",
                'US' => "https://crm.zoho.com/crm/{$organization}",
                'IN' => "https://crm.zoho.in/crm/{$organization}",
                'AU' => "https://crm.zoho.com.eu/crm/{$organization}",
                'CN' => "https://crm.zoho.com.cn/crm/{$organization}",
            ],
            ZohoModules::Recruit => [
                'EU' => "https://recruit.zoho.eu/recruit/{$organization}",
                'US' => "https://recruit.zoho.com/recruit/{$organization}",
                'IN' => "https://recruit.zoho.in/recruit/{$organization}",
                'AU' => "https://recruit.zoho.com.eu/recruit/{$organization}",
                'CN' => "https://recruit.zoho.com.cn/recruit/{$organization}",
            ],
        ][$module][$region];
    }

}