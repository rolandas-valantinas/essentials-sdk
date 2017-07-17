<?php

namespace JTDSoft\EssentialsSdk\Core;

use GuzzleHttp\Client as Guzzle;
use JTDSoft\EssentialsSdk\Contracts\Client;
use JTDSoft\EssentialsSdk\Exceptions\ErrorException;

/**
 * Class Service
 *
 * @package JTDSoft\EssentialsSdk\Services
 */
class Service extends Config
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers ?: [];
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $header
     * @param string $value
     */
    public function setHeader(string $header, string $value)
    {
        $this->headers[$header] = $value;
    }

    /**
     * @param string $header
     * @param string $value
     */
    public function setCustomHeader(string $header, string $value)
    {
        $this->headers[self::$custom_header_prefix . $header] = $value;
    }

    /**
     * Adds all headers
     */
    protected function prepareHeaders()
    {
        $this->setCustomHeader('Version', static::getVersion());
        $this->setHeader('Accept-Language', static::getLanguage());
        $this->setHeader('Authorization', 'Bearer ' . static::getApiKey());
    }

    /**
     * @param string $cast
     * @param        $response
     *
     * @return mixed
     */
    public final function cast(string $cast, $response)
    {
        return new $cast($response);
    }

    /**
     * @param       $cast
     * @param array ...$parameters
     *
     * @return mixed
     */
    public final function callAndCast($cast, ...$parameters)
    {
        return $this->cast($cast, $this->call(...$parameters));
    }

    /**
     * @param       $cast
     * @param array ...$parameters
     *
     * @return mixed
     */
    public final function getAndCast($cast, ...$parameters)
    {
        return $this->cast($cast, $this->get(...$parameters));
    }

    /**
     * @param       $cast
     * @param array ...$parameters
     *
     * @return mixed
     */
    public final function postAndCast($cast, ...$parameters)
    {
        return $this->cast($cast, $this->post(...$parameters));
    }

    /**
     * @param       $cast
     * @param array ...$parameters
     *
     * @return mixed
     */
    public final function putAndCast($cast, ...$parameters)
    {
        return $this->cast($cast, $this->put(...$parameters));
    }

    /**
     * @param       $cast
     * @param array ...$parameters
     *
     * @return mixed
     */
    public final function patchAndCast($cast, ...$parameters)
    {
        return $this->cast($cast, $this->put(...$parameters));
    }

    /**
     * @param       $cast
     * @param array ...$parameters
     *
     * @return mixed
     */
    public final function deleteAndCast($cast, ...$parameters)
    {
        return $this->cast($cast, $this->delete(...$parameters));
    }

    /**
     * @param       $verb
     * @param       $method
     * @param array $request
     *
     * @return mixed
     * @throws \JTDSoft\EssentialsSdk\Exceptions\ErrorException
     * @throws \JTDSoft\EssentialsSdk\Exceptions\ResponseException
     * @throws \JTDSoft\EssentialsSdk\Exceptions\ServerException
     * @throws \JTDSoft\EssentialsSdk\Exceptions\UnauthorizedException
     */
    public final function call($verb, $method, array $request = null)
    {
        if (is_null($request)) {
            $request = [];
        }

        $this->prepareHeaders();

        $client = new GuzzleClient(new Guzzle([
            'proxy'  => static::getProxy(),
            'verify' => static::verify(),
        ]));

        return $client->request(
            $verb,
            $this->getUrl($method),
            $request,
            $this->getHeaders()
        );
    }

    /**
     * @param            $method
     * @param array|null $request
     *
     * @return mixed
     */
    public final function get($method, array $request = null)
    {
        return $this->call('get', $method, $request);
    }

    /**
     * @param            $method
     * @param array|null $request
     *
     * @return mixed
     */
    public final function post($method, array $request = null)
    {
        return $this->call('post', $method, $request);
    }

    /**
     * @param            $method
     * @param array|null $request
     *
     * @return mixed
     */
    public final function put($method, array $request = null)
    {
        return $this->call('put', $method, $request);
    }

    /**
     * @param            $method
     * @param array|null $request
     *
     * @return mixed
     */
    public final function patch($method, array $request = null)
    {
        return $this->call('patch', $method, $request);
    }

    /**
     * @param            $method
     * @param array|null $request
     *
     * @return mixed
     */
    public final function delete($method, array $request = null)
    {
        return $this->call('delete', $method, $request);
    }

    /**
     * @param string $method
     *
     * @return string
     */
    private function getUrl($method)
    {
        $endpoint = sprintf(
            '%s://%s/',
            static::getProtocol(),
            static::getEndpoint(),
            $method
        );

        return $endpoint;
    }

    /**
     * @param string $cast
     * @param array  ...$parameters
     *
     * @return mixed
     */
    public final function callAndCastMany($cast, ...$parameters)
    {
        return $this->castMany($cast, $this->call(...$parameters));
    }

    /**
     * @param      $response
     * @param null $cast
     *
     * @return mixed
     * @throws ErrorException
     */
    public final function castMany($cast, $response)
    {
        $result = [];

        if (!$response) {
            return $result;
        }

        if (isset($response['total']) && isset($response['data'])) { //for paging
            $paging = new Paging();

            $paging->setPage($response['current_page']);
            $paging->setTotal($response['total']);
            $paging->setLastPage($response['last_page']);
            $paging->setFrom($response['from']);
            $paging->setTo($response['to']);
            $paging->setItems($this->castMany($cast, $response['data']));

            return $paging;
        }

        foreach ($response as $key => $value) {
            $result[] = self::cast($cast, $value);
        }

        return new Collection($result);
    }
}
