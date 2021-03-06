#!/usr/bin/env bash

mkdir -p /var/log/deployer

exec 2>&1 >> /var/log/deployer/deployer.log

require () {
	for i
	do
		command -v $i > /dev/null || _error command $i not found
	done
}

_error() {
  echo $@ >&2
  exit 1
}

notifySlack () {
	/var/www/deployer/bin/slack.sh deployer $@
	return 0
}

updateRepository () {
	echo updateRepository $@
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
	test $(ps aux | grep -ve grep | grep watchFifo -c) -gt 3 && _error another fifo listener is running

	while true
	do
		while read repository
		do
			updateRepository $repository
		done < /tmp/deployer.fifo
	done
}

statusFifo () {
	ps aux | grep -ve grep | grep watchFifo
}

stopAllFifo () {
	statusFifo | awk '{print $2}' | xargs kill
}

require mkdir mkfifo date git chown chmod ps grep awk nohup read

case $1 in
	watchFifo) watchFifo;;
	stop)      stopAllFifo;;
	start)     nohup $0 watchFifo &;;
	status)    statusFifo;;
esac