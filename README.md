# WordPress multitenant

Repository for WordPress multitenant setup for [no-cost.site](https://no-cost.site).

## Local dev

To install updates, plugins, locales etc. locally:

- Download [wp-cli](https://make.wordpress.org/cli/handbook/guides/installing/)
- Ensure you have a local MariaDB instance running
- Create an empty dummy database
- Install the dummy DB (just so that wp-cli can connect to it and see the schema):

```bash
wp core install \
    --url="http://localhost" \
    --title="dev" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.com \
    --skip-email
```

- Use wp-cli normally

## Useful cmds

```bash
# wordpress core
wp core update
wp core version

# plugins
wp plugin list
wp plugin install <plugin-slug>
wp plugin update <plugin-slug>
wp plugin delete <plugin-slug>

# themes
wp theme list
wp theme install <theme-slug>
wp theme update <theme-slug>
wp theme delete <theme-slug>

# locales
wp language core update

# for tenants
wp core update-db
wp cache flush
wp rewrite flush
```

After done, commit to `main`, which will be synced to server on deploy (see [`no-cost/deploy`](https://github.com/no-cost/deploy)).
