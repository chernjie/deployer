deployer
========

Custom Git Deployer

How it works
============
1. Utilizing BitBucket POST services, when someone push a commit to BitBucket, BitBucket will make a POST request to `www.yourdomain.com/deploy.php`
2. `deploy.php` will create a new `commit.hash`
3. `cron.sh` will pick up `commit.hash` and rebase your git repository

Setup
=====

1. Add or symlink `deploy.php` to the `DocumentRoot` of your web directory, e.g.
`/var/www/project/deploy.php`
\* Make sure that `/var/www/project` is a git repository

2. Add this to your cron
`*/1 * * * * /var/www/deployer/cron.sh`
