# Laravel Accredify API

### Run
    git clone git@bitbucket.org:clastr333/laravelaccredifyapi.git .

Set .env file. By default:

    APP_NAME=Laravel_Accredify_API
    DB_CONNECTION=sqlite


And then

    docker-compose up

### Usage
1. Register a new user


    POST http://localhost:8080/api/v1/register
    {
    "name": "John Doe",
    "email": "johndoe@example.com",
    "password": "password",
    "password_confirmation": "password"
    }

2. Log in with the user's credentials to receive an API token:


    POST http://localhost:8080/api/v1/login
    {
    "email": "johndoe@example.com",
    "password": "password",
    "device_name": "my_device"
    }

3. Use auth header


    Authorization: Bearer <YOUR_TOKEN>

### Use React client
Set in file client/.env

    REACT_APP_VERIFY_API="http://localhost:8080/api/v1/verify"
    REACT_APP_AUTH_TOKEN="<YOUR_TOKEN>"
    REACT_APP_PAGE_TITLE="JSON Verification"

Then

    docker-compose up --build -d

or

    cd client
    npm start
