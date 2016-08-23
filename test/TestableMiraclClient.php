<?php

namespace Test;

class TestableMiraclClient extends \Com\Miracl\MaasSdk\MiraclClient
{
    /**
     * @var string
     */
    private $mockClient;

    /**
     * @var string
     */
    private $urlResponse;

    public function __construct($mockClient, $urlResponse = '')
    {
        $this->mockClient = $mockClient;
        $this->urlResponse = $urlResponse;
        parent::__construct('', '', '', '');
    }

    public function createOpenIDConnectClient($ignored1, $ignored2, $ignored3)
    {
        $ignored1 = null;
        $ignored2 = null;
        $ignored3 = null;
        return $this->mockClient;
    }

    protected function fetchURL($url, $post_body = null, $headers = array())
    {
        $url = null;
        $post_body = null;
        $headers = array();
        return $this->urlResponse;
    }
}
