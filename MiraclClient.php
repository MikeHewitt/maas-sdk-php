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
    private $baseURL = "https://api.dev.miracl.net";
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
                $this->refreshUserData($token);
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

    public function refreshUserData()
    {
        unset($_SESSION['miracl_sub']);
        unset($_SESSION['miracl_email']);
        $data = $this->requestUserInfo($_SESSION["miracl_access_token"]);

        if (array_key_exists('sub', $data)) {
            $_SESSION['miracl_sub'] = $data->sub;
        } else {
            $this->logout();
            return;
        }

        if (array_key_exists('email', $data)) {
            $_SESSION['miracl_email'] = $data->email;
        } else {
            $_SESSION['miracl_email'] = "";
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
     * @return string User id
     */
    public function getUserID()
    {
        if (isset($_SESSION['miracl_sub'])) {
            return $_SESSION['miracl_sub'];
        }
    }

    /**
     * @return string User e-mail
     */
    public function getEmail()
    {
        if (isset($_SESSION['miracl_email'])) {
            return $_SESSION['miracl_email'];
        } else {
            return "";
        }
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

    private function requestUserInfo($accessToken)
    {
        $user_info_endpoint = $this->getProviderConfigValue("userinfo_endpoint");
        $schema = 'openid';

        $user_info_endpoint .= "?schema=" . $schema;

        //The accessToken has to be send in the Authorization header, so we create a new array with only this header.
        $headers = array("Authorization: Bearer {$accessToken}");

        $user_json = json_decode($this->fetchURL($user_info_endpoint, null, $headers));

        return $user_json;

    }

    private function fetchURL($url, $post_body = null, $headers = array())
    {
        // OK cool - then let's create a new cURL resource handle
        $ch = curl_init();

        // Determine whether this is a GET or POST
        if ($post_body != null) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);

            // Default content type is form encoded
            $content_type = 'application/x-www-form-urlencoded';

            // Determine if this is a JSON payload and add the appropriate content type
            if (is_object(json_decode($post_body))) {
                $content_type = 'application/json';
            }

            // Add POST-specific headers
            $headers[] = "Content-Type: {$content_type}";
            $headers[] = 'Content-Length: ' . strlen($post_body);

        }

        // If we set some heaers include them
        if (count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Set URL to download
        curl_setopt($ch, CURLOPT_URL, $url);

        if (isset($this->httpProxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $this->httpProxy);
        }

        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($ch, CURLOPT_HEADER, 0);

        /**
         * Set cert
         * Otherwise ignore SSL peer verification
         */
        if (isset($this->certPath)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $this->certPath);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        }

        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        // Download the given URL, and return output
        $output = curl_exec($ch);

        if ($output === false) {
            throw new OpenIDConnectClientException('Curl error: ' . curl_error($ch));
        }

        // Close the cURL resource, and free system resources
        curl_close($ch);

        return $output;
    }

}