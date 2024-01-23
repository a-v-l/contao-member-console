# Member command for contao-console

![Packagist Version](https://img.shields.io/packagist/v/a-v-l/contao-member-console)

Adds `contao:member:create` commad to [Contao-CLI](https://docs.contao.org/manual/en/cli/) to create front end users.

The command can either be run without flags in interactive mode or with all required flags (username, firstname, lastname, email and password) and `-n` (no interaction).

## Options
| Flag                    | Description            |
|-------------------------|------------------------|
| -u, --username=USERNAME | The username to create |
| --firstname=FIRSTNAME   | The firstname name     |
| --lastname=LASTNAME     | The lastname name      |
| --email=EMAIL           | The e-mail address     |
| -p, --password=PASSWORD | The password           |
|     --group[=GROUP]     | The groups to assign the user to (optional) (multiple values allowed) |
| -n, --no-interaction    | Do not ask any interactive question |