<?php

use phpunit\Framework\TestCase;

$_SESSION = array();
$_REQUEST = array();

class TestableMiraclClient extends MiraclClient
{

    /**
     * @var string
     */
    private $mockClient;
    /**
     * @var string
     */
    private $urlResponse;

    public function __construct($mockClient, $urlResponse = "")
    {
        $this->mockClient = $mockClient;
        $this->urlResponse = $urlResponse;
        parent::__construct("", "", "");
    }

    public function createOpenIDConnectClient($ignored1, $ignored2, $ignored3)
    {
        return $this->mockClient;
    }

    protected function fetchURL($url, $post_body = null, $headers = array())
    {
        return $this->urlResponse;
    }


}

class MiraclClientTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        //Clean state
        $_SESSION = array();
        $_REQUEST = array();
    }

    public function testLogout()
    {
        $_SESSION['miracl_access_token'] = "X";
        $_SESSION['miracl_email'] = "X";
        $_SESSION['miracl_sub'] = "X";

        $mockClient = $this->createMock(OpenIDConnectClient::class);
        $mockClient->method("providerConfigParam")->willReturn("");
        $client = new TestableMiraclClient($mockClient);
        $client->logout();
        $this->assertFalse(isset($_SESSION['miracl_access_token']));
        $this->assertFalse(isset($_SESSION['miracl_email']));
        $this->assertFalse(isset($_SESSION['miracl_sub']));
    }


    public function testAuthRequestURL()
    {
        $mockClient = $this->createMock(OpenIDConnectClient::class);
        $mockClient->method("providerConfigParam")->willReturn("");
        $client = new TestableMiraclClient($mockClient);
        $url = $client->getAuthURL();
        $this->assertArrayHasKey("openid_connect_nonce", $_SESSION);
        $this->assertArrayHasKey("openid_connect_state", $_SESSION);
        $nonce = $_SESSION['openid_connect_nonce'];
        $state = $_SESSION['openid_connect_state'];
        $this->assertTrue(strpos($url, "nonce=$nonce") !== false);
        $this->assertTrue(strpos($url, "state=$state") !== false);
    }

    public function testGoodToken()
    {
        $mockClient = $this->createMock(OpenIDConnectClient::class);
        $mockClient->method("providerConfigParam")->willReturn("");
        $mockClient->method("authenticate")->willReturn(true);
        $mockClient->method("getAccessToken")->willReturn("TOKEN");


        $client = new TestableMiraclClient($mockClient, json_encode(array("sub" => "MOCK_SUB", "email" => "MOCK_EMAIL")));
        $client->getAuthURL();
        $nonce = $_SESSION['openid_connect_nonce'];
        $state = $_SESSION['openid_connect_state'];

        $_REQUEST["code"] = "MOCK_CODE";
        $_REQUEST["nonce"] = $nonce;
        $_REQUEST["state"] = $state;

        $this->assertTrue($client->validateAuthorization());
        $this->assertEquals("MOCK_EMAIL", $client->getEmail());
        $this->assertEquals("MOCK_SUB", $client->getUserID());
    }

    public function testEmptyAuthorization()
    {
        $mockClient = $this->createMock(OpenIDConnectClient::class);
        $mockClient->method("providerConfigParam")->willReturn("");

        $client = new TestableMiraclClient($mockClient);

        $this->assertFalse($client->validateAuthorization());
    }

}