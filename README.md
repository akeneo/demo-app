# demo-app

[![CircleCI](https://circleci.com/gh/akeneo/demo-app/tree/main.svg?style=svg&circle-token=897c5b9459e4ab537f5b8f10096ff395a18fa87b)](https://circleci.com/gh/akeneo/demo-app/tree/main)

## Development

### Start the project in 3 steps

1) Create your local `.env` file
```shell
make .env
```
2) Edit the values in `.env`, if necessary
3) Start the development environment:
```shell
make up
```

### Test the Demo App

**Requirements:**
- You have a [PIM developer sandbox](https://api.akeneo.com/apps/overview.html#app-developer-starter-kit)

**Steps:**
- Create a [public url](https://api.akeneo.com/apps/apps-getting-started.html#step-2-get-a-public-url-for-your-in-development-app) for your app
- Register your test app to [receive the credentials](https://api.akeneo.com/apps/apps-getting-started.html#step-3-get-your-test-app-credentials)
- Update `AKENEO_CLIENT_ID` & `AKENEO_CLIENT_SECRET` in `.env` with the credentials
- [Connect your app](https://api.akeneo.com/apps/apps-getting-started.html#step-4-connect-your-test-app-and-access-its-settings)

### Useful commands

```shell
make up # build & start the containers
make down # stop the containers
make destroy # remove all containers, all volumes, all docker images

make tests # launch all the tests

docker-compose run --rm app yarn watch # watch scss & js changes

docker-compose run --rm app bin/console [cmd] # execute a symfony command
docker-compose run --rm app composer [cmd] # execute a composer command
docker-compose run --rm app yarn [cmd] # execute a yarn command
```
