<?php

if (empty($_POST) || empty($_POST['payload']))
	exit('Go read <a href="https://confluence.atlassian.com/display/BITBUCKET/POST+Service+Management">this</a>');

define('WEBROOT', '/var/www/');
define('LOG_COMMITS', TRUE);

/**
$_POST['payload'] = '
{
	"canon_url": "https://bitbucket.org",
	"commits": [
		{
			"author": "marcus",
			"branch": "master",
			"files": [
				{
					"file": "somefile.py",
					"type": "modified"
				}
			],
			"message": "Added some more things to somefile.py\n",
			"node": "620ade18607a",
			"parents": [
				"702c70160afc"
			],
			"raw_author": "Marcus Bertrand <marcus@somedomain.com>",
			"raw_node": "620ade18607ac42d872b568bb92acaa9a28620e9",
			"revision": null,
			"size": -1,
			"timestamp": "2012-05-30 05:58:56",
			"utctimestamp": "2012-05-30 03:58:56+00:00"
		}
	],
	"repository": {
		"absolute_url": "/marcus/project-x/",
		"fork": false,
		"is_private": true,
		"name": "Project X",
		"owner": "marcus",
		"scm": "git",
		"slug": "metaf",
		"website": "https://atlassian.com/"
	},
	"user": "marcus"
}
';
/**/

/**
 * Return full output instead of just last line
 * @param string $command
 * @param array $output
 * @param int $return_var
 */
function run_exec($command, &$output = array(), &$return_var = 0)
{
	exec($command, $output, $return_var);
	return implode("\n", $output);
}

$payload = json_decode($_POST['payload']);

$project = $payload->repository->slug;
if (LOG_COMMITS)
{
	$hashes = array();
	foreach ($payload->commits as $commit)
		$hashes[] = $commit->node;
	file_put_contents(
		sprintf('%s-%s.hash', $project, implode('-', $hashes))
		, $_POST['payload']
	);
}

header('Content-Type: text/plain');

$command = $output = $return_var = null;

chdir(WEBROOT . $project);
// error_log(run_exec('which git'));
// error_log(run_exec('pwd'));
// error_log(run_exec('whoami'));
$output = run_exec('git fetch --verbose origin');
error_log($output);
$output = run_exec('git rev-list --left-right --count master..@{upstream}');
list($ahead, $upstream) = explode("\t", $output);
if ($upstream > 0)
{
	$output = run_exec('git rebase origin/master || (git stash save cronjob && git rebase origin/master && git stash pop)');
	error_log($output);
}
