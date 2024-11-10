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

final class UpdateTest extends TestCase
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
        'user_id' => $data['user']['id'],
        'title' => 'Buy Groceries',
        'description' => 'Buy milk, eggs, and bread.',
        'status' => 'todo',
      ]
    ];
  }

  private function validator(array $data): object
  {
    return Validator::setRules($data, [
      'title' => ['required', 'min_length:3', 'max_length:100'],
      'description' => ['required', 'max_length:1000'],
    ]);
  }

  private function response(Request $request, object $validator, array $data, int $id): void
  {
    $this->mock->reset();

    $authorization = explode(' ', $request->getHeader('Authorization')[0]);
    $token_type = $authorization[0];
    $access_token = $authorization[1];
    $decode = MyJWT::decode($access_token);

    if ($token_type !== 'Bearer' || !$decode) {
      $body = json_encode(['message' => 'Unauthorized']);
      $this->mock->append(new Response(401, ['Content-type' => 'application/json'], $body));
    } elseif ($validator->hasValidationErrors()) {
      $body = json_encode(['errors' => $validator->getValidationErrors()]);
      $this->mock->append(new Response(422, ['Content-type' => 'application/json'], $body));
    } else {
      $this->todos[$id]['title'] = $data['title'];
      $this->todos[$id]['description'] = $data['description'];

      $this->mock->append(new Response(201, ['Content-type' => 'application/json'], json_encode([
        'message' => 'Update successful',
        'data' => [
          'id' => $this->todos[$id]['id'],
          'title' => $this->todos[$id]['title'],
          'description' => $this->todos[$id]['description'],
          'status' => $this->todos[$id]['status'],
        ],
      ])));
    }
  }

  private function getId(Request $request): int
  {
    $parsedUri = explode('/', strval($request->getUri()));
    return intval($parsedUri[count($parsedUri) - 1]);
  }

  public function testSuccess(): void
  {
    $input = json_encode([
      'title' => 'Buy Groceries',
      'description' => 'Buy milk, eggs, bread, and cheese.',
    ]);

    $request = new Request('PUT', 'http://todo-list-api.test/todo/1', [
      'Authorization' => 'Bearer ' . $this->access_token,
      'Content-type' => 'application/json',
    ], $input);

    $data = json_decode(strval($request->getBody()), true);

    $validator = $this->validator($data);
    $id = $this->getId($request);
    $this->response($request, $validator, $data, $id);
    $response = $this->client->send($request);
    $body = json_decode($response->getBody()->getContents(), true);

    $this->assertEquals(201, $response->getStatusCode());
    $this->assertArrayHasKey('message', $body);
    $this->assertArrayHasKey('data', $body);
    $this->assertArrayHasKey('id', $body['data']);
    $this->assertArrayHasKey('title', $body['data']);
    $this->assertArrayHasKey('description', $body['data']);
    $this->assertArrayHasKey('status', $body['data']);
    $this->assertSame('Update successful', $body['message']);

    foreach ($data as $key => $value) {
      $this->assertSame($value, $this->todos[$id][$key]);
      $this->assertSame($value, $body['data'][$key]);
    }
  }

  public function testRequiredValidationErrors(): void
  {
    $input = json_encode([]);

    $request = new Request('PUT', 'http://todo-list-api.test/todo/1', [
      'Authorization' => 'Bearer ' . $this->access_token,
      'Content-type' => 'application/json',
    ], $input);

    $data = json_decode(strval($request->getBody()), true);
    $validator = $this->validator($data);
    $id = $this->getId($request);
    $this->response($request, $validator, $data, $id);

    try {
      $this->client->send($request);
    } catch (ClientException $e) {
      $this->assertEquals(422, $e->getResponse()->getStatusCode());
      $body = json_decode($e->getResponse()->getBody()->getContents(), true);

      $this->assertArrayHasKey('errors', $body);
      $this->assertArrayHasKey('title', $body['errors']);
      $this->assertArrayHasKey('description', $body['errors']);
      $this->assertSame('title field is required.', $body['errors']['title']);
      $this->assertSame('description field is required.', $body['errors']['description']);
    }
  }

  public function testUnauthorized(): void
  {
    $input = json_encode([
      'title' => 'Buy Groceries',
      'description' => 'Buy milk, eggs, and bread.',
    ]);

    $request = new Request('PUT', 'http://todo-list-api.test/todo/1', [
      'Authorization' => 'Bearer abc123',
      'Content-type' => 'application/json',
    ], $input);

    $data = json_decode(strval($request->getBody()), true);
    $validator = $this->validator($data);
    $id = $this->getId($request);
    $this->response($request, $validator, $data, $id);

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
