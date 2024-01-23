# Create members with contao-console

![Packagist Version](https://img.shields.io/packagist/v/a-v-l/contao-member-create)

Adds `contao:member:create` commad to [Contao-CLI](https://docs.contao.org/manual/en/cli/) to create front end users.

## Options
| Flag |description|
|-------------------------|------------------------|
| -u, --username=USERNAME | The username to create |
| --firstname=FIRSTNAME   | The firstname name     |
| --lastname=LASTNAME     | The lastname name      |
| --email=EMAIL           | The e-mail address     |
| -p, --password=PASSWORD | The password           |
|     --group[=GROUP]     | The groups to assign the user to (optional) (multiple values allowed) |
| -n, --no-interaction    | Do not ask any interactive question |