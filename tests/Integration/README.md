# Integration Tests

This directory contains integration tests for the application. We use Codeception for writing and running the tests, with a focus on testing the interactions with the MySQL database.

## Setup

1. **Start test database**:
  Navigate to the `docker` folder and run the following command.
  ```bash
  docker compose up -d
  ```

2. **Build Actor Classes**:
   Build the actor classes by running:
   ```bash
   php vendor/bin/codecept build
   ```

## Writing Tests

- Create test files within this directory.
- Use the `IntegrationTester` class to interact with the application and the database.
- Utilize functions like `haveInDatabase`, `seeInDatabase`, and `grabFromDatabase` for database interactions.

## Running Tests

Run the integration tests by executing the following command from the project root:
```bash
php vendor/bin/codecept run integration
```

## Best Practices

- Use a separate testing database to ensure tests do not interfere with development or production data.
- Clean up the database before and after each test to maintain a consistent state.
- Utilize migrations and fixtures to create necessary database structures and initial data.
- Avoid hardcoding IDs, as they can change between runs.
- Write assertions to check the state of the database, ensuring correct interaction with the database.

For more details on database testing with Codeception, refer to the [official documentation](https://codeception.com/docs/modules/Db).
