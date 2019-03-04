# kcnb1-france.org

## Requirements

- [Docker](https://docs.docker.com/install/)

## How to use

```sh
docker-compose up
```

Then:
- open http://localhost:44000/
- At the database configuration step, just change `localhost` to `db`:

![Database step](/additional-configurations/database-step.png)

Then follow the WordPress installation instructions.

## If something goes wrong

```sh
rm -rf db
docker-compose stop
docker-compose rm
docker-compose up
```

## Credentials

You can connect to the database by using:
- http://localhost:44100
- *System*: MySQL
- *Server*: db
- *Username*: username
- *Password*: password
- Leave *Database* empty

Or any other GUI client, like [TablePlus](https://tableplus.io/).
