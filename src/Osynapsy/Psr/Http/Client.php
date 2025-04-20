<?php

namespace Osynapsy\Psr\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class Client implements ClientInterface
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private UriFactoryInterface $uriFactory;
    private string $baseUrl;
    private array $defaultHeaders;

    /**
     * Constructor del client REST
     *
     * @param ClientInterface $httpClient Il client HTTP PSR-18
     * @param RequestFactoryInterface $requestFactory La factory per le richieste PSR-7
     * @param StreamFactoryInterface $streamFactory La factory per gli stream PSR-7
     * @param UriFactoryInterface $uriFactory La factory per gli URI PSR-7
     * @param string $baseUrl La URL base per tutte le richieste
     * @param array $defaultHeaders Gli header predefiniti da inviare con ogni richiesta
     */
    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        string $baseUrl = '',
        array $defaultHeaders = []
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->defaultHeaders = $defaultHeaders;
    }

    /**
     * Invia una richiesta HTTP e ritorna la risposta
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Esegue una richiesta GET
     *
     * @param string $path Il percorso della richiesta
     * @param array $queryParams I parametri di query string
     * @param array $headers Gli header aggiuntivi
     * @return ResponseInterface
     */
    public function get(string $path, array $queryParams = [], array $headers = []): ResponseInterface
    {
        $uri = $this->createUri($path, $queryParams);
        $request = $this->requestFactory->createRequest('GET', $uri);
        $request = $this->addHeaders($request, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Esegue una richiesta POST
     *
     * @param string $path Il percorso della richiesta
     * @param mixed $body Il corpo della richiesta
     * @param array $headers Gli header aggiuntivi
     * @return ResponseInterface
     */
    public function post(string $path, $body = null, array $headers = []): ResponseInterface
    {
        $uri = $this->createUri($path);
        $request = $this->requestFactory->createRequest('POST', $uri);
        $request = $this->addHeaders($request, $headers);

        if ($body !== null) {
            $request = $this->addBody($request, $body);
        }

        return $this->sendRequest($request);
    }

    /**
     * Esegue una richiesta PUT
     *
     * @param string $path Il percorso della richiesta
     * @param mixed $body Il corpo della richiesta
     * @param array $headers Gli header aggiuntivi
     * @return ResponseInterface
     */
    public function put(string $path, $body = null, array $headers = []): ResponseInterface
    {
        $uri = $this->createUri($path);
        $request = $this->requestFactory->createRequest('PUT', $uri);
        $request = $this->addHeaders($request, $headers);

        if ($body !== null) {
            $request = $this->addBody($request, $body);
        }

        return $this->sendRequest($request);
    }

    /**
     * Esegue una richiesta PATCH
     *
     * @param string $path Il percorso della richiesta
     * @param mixed $body Il corpo della richiesta
     * @param array $headers Gli header aggiuntivi
     * @return ResponseInterface
     */
    public function patch(string $path, $body = null, array $headers = []): ResponseInterface
    {
        $uri = $this->createUri($path);
        $request = $this->requestFactory->createRequest('PATCH', $uri);
        $request = $this->addHeaders($request, $headers);

        if ($body !== null) {
            $request = $this->addBody($request, $body);
        }

        return $this->sendRequest($request);
    }

    /**
     * Esegue una richiesta DELETE
     *
     * @param string $path Il percorso della richiesta
     * @param array $headers Gli header aggiuntivi
     * @return ResponseInterface
     */
    public function delete(string $path, array $headers = []): ResponseInterface
    {
        $uri = $this->createUri($path);
        $request = $this->requestFactory->createRequest('DELETE', $uri);
        $request = $this->addHeaders($request, $headers);

        return $this->sendRequest($request);
    }

    /**
     * Crea un URI completo per la richiesta
     *
     * @param string $path Il percorso relativo
     * @param array $queryParams I parametri di query string
     * @return \Psr\Http\Message\UriInterface
     */
    private function createUri(string $path, array $queryParams = [])
    {
        $path = ltrim($path, '/');
        $url = $this->baseUrl ? "{$this->baseUrl}/{$path}" : $path;

        $uri = $this->uriFactory->createUri($url);

        if (!empty($queryParams)) {
            $query = http_build_query($queryParams);
            $uri = $uri->withQuery($query);
        }

        return $uri;
    }

    /**
     * Aggiunge gli header alla richiesta
     *
     * @param RequestInterface $request
     * @param array $headers
     * @return RequestInterface
     */
    private function addHeaders(RequestInterface $request, array $headers): RequestInterface
    {
        // Aggiungiamo prima gli header di default
        foreach ($this->defaultHeaders as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        // Poi aggiungiamo gli header specifici che possono sovrascrivere i default
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    /**
     * Aggiunge il corpo alla richiesta
     *
     * @param RequestInterface $request
     * @param mixed $body
     * @return RequestInterface
     */
    private function addBody(RequestInterface $request, $body): RequestInterface
    {
        if (is_string($body)) {
            $stream = $this->streamFactory->createStream($body);
            return $request->withBody($stream);
        }

        if (is_array($body) || is_object($body)) {
            $json = json_encode($body);

            if ($json === false) {
                throw new \InvalidArgumentException('Impossibile codificare il corpo in JSON');
            }

            $stream = $this->streamFactory->createStream($json);

            // Se non è già impostato l'header Content-Type, lo impostiamo
            if (!$request->hasHeader('Content-Type')) {
                $request = $request->withHeader('Content-Type', 'application/json');
            }

            return $request->withBody($stream);
        }

        throw new \InvalidArgumentException('Formato del corpo non supportato');
    }
}
