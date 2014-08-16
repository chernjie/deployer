#!/usr/bin/env bash

DIR="$(cd "$( dirname "${BASH_SOURCE[0]}" )" > /dev/null && pwd -P)"
REPO_BASE="$DIR/.."
WP_CONFIG=$REPO_BASE/htdocs/wp-config.php
DATA_DIR=$REPO_BASE/data

function _extract_wp_config()
{
	local _constant="$2"
	local _wp_config="$1"
	grep -oE "$_constant', '[^']+'" $_wp_config |
		grep -oE "[^']+" |
		grep -v , |
		tail -1
}

for i in /var/www/*/*/wp-config.php
do

    DB_NAME="$(_extract_wp_config $i DB_NAME)"
    DB_USER="$(_extract_wp_config $i DB_USER)"
DB_PASSWORD="$(_extract_wp_config $i DB_PASSWORD)"
    DB_HOST="$(_extract_wp_config $i DB_HOST)"

	echo CREATE DATABASE $DB_NAME'; GRANT ALL PRIVILEGES ON '$DB_NAME'.* TO "'$DB_USER'"@"'$DB_HOST'" IDENTIFIED BY "'$DB_PASSWORD'";'

done
