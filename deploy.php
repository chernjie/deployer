<?php

// For debugging purpose, you can turn on SAVE_PAYLOAD
define('SAVE_PAYLOAD', FALSE);

class CommandLine
{
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
		error_log($this->lastOutput);
		return $this->lastOutput;
	}
}

abstract class Deployer extends CommandLine
{
	public function __construct($payload)
	{
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
		if (SAVE_PAYLOAD)
		{
			file_put_contents(
				sprintf('hook-%s-%s.hash', $this->getProject(), implode('-', $this->getHashes()))
				, $this->payload
			);
		}
		else
		{
			touch('deployer.hash');
			error_log(__CLASS__ . ':' . $this->payload);
		}

		header('Content-Type: text/plain');
		echo 'OK';
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
