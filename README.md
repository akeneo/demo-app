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

If you are running a PIM locally with docker, first, create a `.env.local` file in the PIM directory with the following values:
```
AKENEO_PIM_URL=http://172.17.0.1:8080
FLAG_APP_DEVELOPER_MODE_ENABLED=1
```
Check our documentation: [How to test my App?](https://api.akeneo.com/apps/how-to-test-my-app.html)  
And fill the form with the url `http://172.17.0.1:8090` exposed by docker:
![Test app creation form](documentation/creation-form-test-app.png)

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
