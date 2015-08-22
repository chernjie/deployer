#!/usr/bin/env bash

mkdir -p /var/log/deployer

exec >> /var/log/deployer/deployer.log
exec 2>> /var/log/deployer/deployer.log

notifySlack () {
	/var/www/deployer/bin/slack.sh deployer $@
	return 0
}

updateRepository () {
	local i=$1
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
			notifySlack deployed $(git describe --tags) on $(hostname):$i &&
			git ls-files -o *.hash | xargs -n1 -I@ mv @ /var/log/deployer

}

createFifo () {
	test -p /tmp/deployer.fifo ||
	mkfifo /tmp/deployer.fifo
	chmod o+w /tmp/deployer.fifo
}

watchFifo () {
	createFifo
	while true
	do
		while read repository
		do
			updateRepository $repository
		done < /tmp/deployer.fifo
	done
}

case $1 in
	watchFifo) watchFifo;;
	'')
		for i in $(ls -d /var/www/*/.git | sed s[.git[[)
		do
			updateRepository $i
		done
		;;
esac