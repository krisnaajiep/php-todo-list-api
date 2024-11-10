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

final class LoginTest extends TestCase
{
  private $mock, $client;

  public function setUp(): void
  {
    $this->mock = new MockHandler([
      new Response(200, ['Content-type' => 'application/json'], 'OK'),
      new Response(202, ['Content-Length' => 0]),
      new RequestException('Error Communicating with Server', new Request('GET', 'test'))
    ]);

    $handlerStack = HandlerStack::create($this->mock);
    $this->client = new Client(['handler' => $handlerStack]);
  }

  private function validator(array $credentials): object
  {
    return Validator::setRules($credentials, [
      'email' => ['required', 'email'],
      'password' => ['required'],
    ]);
  }

  private function response(object $validator, array $data): void
  {
    $this->mock->reset();

    $registeredUser = [
      'id' => 1,
      'name' => 'John Doe',
      'email' => 'john@doe.com',
      'password' => password_hash('password', PASSWORD_DEFAULT),
    ];

    if ($validator->hasValidationErrors()) {
      $body = json_encode(['errors' => $validator->getValidationErrors()]);
      $this->mock->append(new Response(422, ['Content-type' => 'application/json'], $body));
    } elseif (
      $data['user']['email'] !== $registeredUser['email'] ||
      !password_verify($data['user']['password'], $registeredUser['password'])
    ) {
      $this->mock->append(new Response(401, ['Content-type' => 'application/json'], json_encode([
        'message' => 'Unauthorized',
      ])));
    } else {
      $data['user'] = $registeredUser;
      $_ENV['JWT_SECRET'] = 'jwt_secret';

      $access_token = MyJWT::encode($data, 3600, true);
      $refresh_token = MyJWT::encode($data, (3600 * 24 * 3), false);
      $decode = MyJWT::decode($access_token);

      $this->mock->append(new Response(200, ['Content-type' => 'application/json'], json_encode([
        'message' => 'Login successful',
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'token_type' => 'Bearer',
        'expired_at' => date('Y-m-d H:i:s', $decode->exp),
        'user' => [
          'id' => $data['user']['id'],
          'name' => $data['user']['name'],
        ],
      ])));
    }
  }

  public function testSuccess(): void
  {
    $input = json_encode([
      'email' => 'john@doe.com',
      'password' => 'password',
    ]);

    $request = new Request('POST', 'http://todo-list-api.test/login', ['Content-type' => 'application/json'], $input);
    $data['user'] = json_decode(strval($request->getBody()), true);
    $validator = $this->validator($data['user']);
    $this->response($validator, $data);
    $response = $this->client->send($request);
    $body = json_decode($response->getBody()->getContents(), true);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertArrayHasKey('message', $body);
    $this->assertArrayHasKey('access_token', $body);
    $this->assertArrayHasKey('refresh_token', $body);
    $this->assertArrayHasKey('token_type', $body);
    $this->assertArrayHasKey('expired_at', $body);
    $this->assertArrayHasKey('user', $body);
    $this->assertSame('Login successful', $body['message']);
    $this->assertSame('John Doe', $body['user']['name']);
  }

  public function testRequiredValidationErrors(): void
  {
    $input = json_encode([]);
    $request = new Request('POST', 'http://todo-list-api.test/login', ['Content-type' => 'application/json'], $input);
    $data['user'] = json_decode(strval($request->getBody()), true);
    $validator = $this->validator($data['user']);
    $this->response($validator, $data);

    try {
      $this->client->send($request);
    } catch (ClientException $e) {
      $this->assertEquals(422, $e->getResponse()->getStatusCode());
      $body = json_decode($e->getResponse()->getBody()->getContents(), true);

      $this->assertArrayHasKey('errors', $body);
      $this->assertArrayHasKey('email', $body['errors']);
      $this->assertArrayHasKey('password', $body['errors']);
      $this->assertSame('email field is required.', $body['errors']['email']);
      $this->assertSame('password field is required.', $body['errors']['password']);
    }
  }

  public function testInvalidEmailOrPassword(): void
  {
    $input = json_encode([
      'email' => 'john@doe.com',
      'password' => 'drowssap',
    ]);
    $request = new Request('POST', 'http://todo-list-api.test/login', ['Content-type' => 'application/json'], $input);
    $data['user'] = json_decode(strval($request->getBody()), true);
    $validator = $this->validator($data['user']);
    $this->response($validator, $data);

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
