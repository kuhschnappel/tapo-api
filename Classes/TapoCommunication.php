<?php
declare(strict_types = 1);

namespace Kuhschnappel\TapoApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Response;
use phpseclib3\Crypt\AES;

trait TapoCommunication
{

    /**
     * @var string $token authentification token
     */
    private $token = null;

    /**
     * @var object $httpClient guzzle object for client
     */
    private $httpClient = null;

    /**
     * @var OpenSSLAsymmetricKey $privateKey
     */
    private $privateKey = null;

    /**
     * @var AES $aes
     */
    private $aes = null;

    /**
     * @param string $user
     * @param string $password
     * @param string $host
     * @return void
     */
    public function initCommunication(string $user, string $password, string $host)
    {

        $this->httpClient = new Client([
            'base_uri' => $host,
            'cookies' => true,
        ]);

        $this->handshake();
        $this->login($user, $password);

    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function handshake()
    {

        // generate private key
        $this->privateKey = openssl_pkey_new([
            "digest_alg" => "sha512",
            "private_key_bits" => 1024,
            "private_key_type" => OPENSSL_KEYTYPE_RSA]
        );
        $privateKeyDetails = openssl_pkey_get_details($this->privateKey);

        $payload = [
            'method' => 'handshake',
            'params' => [
                'key' => $privateKeyDetails['key'],
                'requestTimeMils' => 0
            ]
        ];

        $response = $this->httpClient->request(
            'POST',
            '/app',
            [
                RequestOptions::JSON => $payload,
            ]
        );

        $content = json_decode($response->getBody()->getContents());
        $handshakeKey = base64_decode($content->result->key);

        if (openssl_private_decrypt($handshakeKey, $doFinal,$this->privateKey,OPENSSL_PKCS1_PADDING) == false) {
            throw new Exception('Decryption of handshake key failed.');
        }

        $b_arr = [];
        $b_arr2 = [];
        for ($i=0;$i<16;$i++) {
            $b_arr[] = $doFinal[$i];
        }
        for ($i=0;$i<16;$i++) {
            $b_arr2[] = $doFinal[$i+16];
        }

        $this->aes = new AES('cbc');
        $this->aes->setKey(implode('', $b_arr));
        $this->aes->setIV(implode('', $b_arr2));

    }

    /**
     * @param array $payload
     * @return array
     */
    private function getSecuredPayload(array $payload) : array
    {
        return [
            'method' => 'securePassthrough',
            'params' => [
                'request' =>  $this->encryptPayload($payload),
            ]
        ];
    }

    /**
     * @param string $user
     * @param string $password
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function login(string $user, string $password)
    {
        $payload = [
            'method' => 'login_device',
            'params' => [
                'password' => base64_encode($password),
                'username' => base64_encode(sha1($user))
            ],
            'requestTimeMils' => 0,
        ];

        $response = $this->httpClient->request(
            'POST',
            '/app',
            [
                RequestOptions::JSON => $this->getSecuredPayload($payload),
            ]
        );
        $data = $this->decryptResponse($response);

        $this->token = $data->result->token;
    }


    /**
     * @param string $method
     * @param array|null $params
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendCommand(string $method, array $params = null) : object {

        $payload = [
            'method' => $method,
            'requestTimeMils' => 0
        ];

        if ($params) {
            $payload['params'] = $params;
        }

        $response = $this->httpClient->request(
            'POST',
            '/app?token=' . $this->token,
            [
                RequestOptions::JSON => $this->getSecuredPayload($payload),
            ]
        );
        return $this->decryptResponse($response);

    }
    
    /**
     * @param Response $response
     * @return array
     */
    private function decryptResponse(Response $response) : object
    {
        $contentJson = json_decode($response->getBody()->getContents());
        if ($contentJson == null) {
            throw new Exception('Invalid response, JSON object expected.');
        }

        $contentJsonBase64Decode = base64_decode($contentJson->result->response);
        $data = $this->aes->decrypt($contentJsonBase64Decode);
        $dataJson = json_decode($data);
        if ($dataJson == null) {
            throw new Exception('Decoding failed, JSON object expected.');
        }

        return $dataJson;
    }

    /**
     * @param array $payload
     * @return string
     */
    private function encryptPayload(array $payload) : string
    {
        $payloadEncrypted = $this->aes->encrypt(json_encode($payload));
        return base64_encode($payloadEncrypted);
    }

}