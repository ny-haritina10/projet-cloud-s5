# Cloud Project S5

## Laravel Backend with PostgreSQL and Docker

This guide explains how to set up and run your Laravel backend application using Docker and PostgreSQL as the database.

---

### Prerequisites

Before you begin, ensure you have the following installed on your machine:

- Docker
- Docker Compose
- Composer (for Laravel setup)

---

### Project Structure

Your project structure should resemble the following:

```
project-root/
├── app/
├── database/
├── public/
├── docker-compose.yml
├── Dockerfile
├── .env
├── composer.json
└── other Laravel files...
```

---

### Configuration

### .env File

Ensure your `.env` file contains the following database configuration:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=docker_cloud_s5
DB_USERNAME=postgres
DB_PASSWORD=docker_cloud_s5
```

---

### Docker Setup

## Steps to Run the Application

1. **Build and Start Containers**

   Run the following command to build and start the containers:

   ```bash
   docker-compose up --build
   ```

2. **Run Migrations**

   Once the containers are running, open a new terminal and access the app container:

   ```bash
   docker exec -it project_cloud_s5 bash
   ```

   Inside the container, run:

   ```bash
   php artisan migrate
   ```

3. **Access the Application**

   The application will be available at:

   [http://localhost:8000](http://localhost:8000)

4. **Access the Database**

   The PostgreSQL database can be accessed on:

   - **Host:** `localhost`
   - **Port:** `5433`
   - **Database:** `docker_cloud_s5`
   - **Username:** `postgres`
   - **Password:** `docker_cloud_s5`

---

## Useful Commands

- **Stop Containers:**

  ```bash
  docker-compose down
  ```

- **Rebuild Containers:**

  ```bash
  docker-compose up --build
  ```

- **View Logs:**

  ```bash
  docker-compose logs -f
  ```

---