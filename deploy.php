<?php

define('WEBROOT', '/var/www/');
define('LOG_COMMITS', TRUE);
define('DEBUG', TRUE);

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

class CommandLine
{
	const DEBUG = DEBUG;
	protected $lastOutput = '';

	/**
	 * Return full output instead of just last line
	 * @param string $command
	 * @param array $output
	 * @param int $return_var
	 */
	protected function run_exec($command, &$output = array(), &$return_var = 0)
	{
		exec($command, $output, $return_var);
		$this->lastOutput = implode("\n", $output);
		if (self::DEBUG) error_log($this->lastOutput);
		return $this->lastOutput;
	}
}

abstract class Deployer extends CommandLine
{
	const WEBROOT     = WEBROOT; // '/var/www/';
	const LOG_COMMITS = LOG_COMMITS; // TRUE;

	public function __construct($payload)
	{
		self::DEBUG && error_log($payload);
		$this->payload = $payload;
		$this->main();
	}

	protected function getPayload()
	{
		static $payload;
		if (empty($payload))
			$payload = json_decode($this->payload);
		return $payload;
	}
	abstract protected function getProject();
	abstract protected function getHashes();

	/**
	 * Main execution
	 */
	public function main()
	{
		if (self::LOG_COMMITS)
		{
			file_put_contents(
				sprintf('hook-%s-%s.hash', $this->getProject(), implode('-', $this->getHashes()))
				, json_encode($this->payload)
			);
		}

		header('Content-Type: text/plain');

		chdir(self::WEBROOT . $this->getProject());
		// $this->run_exec('which git');
		// $this->run_exec('pwd');
		// $this->run_exec('whoami');
		$this->run_exec('git fetch --verbose origin');
		$this->run_exec('git rev-list --left-right --count master..@{upstream}');
		list($ahead, $upstream) = explode("\t", $this->lastOutput);
		if ($upstream > 0)
		{
			$this->run_exec('git rebase origin/master || (git stash save cronjob && git rebase origin/master && git stash pop)');
		}
	}
}

class BitBucketDeployer extends Deployer
{
	protected function getProject()
	{
		return $this->getPayload()->repository->slug;
	}
	protected function getHashes()
	{
		foreach ($this->getPayload()->commits as $commit)
			$hashes[] = $commit->node;
		return $hashes;
	}
}

class GithubDeployer extends Deployer
{
	protected function getProject()
	{
		return $this->getPayload()->repository->name;
	}
	protected function getHashes()
	{
		foreach ($this->getPayload()->commits as $commit)
			$hashes[] = $commit->id;
		return $hashes;
	}
}

if (! empty($_POST['payload']))
	new BitBucketDeployer($_POST['payload']);
else if (array_key_exists('HTTP_USER_AGENT', $_SERVER) && strpos($_SERVER['HTTP_USER_AGENT'], 'GitHub-Hookshot') === 0)
	new GithubDeployer(file_get_contents("php://input"));
else
	exit('Go read <a href="https://confluence.atlassian.com/display/BITBUCKET/POST+Service+Management">this</a>');
