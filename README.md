deployer
========

Simple Git Deployer

#### How it works

1. Utilizing [Github webhooks](https://developer.github.com/webhooks/) or [BitBucket POST services](https://confluence.atlassian.com/display/BITBUCKET/POST+hook+management)
2. `deploy.php` will create a new `commit.hash`
3. `cron.sh` will pick up `commit.hash` and rebase your git repository

#### Setup

1. Login to Github/Bitbucket, and add `http://yourproject/deploy.php` to your web hook.

2. Clone this repository
```shell
git clone https://github.com/chernjie/deployer /var/www/
```

3. Add or symlink `deploy.php` to the `DocumentRoot` of your web directory, e.g. `/var/www/project/deploy.php`
 * Make sure that `/var/www/project` is a git repository
```shell
ln -sf /var/www/deployer/deploy.php /var/www/project
```

4. Add this to your cron
`* * * * * /var/www/deployer/cron.sh`

Make sure your cronjob run as root
```shell
sudo crontab -e
```

### Slack Integration

Send notification whenever `deployer` completes a deploy.

Add a new [incoming-webhook](https://my.slack.com/services/new/incoming-webhook) integration on Slack. Copy the `Webhook URL` to your repository:

```shell
git config deployer.slack.incoming-webhook <webhook url>
```

#### Alternatives

* Bruteforce Cron `* * * * * cd /var/www/project && git pull --rebase`
* https://github.com/thephpdeveloper/Deployer
* https://github.com/mislav/git-deploy
* https://github.com/BrunoDeBarros/git-deploy-php
