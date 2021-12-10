<?php

namespace MelbaCh\LaravelZoho\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;

class ZohoAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware(config('zoho.middleware', []));
    }

    /**
     * @param AccessTokenRepository $accessTokenRepository
     *
     * @return RedirectResponse|Redirector|string
     *
     * @throws IdentityProviderException
     */
    public function requestToken(AccessTokenRepository $accessTokenRepository)
    {
        if (! request()->get('code')) {
            return $this->redirectToZoho();
        }

        $this->verifyState();
        $this->getAccessToken($accessTokenRepository);

        return redirect(config('zoho.redirect_url', '/'));
    }

    /**
     * @return RedirectResponse|Redirector
     */
    private function redirectToZoho()
    {
        $provider = $this->getProvider();

        $redirectTo = $provider->getAuthorizationUrl(
            [
                'redirect_uri' => url()->current(),
                'access_type'  => 'offline',
                'prompt'       => 'consent',
            ]
        );

        session(['oauth2state' => $provider->getState()]);

        return redirect($redirectTo);
    }

    /**
     * Get a fresh Access Token from Zoho and store it into the database.
     *
     * @param AccessTokenRepository $accessTokenRepository
     *
     * @return AccessTokenInterface
     *
     * @throws IdentityProviderException
     */
    private function getAccessToken(
        AccessTokenRepository $accessTokenRepository
    ): AccessTokenInterface {
        $provider = $this->getProvider();
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code'         => request()->get('code'),
            'redirect_uri' => url()->current(),
        ]);

        $accessTokenRepository->store($accessToken);

        return $accessToken;
    }

    private function getProvider(): ZohoAuthProvider
    {
        return app(ZohoAuthProvider::class);
    }

    /**
     * Validate that the callback has the correct state.
     * Otherwise it may a phishing/csrf attempt
     * It guard like a CSRF protection.
     * It could also be refactored as middleware.
     */
    private function verifyState(): void
    {
        $state = request()->get('state');

        if (
            $state === null
            || session('oauth2state') !== $state
        ) {
            session()->forget('oauth2state');
            abort(403, 'Invalid state');
        }
    }
}