# Web Installer

The web installer is a simple Symfony application packaged as a Phar file, that allows running automated Composer commands to install or update Cicada.
The term installer means 

## Create a Phar file

To create a Phar file, first install the dependencies with Composer:

    composer install

Then run the following command:

    composer run build-phar

## Running Unit Tests

To run the unit tests, use the following command:

    composer run test

## Running the Web Installer

Copy the created `cicada-installer.phar.php` file to the root directory of your Cicada installation or into an empty directory.

Request that page in your browser with /cicada-installer.phar.php and the Installer will decide if you need to install or update Cicada.

## Running the Web Installer in Development Mode

For development first set up a second Shop installation inside the `shop` directory, to set up this installation run `composer run e2e:web-update:prepare`.

Then start a second Webserver for only this Shop with `composer run e2e:web-update:start`. 
The Web installer will be available at http://localhost:8050/cicada-installer.phar.php. 
It is recommended to start the watcher when you're changing the Web Installer code with `watch-updater`.

## Running update against an unreleased Cicada version

To run an update against an unreleased Cicada version,
copy the `cicada-installer.phar.php` file to the root directory of your Cicada installation.

Clone Cicada into `platform` directory and checkout the branch you want to test.

Then edit the `composer.json` of the Cicada installation and add the following line:

```diff
"repositories": [
    {
        "type": "path",
        "url": "custom/plugins/*",
        "options": {
            "symlink": true
        }
    },
    {
        "type": "path",
        "url": "custom/plugins/*/packages/*",
        "options": {
            "symlink": true
        }
    },
    {
        "type": "path",
        "url": "custom/static-plugins/*",
        "options": {
            "symlink": true
        }
-   }
+   },
+   {
+       "type": "path",
+       "url": "platform/src/*",
+       "options": {
+           "symlink": true
+       }
+   }
],
```

and 
create a `.env.installer` file with the following content:

```
SW_RECOVERY_NEXT_VERSION=6.5.1.0
SW_RECOVERY_NEXT_BRANCH=trunk
```

Replace the version and branch with the version and branch you want to test. 
If in the `composer.json` of the branch is a version set (like in release branches), 
you have to use that version for the next version variable.

Then run the updater regularly with `php cicada-installer.phar.php`,
it will use the forced version and don't try to determine a version anymore.

### Configurable Installer Timeout

The installer timeout can be configured using the `CICADA_INSTALLER_TIMEOUT` environment variable (in seconds). 
Default is 900 seconds (15 minutes). Invalid values fall back to default.

Example:
```bash
export CICADA_INSTALLER_TIMEOUT=1200  # 20 minutes
```

