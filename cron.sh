#!/bin/bash

for i in $(ls -d /var/www/*/.git | sed s[.git[[)
do
	cd $i &&
		[ $(git ls-files -o *.hash | wc -l) -gt 0 ] &&
		git fetch origin &&
		(
			git rebase origin/master ||
				(
					git stash save -u cronjob &&
						git rebase origin/master &&
						git stash pop
				) ||
				echo -n
		) &&
			chmod -R g+w . &&
			chown -R www-data:www-data . &&
			git ls-files -o *.hash | xargs -n1 -I@ mv @ /var/log/bitbucket &&
			date --rfc-3339=seconds | xargs echo $i >> /var/log/bitbucket/cronjob.log
done # bitbucket
