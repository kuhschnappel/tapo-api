<?php
declare(strict_types = 1);

namespace Kuhschnappel\TapoApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Response;
use phpseclib3\Crypt\AES;
use Psr\Http\Message\ResponseInterface;

trait TapoCommunication
{

    /**
     * @var string $token authentification token
     */
    private $token = null;

    /**
     * @var Client $httpClient guzzle object for client
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
     * @var string $error
     */
    private $error = null;

    /**
     * @var int $status
     */
    private $status = self::STATUS_INIT;

    /**
     * @param string $user
     * @param string $password
     * @param string $host
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function initCommunication(string $user, string $password, string $host)
    {

        $this->httpClient = new Client([
            'base_uri' => $host,
            'cookies' => true,
            'timeout' => 5,
            'connect_timeout' => 5
        ]);

        $this->handshake();
        $this->login($user, $password);
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getResponse(string $method = 'GET', string $uri = '/app', array $options = [] ) : ?ResponseInterface
    {
        try {
            /** @var ResponseInterface $response */
            $response = $this->httpClient->request($method, $uri, $options);
        } catch (ClientErrorResponseException $e) {
            $this->error = "Client error: " . $e->getResponse()->getBody(true);
        } catch (ServerErrorResponseException $e) {
            $this->error = "Server error: " . $e->getResponse()->getBody(true);
        } catch (BadResponseException $e) {
            $this->error = "BadResponse error: " . $e->getResponse()->getBody(true);
        } catch (\Exception $e) {
            $this->error = "Error: " . $e->getMessage();
        }

        if ($this->error === null)
            return $response;

        return null;
    }
    
    /**
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

        $response = $this->getResponse('POST', '/app', [RequestOptions::JSON => $payload]);
        if (!$response)
            return;

        $this->setStatus(self::STATUS_HANDSHAKE_SUCESSFULL);

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
        if ($this->getStatus() !== self::STATUS_HANDSHAKE_SUCESSFULL)
            return;

        $payload = [
            'method' => 'login_device',
            'params' => [
                'password' => base64_encode($password),
                'username' => base64_encode(sha1($user))
            ],
            'requestTimeMils' => 0,
        ];

        $response = $this->getResponse('POST', '/app', [RequestOptions::JSON => $this->getSecuredPayload($payload)]);
        if (!$response)
            return;
        
        $data = $this->decryptResponse($response);

        if ($data === null)
            return null;

        $this->setStatus(self::STATUS_LOGIN_SUCESSFULL);

        $this->token = $data->result->token;
    }


    /**
     * @param string $method
     * @param array|null $params
     * @return object|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendCommand(string $method, array $params = null) : ?object {

        if ($this->getStatus() !== self::STATUS_LOGIN_SUCESSFULL)
            return null;

        $payload = [
            'method' => $method,
            'requestTimeMils' => 0
        ];

        if ($params) {
            $payload['params'] = $params;
        }

        $response = $this->getResponse('POST', '/app?token=' . $this->token, [RequestOptions::JSON => $this->getSecuredPayload($payload)]);

        if (!$response)
            return null;

        return $this->decryptResponse($response);

    }

    /**
     * @param Response $response
     * @return object|null
     */
    private function decryptResponse(Response $response) : ?object
    {
        $contentJson = json_decode($response->getBody()->getContents());
        if ($contentJson == null) {
            $this->error = "Response error: JSON object expected.";
            return null;
        }

        if ($contentJson->error_code !== 0) {
            $this->error = "Response error code: " . $contentJson->error_code;
            return null;
        }

        $contentJsonBase64Decode = base64_decode($contentJson->result->response);
        $data = $this->aes->decrypt($contentJsonBase64Decode);
        $dataJson = json_decode($data);
        if ($dataJson == null) {
            $this->error = "Response decrypt error: JSON object expected.";
            return null;
        }

        if ($dataJson->error_code !== 0) {
            switch ($dataJson->error_code) {
                case -1501:
                    $this->error = "Response error code: Wrong Username or password ({$dataJson->error_code})";
                    break;
                default:
                    $this->error = "Response error code: {$dataJson->error_code}";
                    break;
            }
            return null;
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