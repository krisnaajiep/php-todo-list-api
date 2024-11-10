<?php

$route = new Route();

$route->post('/register', AuthController::class, 'register');
$route->post('/login', AuthController::class, 'login');
$route->get('/refresh', AuthController::class, 'refresh');
$route->post('/todo', TodoController::class, 'create');
$route->get('/todo', TodoController::class);
$route->get('/todo/{id}', TodoController::class, 'show');
$route->put('/todo/{id}', TodoController::class, 'update');
$route->put('/todo/{id}/mark-in-progress', TodoController::class, 'mark');
$route->put('/todo/{id}/mark-done', TodoController::class, 'mark');
$route->delete('/todo/{id}', TodoController::class, 'delete');
