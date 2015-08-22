#!/usr/bin/env bash

mkdir -p /var/log/deployer

exec 2>&1 >> /var/log/deployer/deployer.log

_error() {
  echo $@ >&2
  exit 1
}

notifySlack () {
	/var/www/deployer/bin/slack.sh deployer $@
	return 0
}

updateRepository () {
	local i=$(cd $1 && git rev-parse --show-toplevel)
	date --rfc-3339=seconds | xargs echo $i
	cd $i &&
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
			notifySlack deployed $(git describe --tags) on $(hostname):$i

}

createFifo () {
	test -p /tmp/deployer.fifo ||
	mkfifo /tmp/deployer.fifo
	chmod o+w /tmp/deployer.fifo
}

watchFifo () {
	createFifo

	# control the number of running listeners
	test $(ps aux | grep watchFifo -c) -gt 6 && _error another fifo listener is running

	while true
	do
		while read repository
		do
			updateRepository $repository
		done < /tmp/deployer.fifo
	done
}

stopAllFifo () {
	ps aux | grep -ve grep | grep watchFifo | awk '{print $2}' | xargs kill
}

case $1 in
	watchFifo) watchFifo;;
	stop)      stopAllFifo;;
	'') # legacy 0.3.0
		for i in $(ls -d /var/www/*/.git | sed s[.git[[)
		do
			cd $i &&
				[ $(git ls-files -o *.hash | wc -l) -gt 0 ] &&
				updateRepository $i &&
				git ls-files -o *.hash | xargs -n1 -I@ mv @ /var/log/deployer
			cd -
		done
		;;
esac