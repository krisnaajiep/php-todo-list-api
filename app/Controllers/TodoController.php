<?php

class TodoController extends Controller
{
  public function __construct()
  {
    try {
      $authorization = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
      $token_type = $authorization[0];

      if ($token_type !== 'Bearer') throw new Exception("Unauthorized", 401);

      $access_token = $authorization[1];
      $decode = MyJWT::decode($access_token);

      if (!$decode) throw new Exception("Unauthorized", 401);
      if ($decode->exp < time()) throw new Exception("Expired Token", 401);

      $_GET['user_id'] = intval($decode->sub);

      if (!$this->model('User')->get(Request::get('user_id')) || !$decode->access)
        throw new Exception("Unauthorized", 401);
    } catch (\Throwable $th) {
      echo Response::json($th->getCode(), ['message' => $th->getMessage()]);
      exit;
    }
  }

  public function index()
  {
    $count = $this->model('Todo')->count();
    Pagination::setLimit(Request::get('limit') ?? $count);
    $limit = Pagination::getLimit();
    $start = Pagination::getStart();

    $result = $this->model('Todo')->all($start, $limit);

    return Response::json(data: [
      'data' => $result,
      'page' => empty(Request::get('page')) ? 1 : intval(Request::get('page')),
      'limit' => $limit,
      'total' => $count,
    ]);
  }

  public function create(array $data = [])
  {
    $validator = Validator::setRules($data, [
      'title' => ['required', 'min_length:3', 'max_length:100'],
      'description' => ['required', 'max_length:1000'],
    ]);

    if ($validator->hasValidationErrors())
      return Response::json(422, ['errors' => $validator->getValidationErrors()]);

    $validated = $validator->validated();

    $validated['user_id'] = Request::get('user_id');

    $result = $this->model('Todo')->create($validated);

    return Response::json(201, ['message' => 'Create successful', 'data' => $result]);
  }

  public function show(int $id = null)
  {
    $todo = $this->model('Todo')->one($id);

    return Response::json(data: ['data' => $todo]);
  }

  public function update(array $data, int $id = null)
  {
    $validator = Validator::setRules($data, [
      'title' => ['required', 'min_length:3', 'max_length:100'],
      'description' => ['required', 'max_length:1000'],
    ]);

    if ($validator->hasValidationErrors())
      return Response::json(422, ['errors' => $validator->getValidationErrors()]);

    $result = $this->model('Todo')->update($validator->validated(), $id);

    return Response::json(data: ['message' => 'Update successful', 'data' => $result]);
  }

  public function mark(array $data, int $id)
  {
    $url = Request::get('url');
    $data['status'] = strpos($url, 'mark-in-progress') !== false ? 'in progress' : 'done';
    $result = $this->model('Todo')->update($data, $id);

    return Response::json(data: ['message' => 'Update successful', 'data' => $result]);
  }

  public function delete(int $id)
  {
    $result = $this->model('Todo')->delete($id);

    if ($result === true) {
      http_response_code(204);
      return;
    }
  }
}
