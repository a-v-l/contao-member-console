# Member command for contao-console

![Packagist Version](https://img.shields.io/packagist/v/a-v-l/contao-member-console)

Adds `contao:member:create` and  `contao:member:password` commads to [Contao-CLI](https://docs.contao.org/manual/en/cli/) to create front end users or reset passwords.

The command can either be run without flags in interactive mode or with required flags and `-n` (no interaction).

## Usage

### Options for `contao:member:create`
| Flag                    | Description            |
|-------------------------|------------------------|
| -u, --username=USERNAME | The username to create |
| --firstname=FIRSTNAME   | The firstname name     |
| --lastname=LASTNAME     | The lastname name      |
| --email=EMAIL           | The e-mail address     |
| -p, --password=PASSWORD | The password           |
|     --group[=GROUP]     | The groups to assign the user to (optional) (multiple values allowed) |
| -n, --no-interaction    | Do not ask any interactive question |

### Arguments for `contao:member:password`
| Argument                | Description            |
|-------------------------|------------------------|
| username                | The username of the front end user |

### Options for `contao:member:password`
| Flag                    | Description            |
|-------------------------|------------------------|
| -p, --password=PASSWORD | The new password (using this option is not recommended for security reasons) |