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
# in another tab:
(cd wordpress/wp-content/themes/kcnb1 && yarn start)
```

Then:
- open http://localhost:44000/

TODO:
====
- script to sync db data from production to here
- force remove of www and addition of https (test it with HEAD requests)

## Deploy changes to theme only

```sh
(cd wordpress/wp-content/themes/kcnb1 && yarn build:production)
```

Then copy the folder over OVH.

## Deploy changes to wordpress

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
