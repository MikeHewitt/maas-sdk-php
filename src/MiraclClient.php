<?php
/**
 * Miracl MAAS Client
 */

namespace Com\Miracl\MaasSdk;

/**
 * Class MiraclClient
 */
class MiraclClient
{
    /**
     * Base url for OIDC
     *
     * @var string
     */
    private $baseURL;

    /**
     * OIDC configuration
     *
     * @var array
     */
    private $config;

    /**
     * OpenIDConnectClient object
     *
     * @var \OpenIDConnectClient
     */
    private $oidc;

    /**
     * Client ID (from user)
     *
     * @var string
     */
    private $clientID;

    /**
     * Client secret (from user)
     *
     * @var string
     */
    private $clientSecret;

    /**
     * Redirect URL (from user)
     * @var string
     */
    private $redirectURL;

    /**
     * MiraclClient constructor
     *
     * @param string $clientID     Client ID
     * @param string $clientSecret Client Secret
     * @param string $redirectURL  Redirect URL
     * @param string $baseURL      Base URL
     */
    public function __construct($clientID, $clientSecret, $redirectURL, $baseURL = 'https://api.mpin.io')
    {
        $this->baseURL = $baseURL;

        $this->config = array(
            'authorization_endpoint' => $baseURL.'/authorize',
            'token_endpoint' => $baseURL.'/oidc/token',
            'userinfo_endpoint' => $baseURL.'/oidc/userinfo',
            'jwks_uri' => $baseURL.'/oidc/certs'
        );

        $this->oidc = $this->createOpenIDConnectClient($baseURL, $clientID, $clientSecret);
        $this->oidc->providerConfigParam($this->config);
        /** @noinspection PhpParamsInspection */
        $this->oidc->setRedirectURL($redirectURL);
        $this->redirectURL = $redirectURL;
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Method returns default implementation of OpenIDConnectClient.
     * Override for client customization
     *
     * @param string $baseURL      Base URL
     * @param string $clientID     Client ID
     * @param string $clientSecret Client Secret
     *
     * @return \OpenIDConnectClient OpenIDConnectClient object
     */
    public function createOpenIDConnectClient($baseURL, $clientID, $clientSecret)
    {
        return new \OpenIDConnectClient($baseURL, $clientID, $clientSecret);
    }

    /**
     * Validates the current authorization.
     * In the case of callback it requests additional data from the Miracl system and caches data in the session.
     * Returns true if authorization has just happened.
     *
     * @return bool True if authorization validation just happened.
     *
     * @throws OpenIDConnectClientException
     */
    public function validateAuthorization()
    {
        try {
            if (isset($_REQUEST['code'])) {
                $this->oidc->authenticate();
                $token = $this->oidc->getAccessToken();
                if ($token != null) {
                    $_SESSION['miracl_access_token'] = $token;
                    $this->refreshUserData();
                    return true;
                }
            }
        } catch (\OpenIDConnectClientException $e) {
            error_log(sprintf('OpenIDConnect Client Exception: %s', $e->getMessage()));
        }
        return false;
    }

    /**
     * Checks if authentification information is in the session and
     * returns true if the user is considered to be logged in.
     *
     * NOTE:Call {@link MiraclClient::validateAuthorization} before this function
     * to ensure that client is logged in if authorization has happened.
     *
     * @return bool True if session contains miracl token
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['miracl_access_token']);
    }

    /**
     * Generates the URL for use in the mpad.js script and saves the verification data in the session.
     *
     * @return string URL
     */
    public function getAuthURL()
    {
        $auth_endpoint = $this->getProviderConfigValue('authorization_endpoint');
        $response_type = 'code';

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

        $auth_endpoint .= '?'.http_build_query($auth_params, null, '&');

        return $auth_endpoint;
    }

    /**
     * Refreshes cached user data.
     * Can invalidate logged in status if token is expired.
     */
    public function refreshUserData()
    {
        unset($_SESSION['miracl_sub']);
        unset($_SESSION['miracl_email']);
        $data = $this->requestUserInfo($_SESSION['miracl_access_token']);

        if (array_key_exists('sub', $data)) {
            $_SESSION['miracl_sub'] = $data->sub;
        } else {
            $this->logout();
            return;
        }

        if (array_key_exists('email', $data)) {
            $_SESSION['miracl_email'] = $data->email;
        } else {
            $_SESSION['miracl_email'] = '';
        }
    }


    /**
     * Clears user data from session.
     */
    public function logout()
    {
        unset($_SESSION['miracl_access_token']);
        unset($_SESSION['miracl_sub']);
        unset($_SESSION['miracl_email']);
        unset($_SESSION['openid_connect_nonce']);
        unset($_SESSION['openid_connect_state']);
    }

    /**
     * Returns cached user ID.
     * Can be used only when logged in.
     *
     * @return string
     */
    public function getUserID()
    {
        if (isset($_SESSION['miracl_sub'])) {
            return $_SESSION['miracl_sub'];
        }
    }

    /**
     * Returns cached user e-mail.
     * Can be used only when logged in.
     *
     * @return string
     */
    public function getEmail()
    {
        if (isset($_SESSION['miracl_email'])) {
            return $_SESSION['miracl_email'];
        }
        return '';
    }

    /**
     * Generate MD5 string from unique random Identifier
     *
     * @return string
     */
    protected function generateRandString()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * @param string $key config key
     *
     * @return string Value from provider config
     */
    protected function getProviderConfigValue($key)
    {
        return $this->config[$key];
    }

    /**
     * Return the request user info
     *
     * @param string $accessToken Access Token
     *
     * @return mixed
     */
    private function requestUserInfo($accessToken)
    {
        $user_info_endpoint = $this->getProviderConfigValue('userinfo_endpoint');
        $schema = 'openid';

        $user_info_endpoint .= '?schema='.$schema;

        //The accessToken has to be send in the Authorization header, so we create a new array with only this header.
        $headers = array('Authorization: Bearer '.$accessToken);

        $user_json = json_decode($this->fetchURL($user_info_endpoint, null, $headers));

        return $user_json;
    }

    /**
     * Fetch the content from the specified URL
     *
     * @param string $url       Request URL
     * @param string $post_body Content of the POST BODY
     * @param array  $headers   Headers
     *
     * @return string
     */
    protected function fetchURL($url, $post_body = null, $headers = array())
    {
        // OK cool - then let's create a new cURL resource handle
        $crh = curl_init();

        // Determine whether this is a GET or POST
        if ($post_body != null) {
            curl_setopt($crh, CURLOPT_POST, 1);
            curl_setopt($crh, CURLOPT_POSTFIELDS, $post_body);

            // Default content type is form encoded
            $content_type = 'application/x-www-form-urlencoded';

            // Determine if this is a JSON payload and add the appropriate content type
            if (is_object(json_decode($post_body))) {
                $content_type = 'application/json';
            }

            // Add POST-specific headers
            $headers[] = 'Content-Type: '.$content_type;
            $headers[] = 'Content-Length: ' . strlen($post_body);
        }

        // If we set some heaers include them
        if (count($headers) > 0) {
            curl_setopt($crh, CURLOPT_HTTPHEADER, $headers);
        }

        // Set URL to download
        curl_setopt($crh, CURLOPT_URL, $url);

        if (isset($this->httpProxy)) {
            curl_setopt($crh, CURLOPT_PROXY, $this->httpProxy);
        }

        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($crh, CURLOPT_HEADER, 0);

        // Set cert, otherwise ignore SSL peer verification
        if (isset($this->certPath)) {
            curl_setopt($crh, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($crh, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($crh, CURLOPT_CAINFO, $this->certPath);
        } else {
            curl_setopt($crh, CURLOPT_SSL_VERIFYPEER, false);
        }

        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($crh, CURLOPT_RETURNTRANSFER, true);

        // Timeout in seconds
        curl_setopt($crh, CURLOPT_TIMEOUT, 60);

        // Download the given URL, and return output
        $output = curl_exec($crh);

        if ($output === false) {
            throw new \OpenIDConnectClientException(sprintf('CURL error: %s', curl_error($crh)));
        }

        // Close the cURL resource, and free system resources
        curl_close($crh);

        return $output;
    }
}
