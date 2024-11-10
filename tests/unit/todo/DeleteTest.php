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

final class DeleteTest extends TestCase
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
        'status' => 'todo',
      ]
    ];
  }

  private function response(Request $request, int $id): void
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
      unset($this->todos[$id]);
      $this->mock->append(new Response(204));
    }
  }

  private function getId(Request $request): int
  {
    $parsedUri = explode('/', strval($request->getUri()));
    return intval($parsedUri[count($parsedUri) - 1]);
  }

  public function testSuccess(): void
  {
    $request = new Request('DELETE', 'http://todo-list-api.test/todo/0', [
      'Authorization' => 'Bearer ' . $this->access_token,
      'Content-type' => 'application/json',
    ]);

    $id = $this->getId($request);
    $this->response($request, $id);
    $response = $this->client->send($request);

    $this->assertEquals(204, $response->getStatusCode());
    $this->assertTrue(!isset($this->todos[$id]));
  }

  public function testUnauthorized(): void
  {
    $request = new Request('DELETE', 'http://todo-list-api.test/todo/0', [
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
