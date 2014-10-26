deployer
========

Simple Git Deployer

#### How it works

1. Utilizing [Github webhooks](https://developer.github.com/webhooks/) or [BitBucket POST services](https://confluence.atlassian.com/display/BITBUCKET/POST+hook+management)
2. `deploy.php` will create a new `commit.hash`
3. `cron.sh` will pick up `commit.hash` and rebase your git repository

#### Setup

1. Login to Github/Bitbucket, and add `http://yourproject/deploy.php` to your web hook.

2. Add or symlink `deploy.php` to the `DocumentRoot` of your web directory, e.g.
`/var/www/project/deploy.php`
 * Make sure that `/var/www/project` is a git repository

3. Add this to your cron
`* * * * * /var/www/deployer/cron.sh`

#### Alternatives

* Bruteforce Cron `* * * * * cd /var/www/project && git pull --rebase`
* https://github.com/thephpdeveloper/Deployer
* https://github.com/mislav/git-deploy
* https://github.com/BrunoDeBarros/git-deploy-php
