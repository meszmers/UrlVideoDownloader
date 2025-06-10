## Video Downloader

This application is using Symphony PHP framework with ReactPHP library to download videos form provided url's to local storage concurrently.

## Setup project
### Prerequisites

Before you begin, ensure you have the following installed on your system:

``Docker``

``Git``

### Clone the repository:

``git clone https://github.com/meszmers/UrlVideoDownloader.git``

``cd UrlVideoDownloader``

``docker-compose up --build -d``

``docker-compose exec php-fpm bash``

### Once inside the PHP container, install the project's dependencies:

``composer install``

### Running the Application

#### If you are not inside a container:

``docker-compose exec php-fpm bash``

#### Run application command to download videos:

``php bin/console app:video-downloader``

### Design Decisions

- Resumable Downloads:
    - Downloads can be resumed by checking for partial files (.part extension) and utilizing the Range HTTP header. This improves reliability, especially for large files or unstable network conditions.
- Automatic Retries:
    - The service includes an automatic retry mechanism with a timeout (RETRY_TIMEOUT_INTERVAL). This handles transient network errors or temporary disconnections gracefully.
- Docker for Deployment:
  - The project is designed to run within Docker containers. This simplifies environment setup, ensures consistency between development and production, and isolates the application from the host system.


### Possible improvements

1. URL Validation: Add more robust URL validation to ensure only valid video URLs are processed.
2. Cancellation Mechanism: Provide a way to explicitly cancel an ongoing download.
3. Cleanup of Stale Partial Files: Implement a mechanism to periodically clean up very old or abandoned .part files in the temp directory.
4. Progress Callbacks/Events: Introduce a mechanism to report download progress (e.g., percentage completed) back to the calling process or a message queue for better user feedback.



