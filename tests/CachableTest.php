<?php

namespace hamburgscleanest\GuzzleAdvancedThrottle\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use hamburgscleanest\GuzzleAdvancedThrottle\Middleware\ThrottleMiddleware;
use hamburgscleanest\GuzzleAdvancedThrottle\RequestLimitRuleset;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Class CachableTest
 * @package hamburgscleanest\GuzzleAdvancedThrottle\Tests
 */
class CachableTest extends TestCase
{

    /** @test
     * @throws \Exception
     */
    public function dont_cache_error_responses() : void
    {
        $host = 'www.test.com';
        $ruleset = new RequestLimitRuleset(
            [
                [
                    'host'         => $host,
                    'max_requests' => 1
                ]
            ],
            'cache');
        $throttle = new ThrottleMiddleware($ruleset);
        $stack = new MockHandler([new Response(500), new Response()]);
        $client = new Client(['base_uri' => $host, 'handler' => $throttle->handle()($stack)]);

        $client->request('GET', '/');

        $this->expectException(TooManyRequestsHttpException::class);

        $client->request('GET', '/');
    }

    /** @test */
    public function respects_request_parameters() : void
    {
        $host = 'www.test.com';
        $query = 'test';
        $ruleset = new RequestLimitRuleset(
            [
                [
                    'host'         => $host,
                    'max_requests' => 1
                ]
            ],
            'cache');
        $throttle = new ThrottleMiddleware($ruleset);
        $stack = new MockHandler([new Response(), new Response(), new Response()]);
        $client = new Client(['base_uri' => $host, 'handler' => $throttle->handle()($stack)]);

        $client->request('GET', 'test?query=' . $query);
        $client->request('GET', 'test?query=' . $query); // should get cached response
        $this->expectException(TooManyRequestsHttpException::class);
        $client->request('GET', 'test?query=different');
    }

    /** @test */
    public function respects_body_parameters() : void
    {
        $host = 'www.test.com';
        $params = ['some_param' => 'test'];
        $ruleset = new RequestLimitRuleset(
            [
                [
                    'host'         => $host,
                    'max_requests' => 1
                ]
            ],
            'cache');
        $throttle = new ThrottleMiddleware($ruleset);
        $stack = new MockHandler([new Response(), new Response(), new Response()]);
        $client = new Client(['base_uri' => $host, 'handler' => $throttle->handle()($stack)]);

        $client->request('POST', 'test', ['form_params' => $params]);
        $client->request('POST', 'test', ['form_params' => $params]); // should get cached response
        $this->expectException(TooManyRequestsHttpException::class);
        $client->request('POST', 'test', ['form_params' => ['another_param' => 'different']]);
    }

}