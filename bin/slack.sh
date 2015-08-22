#!/usr/bin/env bash

_error() {
  echo $@ >&2
  exit 1
}

getSlackConfig() {
  git config deployer.slack.incoming-webhook
}

main () {
  getSlackConfig > /dev/null || _error git config deployer.slack.incoming-webhook not found
  local username=$1
  shift
  local text="$@"
  curl -XPOST --silent \
    --output /dev/null
    --data-urlencode 'payload={"username":"'"$username"'", "text":"'"$text"'"}' \
    $(getSlackConfig)
}

case $1 in
  *) main $@;;
esac
