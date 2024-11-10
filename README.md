# PHP Todo List API

A RESTful API built with PHP and MySQL that allows users to manage their to-do list. It supports pagination and filtering by status. This API uses [php-jwt](https://github.com/firebase/php-jwt) for JWT authentication, [phpdotenv](https://github.com/vlucas/phpdotenv) for loads environment variables and [guzzle](https://docs.guzzlephp.org/en/stable/index.html) for PHP HTTP client. Inspired by [roadmap.sh](https://roadmap.sh/projects/todo-list-api)

## **Getting started guide**

Make sure you have installed:

- **PHP**: Latest version, to run the API server and scripts.
- **MySQL**: For storing and managing the todo list data.

To start using the Todo List Tracker API, you need to -

1. Clone the repository.

   ```bash
    git clone https://github.com/krisnaajiep/todo-list-api.git

   ```

2. Install dependencies.

   ```bash
    composer install

   ```

3. Configure `.env` file.

   ```bash
    cp .env.example .env

   ```

4. Generate JWT Secret.

   ```bash
    php index.php jwt:secret

   ```

5. Run the local web server.
6. Run the API with the base URL.

## Authentication

This API uses Bearer Token for authentication. You can generate an access token by registering a new user or login.

You must include an access token in each request to the API with the Authorization request header.

### Authentication error response

If an API key is missing, malformed, or invalid, you will receive an HTTP 401 Unauthorized response code.

## Rate and usage limits

API access rate limits apply at a per-API key basis in unit time. The limit is 60 requests per minute. Also, depending on your plan, you may have usage limits. If you exceed either limit, your request will return an HTTP 429 Too Many Requests status code.

Each API response returns the following set of headers to help you identify your use status:

| Header                  | Description                                                                       |
| ----------------------- | --------------------------------------------------------------------------------- |
| `X-RateLimit-Limit`     | The maximum number of requests that the consumer is permitted to make per minute. |
| `X-RateLimit-Remaining` | The number of requests remaining in the current rate limit window.                |
| `X-RateLimit-Reset`     | The time at which the current rate limit window resets in UTC epoch seconds.      |

## HTTP Response Codes

The following status codes are returned by the API depending on the success or failure of the request.

| Status Code               | Description                                                                                  |
| ------------------------- | -------------------------------------------------------------------------------------------- |
| 200 OK                    | The request was processed successfully.                                                      |
| 201 Created               | The new resource was created successfully.                                                   |
| 401 Unauthorized          | Authentication is required or the access token is invalid.                                   |
| 403 Forbidden             | Access to the requested resource is forbidden.                                               |
| 404 Not Found             | The requested resource was not found.                                                        |
| 409 Conflict              | Indicates a conflict between the request and the current state of a resource on a web server |
| 422 Unprocessable Content | The server understands the request, but cannot process it due to a validation error          |
| 429 Too Many Request      | The client has sent too many requests in a given amount of time (rate limiting).             |
| 500 Internal Server Error | An unexpected server error occurred.                                                         |

### **Need some help?**

In case you have questions or need further assistance, you can refer to the following resources:

- [API Documentation](https://documenter.getpostman.com/view/37187730/2sAY52dLAH)
- [Issues](https://github.com/krisnaajiep/php-todo-list-api/issues)
