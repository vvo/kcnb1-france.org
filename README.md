# kcnb1-france.org

## Requirements

- [Docker](https://docs.docker.com/install/)
- [nvm](https://github.com/creationix/nvm#installation-and-update)
- [Yarn](https://yarnpkg.com/en/docs/install#alternatives-stable)

## How to use

```sh
nvm install
nvm use
(cd wordpress/wp-content/themes/kcnb1 && composer install && yarn)
docker-compose up
```

Then:
- open http://localhost:44000/

## Database seed
TODO

## Deploy

1. Export DB
2. Replace all url occurences in DB backup
3. Copy code to OVH
4. Import DB backup

## If something goes wrong

```sh
rm -rf db
docker-compose stop
docker-compose rm
docker-compose up
```
