<?php

/**
 * Created by PhpStorm.
 * User: elviss
 * Date: 16.28.4
 * Time: 20:25
 */

require __DIR__ . '/vendor/autoload.php';

/**
 * Class MiraclClient
 */
class MiraclClient
{
    /**
     * @var string base url for OIDC
     */
    private $baseURL = "http://mpinaas-demo.miracl.net:8001";
    /**
     * @var array OIDC configuration
     */
    private $config;

    /**
     * @var OpenIDConnectClient
     */
    private $oidc;
    /**
     * @var string Client ID (from user)
     */
    private $clientID;
    /**
     * @var string Client secret (from user)
     */
    private $clientSecret;
    /**
     * @var string Redirect URL (from user)
     */
    private $redirectURL;

    /**
     * MiraclClient constructor.
     * @param $clientID string
     * @param $clientSecret string
     * @param $redirectURL string
     */
    function __construct($clientID, $clientSecret, $redirectURL)
    {
        $baseURL = $this->baseURL;

        $this->config = array(
            'authorization_endpoint' => "$baseURL/authorize",
            'token_endpoint' => "$baseURL/oidc/token",
            'userinfo_endpoint' => "$baseURL/oidc/userinfo",
            'jwks_uri' => "$baseURL/oidc/certs"
        );

        $this->oidc = new OpenIDConnectClient($baseURL, $clientID, $clientSecret);
        $this->oidc->providerConfigParam($this->config);
        /** @noinspection PhpParamsInspection */
        $this->oidc->setRedirectURL($redirectURL);
        $this->redirectURL = $redirectURL;
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return bool True if authorization validation just happened.
     * @throws OpenIDConnectClientException
     */
    public function validateAuthorization()
    {
        if (isset($_REQUEST["code"])) {
            $this->oidc->authenticate();
            $token = $this->oidc->getAccessToken();
            if ($token != null) {
                $_SESSION['miracl_access_token'] = $token;
                $_SESSION['miracl_email'] = $this->oidc->requestUserInfo("sub");
                $_SESSION['miracl_user'] = $this->oidc->requestUserInfo("user_id");
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if user is logged in. Call {@link MiraclClient::validateAuthorization} before this function to ensure that
     * client is logged in if authorization has happened.
     *
     * @return bool True if session contains miracl token
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['miracl_access_token']);
    }

    /**
     * Get URL used for authentication
     *
     * @return string URL
     */
    public function getAuthURL()
    {
        $auth_endpoint = $this->getProviderConfigValue("authorization_endpoint");
        $response_type = "code";

        // Generate and store a nonce in the session
        // The nonce is an arbitrary value
        $nonce = $this->generateRandString();
        $_SESSION['openid_connect_nonce'] = $nonce;

        // State essentially acts as a session key for OIDC
        $state = $this->generateRandString();
        $_SESSION['openid_connect_state'] = $state;

        $auth_params = array(
            'response_type' => $response_type,
            'redirect_uri' => $this->redirectURL,
            'client_id' => $this->clientID,
            'nonce' => $nonce,
            'state' => $state,
            'scope' => 'openid email sub name'
        );

        $auth_endpoint .= '?' . http_build_query($auth_params, null, '&');

        return $auth_endpoint;
    }

    /**
     * Clears user data from session.
     */
    public function logout()
    {
        unset($_SESSION['miracl_access_token']);
        unset($_SESSION['miracl_email']);
        unset($_SESSION['openid_connect_nonce']);
        unset($_SESSION['openid_connect_state']);
    }

    /**
     * @return string User e-mail
     */
    public function getEmail()
    {
        return $_SESSION['miracl_email'];
    }

    /**
     * @return string MD5 string from unique random Identifier
     */
    protected function generateRandString()
    {
        return md5(uniqid(rand(), TRUE));
    }

    /**
     * @param $key string config key
     * @return string value from provider config
     */
    protected function getProviderConfigValue($key)
    {
        return $this->config[$key];
    }
}