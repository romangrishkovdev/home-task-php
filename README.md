# Lumen REST API with Docker

A robust REST API built with the Lumen PHP framework, designed for efficient data processing, including JWT authentication, complex calculation handling, and asynchronous CSV file processing via a Redis queue. This application leverages Docker and Docker Compose for easy setup and deployment, ensuring a consistent development and production environment.

## Features

- **JWT Authentication**: Secure API access using JSON Web Tokens.
- **Complex Calculation Endpoint**: Handles diverse data types (integers, floats, booleans, strings) for various mathematical operations.
- **Asynchronous CSV Processing**: Efficiently processes large CSV files in the background using a Redis-backed queue.
- **Swagger/OpenAPI Documentation**: Interactive API documentation available via Swagger UI and downloadable OpenAPI specification.
- **Health Check Endpoint**: Provides a simple way to monitor the application's health.
- **Docker and Docker Compose Setup**: Streamlined environment setup for development and production.

## Project Structure

The project follows a standard Lumen (Laravel micro-framework) structure, enhanced with Docker for containerization:

- `app/Http/Controllers`: Contains the application's controllers, handling incoming requests and responses.
- `app/Services`: Houses business logic and service classes, such as `JwtService` for token management and `RedisService` for Redis interactions.
- `routes/web.php`: Defines all API routes, mapping URLs to controller actions.
- `storage/app/uploads`: Directory for temporarily storing uploaded CSV files.
- `config`: Configuration files for various services and application settings.
- `database`: Database migrations and seeders (though not heavily used in this specific project).
- `public`: The public entry point for the web application, containing `index.php`.
- `tests`: Contains PHPUnit tests for the application's features (feature tests are currently the primary focus).
- `docker/`: Custom Docker configurations and scripts.
- `Dockerfile`: Defines the main application Docker image.
- `Dockerfile.worker`: Defines the worker Docker image for background processing.
- `docker-compose.yml`: Orchestrates all Docker services (app, nginx, redis, worker) for local development.

## Requirements

- Docker
- Docker Compose
- Composer (for local development, if running commands directly on the host)

## Setup

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/romangrishkovdev/home-task-php.git
    cd home-task-php
    ```

2.  **Copy the environment file**:
    ```bash
    cp .env.example .env
    ```
3.  **Run composer**:
    ```bash
    composer install
    ```

4.  **Build and start the services**:
    ```bash
    docker compose up -d --build
    ```
    This command will build the Docker images and start the `lumen-app`, `lumen-redis`, `lumen-nginx`, and `lumen-worker` containers in detached mode.

The application will be available at:
-   API: `http://localhost:8080`
-   Redis: `localhost:6379`

## API Endpoints

All API endpoints are prefixed with `/api/v1/`.

### Authentication

-   `POST /api/v1/auth/token` - Generate JWT token.
    -   **Authentication**: None required.
    -   **Request Body**:
        ```json
        {
            "username": "test",
            "password": "test"
        }
        ```
    -   **Returns**: `{"token": "string"}`

### Calculation

-   `POST /api/v1/calculation` - Process calculations based on provided arrays of numbers and strings.
    -   **Authentication**: Requires JWT.
    -   **Request Body**:
        ```json
        {
            "integers": [1, 2, 3],
            "floats": [1.5, 2.5, 3.5],
            "booleans": [true, false, true],
            "strings": ["100", "text", "200"]
        }
        ```
    -   **Returns**: `{"integer_sum": 6, "float_average": 2.5, "boolean_true_count": 2, "numeric_string_sum": 300}`

### CSV Processing

-   `POST /api/v1/task` - Upload a CSV file for asynchronous processing.
    -   **Authentication**: Requires JWT.
    -   **Form Data**: `csv` (file, max 100MB).
    -   **Returns (202 Accepted)**: `{"task_id": "uuid", "row_count": 102, "column_count": 12, "headers": [...], "status": "processing"}`

-   `GET /api/v1/task/{taskId}` - Get the status and summary of a processed CSV task.
    -   **Authentication**: Requires JWT.
    -   **Returns (200 OK - Completed)**: `{"row_count": 100, "column_count": 12, "headers": [...], "status": "completed"}`
    -   **Returns (202 Accepted - Processing)**: `{"status": "processing", "row_count": 102, "column_count": 12, "headers": [...]}`

### System

-   `GET /health` - Health check endpoint.
    -   **Authentication**: None required.
    -   **Returns**: `{"status": "ok"}` or `{"status": "error", "message": "..."}`

### Documentation

-   `GET /api/v1/docs` - Access the interactive Swagger UI.
-   `GET /api/v1/openapi.yaml` - Download the OpenAPI specification in YAML format.

## Testing

### Manual Testing with Postman

A Postman collection (`postman_collection.json`) is provided to easily test all API endpoints.

1.  **Import the collection**: Import `postman_collection.json` into your Postman application.
2.  **Get a JWT Token**: Use the "Get Token" request under "Authentication" to obtain a JWT. This token will be automatically stored in a collection variable named `token`.
3.  **Test Protected Endpoints**: All other protected endpoints use the `{{token}}` variable in their Authorization header, so you can run them directly after getting the token.
4.  **Upload CSV**: For the "Upload CSV" request, ensure you select the `csv` key with "File" type in the "Body" tab and select your CSV file (e.g., `customers-100000.csv`).

### Automated Testing with PHPUnit

To run the automated tests (if any exist):

```bash
docker exec lumen-app vendor/bin/phpunit
```

This will execute all unit and feature tests defined in the `tests/` directory.

## Performance Testing

For performance testing of the CSV upload functionality, you can use the provided Postman collection with the `customers-100000.csv` file. This file contains 100,000 rows and is suitable for testing the asynchronous processing capabilities.

-   **Test Scenario**: Upload `customers-100000.csv` to the `/api/v1/task` endpoint.
-   **Expected Behavior**: The API should return a `202 Accepted` status with a `task_id`. The worker should then process the file in the background. You can check the status using the `GET /api/v1/task/{taskId}` endpoint.

## Future Improvements:
    -   Add .env APP_KEY generation
    -   Replace Redis queue with RabbitMQ for better scalability.
    -   Implement dead letter queues for failed tasks.
    -   Add token refresh mechanism.
    -   Implement rate limiting.
    -   Add request validation middleware.
    -   Add PHP CS Fixer.
    -   Implement comprehensive unit and integration tests.
    -   Add CI/CD pipeline.

## License

The Lumen REST API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). 