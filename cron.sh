#!/usr/bin/env bash

for i in $(ls -d /var/www/*/.git | sed s[.git[[)
do
	date --rfc-3339=seconds | xargs echo $i
	cd $i &&
		[ $(git ls-files -o *.hash | wc -l) -gt 0 ] &&
		git fetch &&
		(
			git rebase ||
				(
					git stash save -u cronjob &&
						git rebase &&
						git stash pop
				) ||
				echo -n
		) &&
			chmod -R g+w . &&
			chown -R www-data:www-data . &&
			git ls-files -o *.hash | xargs -n1 -I@ mv @ /var/log/bitbucket
done 2>&1 | tee -a /var/log/bitbucket/cronjob.log
