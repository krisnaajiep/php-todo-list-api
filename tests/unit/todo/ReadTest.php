<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\RequestException;

final class ReadTest extends TestCase
{
  private $mock, $client, $access_token, $todos;

  public function setUp(): void
  {
    $this->mock = new MockHandler([
      new Response(200, ['Content-type' => 'application/json'], 'OK'),
      new Response(202, ['Content-Length' => 0]),
      new RequestException('Error Communicating with Server', new Request('GET', 'test'))
    ]);

    $handlerStack = HandlerStack::create($this->mock);
    $this->client = new Client(['handler' => $handlerStack]);

    $data = [
      'user' => [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@doe.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
      ]
    ];

    $_ENV['JWT_SECRET'] = 'jwt_secret';
    $this->access_token = MyJWT::encode($data, 3600, true);

    $this->todos = [
      1 => [
        'id' => 1,
        'user_id' => 1,
        'title' => 'Buy Groceries',
        'description' => 'Buy milk, eggs, and bread.',
        'status' => 'done',
      ],
      2 => [
        'id' => 2,
        'user_id' => 1,
        'title' => 'Pay bills',
        'description' => 'Pay electricity and water bills',
        'status' => 'in progress',
      ],
    ];
  }

  private function response(Request $request): void
  {
    $this->mock->reset();

    $authorization = explode(' ', $request->getHeader('Authorization')[0]);
    $token_type = $authorization[0];
    $access_token = $authorization[1];
    $decode = MyJWT::decode($access_token);

    if ($token_type !== 'Bearer' || !$decode) {
      $body = json_encode(['message' => 'Unauthorized']);
      $this->mock->append(new Response(401, ['Content-type' => 'application/json'], $body));
    } else {
      $count = count($this->todos);
      Pagination::setLimit($_GET['limit'] ?? $count);

      $body = json_encode([
        'data' => $this->todos,
        'page' => empty($_GET['page']) ? 1 : intval($_GET['page']),
        'limit' => Pagination::getLimit(),
        'total' => $count,
      ]);

      $this->mock->append(new Response(200, ['Content-type' => 'application/json'], $body));
    }
  }

  private function getId(Request $request): int
  {
    $parsedUri = explode('/', strval($request->getUri()));
    return intval($parsedUri[count($parsedUri) - 1]);
  }

  private function setQueryParams(string $string)
  {
    $explode = explode('&', $string);

    foreach ($explode as $value) {
      $string = explode('=', $value);
      $_GET[$string[0]] = $string[1];
    }
  }

  public function testSuccess(): void
  {
    $request = new Request('GET', 'http://todo-list-api.test/todo', [
      'Authorization' => 'Bearer ' . $this->access_token,
      'Content-type' => 'application/json',
    ]);

    $id = $this->getId($request);
    $this->response($request, $id);
    $response = $this->client->send($request);
    $body = json_decode($response->getBody()->getContents(), true);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertArrayHasKey('data', $body);
    $this->assertArrayHasKey('page', $body);
    $this->assertArrayHasKey('limit', $body);
    $this->assertArrayHasKey('total', $body);
    $this->assertSame($this->todos, $body['data']);
    $this->assertSame(1, $body['page']);
    $this->assertSame(count($this->todos), $body['limit']);
    $this->assertSame(count($this->todos), $body['total']);
  }

  public function testSuccessWithPagination(): void
  {
    $request = new Request('GET', 'http://todo-list-api.test/todo/?page=1&limit=10', [
      'Authorization' => 'Bearer ' . $this->access_token,
      'Content-type' => 'application/json',
    ]);

    $this->setQueryParams($request->getUri()->getQuery());

    $id = $this->getId($request);
    $this->response($request, $id);
    $response = $this->client->send($request);
    $body = json_decode($response->getBody()->getContents(), true);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertArrayHasKey('data', $body);
    $this->assertArrayHasKey('page', $body);
    $this->assertArrayHasKey('limit', $body);
    $this->assertArrayHasKey('total', $body);
    $this->assertSame($this->todos, $body['data']);
    $this->assertSame(intval($_GET['page']), $body['page']);
    $this->assertSame(intval($_GET['limit']), $body['limit']);
    $this->assertSame(count($this->todos), $body['total']);
  }

  public function testUnauthorized(): void
  {
    $request = new Request('GET', 'http://todo-list-api.test/todo', [
      'Authorization' => 'Bearer abc123',
      'Content-type' => 'application/json',
    ]);

    $id = $this->getId($request);
    $this->response($request, $id);

    try {
      $this->client->send($request);
    } catch (ClientException $e) {
      $this->assertEquals(401, $e->getResponse()->getStatusCode());
      $body = json_decode($e->getResponse()->getBody()->getContents(), true);

      $this->assertArrayHasKey('message', $body);
      $this->assertSame('Unauthorized', $body['message']);
    }
  }
}
