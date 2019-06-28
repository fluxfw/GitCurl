<?php

namespace srag\GitCurl;

use ilCurlConnection;
use ilProxySettings;
use srag\DIC\DICTrait;
use Throwable;

/**
 * Class GitCurl
 *
 * @package srag\GitCurl
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
final class GitCurl {

	use DICTrait;
	/**
	 * @var self[]
	 */
	protected static $instances = [];


	/**
	 * @param string $url
	 *
	 * @return self
	 */
	public static function getInstance(string $url): self {
		if (!isset(self::$instances[$url])) {
			self::$instances[$url] = new self($url);
		}

		return self::$instances[$url];
	}


	/**
	 * @var string
	 */
	private $url;
	/**
	 * @var string
	 */
	private $default_branch = "master";


	/**
	 * GitCurl constructor
	 *
	 * @param string $url
	 */
	private function __construct(string $url) {
		$this->url = $url;

		$this->fixUrl();
	}


	/**
	 *
	 */
	public function getDefaultBranch()/*: void*/ {
		try {
			// Supports only github api
			if (strpos($this->url, "github.com") !== false) {
				$url = str_replace("github.com", "api.github.com/repos", $this->url);

				$curlConnection = new ilCurlConnection();

				$curlConnection->init();

				$curlConnection->setOpt(CURLOPT_RETURNTRANSFER, true);
				$curlConnection->setOpt(CURLOPT_URL, $url);

				$headers = [
					"Accept" => "application/json",
					"User-Agent" => "ILIAS " . self::version()->getILIASVersion()
				];
				$headers = array_map(function ($key, $value) {
					return ($key . ": " . $value);
				}, array_keys($headers), $headers);
				$curlConnection->setOpt(CURLOPT_HTTPHEADER, $headers);

				$result = $curlConnection->exec();

				$result = json_decode($result, true);
				if (is_array($result) && is_string($result["default_branch"]) && !empty($result["default_branch"])) {
					$this->default_branch = $result["default_branch"];
				}
			}
		} catch (Throwable $ex) {
		}
	}


	/**
	 * @param string $path
	 *
	 * @return string|null
	 */
	public function fetchFile(string $path)/*: ?string*/ {
		try {
			$url = $this->url . "/" . $this->default_branch . "/" . $path;

			$curlConnection = new ilCurlConnection();

			$curlConnection->init();

			// use a proxy, if configured by ILIAS
			if (!self::version()->is60()) {
				$proxy = ilProxySettings::_getInstance();
				if ($proxy->isActive()) {
					$curlConnection->setOpt(CURLOPT_HTTPPROXYTUNNEL, true);

					if (!empty($proxy->getHost())) {
						$curlConnection->setOpt(CURLOPT_PROXY, $proxy->getHost());
					}

					if (!empty($proxy->getPort())) {
						$curlConnection->setOpt(CURLOPT_PROXYPORT, $proxy->getPort());
					}
				}
			}

			$curlConnection->setOpt(CURLOPT_RETURNTRANSFER, true);
			$curlConnection->setOpt(CURLOPT_URL, $url);

			$result = $curlConnection->exec();

			if (is_string($result) && !empty($result)) {
				return $result;
			} else {
				return NULL;
			}
		} catch (Throwable $ex) {
			return NULL;
		}
	}


	/**
	 *
	 */
	private function fixUrl()/*: void*/ {
		// Fix possible windows paths
		$this->url = str_replace("\\", "/", $this->url);

		// Remove ends with /
		$this->url = preg_replace("/\/$/", "", $this->url);

		// Some urls includes releases at end. Remove it
		$this->url = preg_replace("/\/releases$/", "", $this->url);

		// Fix some links not includes http protocol
		if (strpos($this->url, "https://") !== 0) {
			$this->url = "https://" . $this->url;
		}

		$this->getDefaultBranch();

		// Get github raw file content
		$this->url = str_replace("github.com", "raw.githubusercontent.com", $this->url);
	}
}
