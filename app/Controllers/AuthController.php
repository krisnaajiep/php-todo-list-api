<?php

class AuthController extends Controller
{
  public function register(array $data): string
  {
    $validator = Validator::setRules($data, [
      'name' => ['required', 'alpha', 'min_length:2', 'max_length:50'],
      'email' => ['required', 'email', 'max_length:254'],
      'password' => ['required', 'min_length:8', 'max_length:255', 'match:password_confirmation'],
      'password_confirmation' => ['required', 'match:password'],
    ]);

    if ($validator->hasValidationErrors())
      return Response::json(422, ['errors' => $validator->getValidationErrors()]);

    $user = $this->model('User')->create($validator->validated());

    http_response_code(201);
    return json_encode($this->respondWithToken([
      'message' => 'Register successful',
      'user' => [
        'id' => $user->id,
        'name' => $user->name,
      ],
    ]));
  }

  public function login(array $credentials)
  {
    $validator = Validator::setRules($credentials, [
      'email' => ['required', 'email'],
      'password' => ['required'],
    ]);

    if ($validator->hasValidationErrors())
      return Response::json(422, ['errors' => $validator->getValidationErrors()]);

    $user = $this->model('User')->login($validator->validated());

    return json_encode($this->respondWithToken([
      'message' => 'Login successful',
      'user' => [
        'id' => $user->id,
        'name' => $user->name,
      ],
    ]));
  }

  public function refresh()
  {
    try {
      if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $authorization = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
        if ($authorization[0] !== 'Bearer') throw new Exception("Unauthorized", 401);
        $refresh_token = $authorization[1];
      } else {
        $refresh_token = $_COOKIE['refresh_token'];
      }

      $decode = MyJWT::decode($refresh_token);

      if (!$decode || $this->model('User')->getBlaclistedToken($decode->jti) || $decode->access)
        throw new Exception("Unauthorized", 401);

      if ($decode->exp < time()) throw new Exception("Expired Token", 401);

      $this->model('User')->blacklistToken([
        'jti' => $decode->jti,
        'user_id' => intval($decode->sub),
        'expired_at' => date('Y-m-d H:i:s', $decode->exp),
      ]);

      return json_encode($this->respondWithToken([
        'message' => 'Refresh token successful',
        'user' => [
          'id' => intval($decode->sub),
          'name' => $decode->name
        ]
      ]));
    } catch (\Throwable $th) {
      return Response::json($th->getCode(), ['message' => $th->getMessage()]);
    }
  }

  private function respondWithToken(array $data): array
  {
    $access_token = MyJWT::encode($data, 3600, true);
    $refresh_token = MyJWT::encode($data, (3600 * 24 * 3), false);
    $decode = MyJWT::decode($access_token);

    setcookie('refresh_token', $refresh_token, time() + (3600 * 24 * 3), httponly: true);

    header('Content-type: application/json');

    return [
      'message' => $data['message'],
      'access_token' => $access_token,
      'refresh_token' => $refresh_token,
      'token_type' => 'Bearer',
      'expires_at' => date('Y-m-d H:i:s', $decode->exp),
      'user' => [
        'id' => $data['user']['id'],
        'name' => $data['user']['name'],
      ],
    ];
  }
}
