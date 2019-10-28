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

## Deploy changes to wordpress

1. Export DB via `docker exec kcnb1-franceorg_db_1 /usr/bin/mysqldump -u root --password=password wordpress > backup.sql`
2. Replace all url occurences in DB backup about http://localhost to https://kcnb1-france.org in an editor
3. Copy code to OVH
4. Import DB backup via OVH phpMyAdmin

## Deploy command

```sh
rsync -zahv --update --delete --exclude-from 'exclude-list.txt' wordpress/ kcnbfrh@sshcloud.cluster024.hosting.ovh.net:./www
```

```txt
Host sshcloud.cluster024.hosting.ovh.net
  Port 41857
  User kcnbfrh
```

