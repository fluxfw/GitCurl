<?php

namespace srag\GitCurl;

use ilCurlConnection;
use srag\DIC\DICTrait;
use Throwable;

/**
 * Class GitCurl
 *
 * @package srag\GitCurl
 */
final class GitCurl
{

    use DICTrait;

    /**
     * @var self[]
     */
    protected static $instances = [];
    /**
     * @var string
     */
    private $default_branch = "main";
    /**
     * @var string
     */
    private $url;


    /**
     * GitCurl constructor
     *
     * @param string $url
     */
    private function __construct(string $url)
    {
        $this->url = $url;

        $this->fixUrl();
    }


    /**
     * @param string $url
     *
     * @return self
     */
    public static function getInstance(string $url) : self
    {
        if (!isset(self::$instances[$url])) {
            self::$instances[$url] = new self($url);
        }

        return self::$instances[$url];
    }


    /**
     * @param string $path
     *
     * @return string|null
     */
    public function fetchFile(string $path) : ?string
    {
        $url = $this->url . "/" . $this->default_branch . "/" . $path;

        $headers = [];

        $result = $this->doRequest($url, $headers);

        if (empty($result)) {
            return null;
        }

        return $result;
    }


    /**
     *
     */
    public function getDefaultBranch() : void
    {
        // Supports only github api
        if (strpos($this->url, "github.com") === false) {
            return;
        }

        $url = str_replace("github.com", "api.github.com/repos", $this->url);

        $headers = [
            "Accept" => "application/json"
        ];

        $result = $this->doRequest($url, $headers);

        $result_json = json_decode($result, true);

        if (is_array($result_json) && is_string($result_json["default_branch"]) && !empty($result_json["default_branch"])) {
            $this->default_branch = $result_json["default_branch"];
        }
    }


    /**
     * @param string $url
     * @param array  $headers
     *
     * @return string|null
     */
    private function doRequest(string $url, array $headers) : ?string
    {
        $curlConnection = null;

        try {
            $curlConnection = $this->initCurlConnection($url, $headers);

            $result = $curlConnection->exec();

            return $result;
        } catch (Throwable $ex) {
        } finally {
            if ($curlConnection !== null) {
                $curlConnection->close();
                $curlConnection = null;
            }
        }

        return null;
    }


    /**
     *
     */
    private function fixUrl() : void
    {
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


    /**
     * @param string $url
     * @param array  $headers
     *
     * @return ilCurlConnection
     */
    private function initCurlConnection(string $url, array $headers) : ilCurlConnection
    {
        $curlConnection = new ilCurlConnection($url);

        $curlConnection->init();

        $headers["User-Agent"] = "ILIAS " . self::version()->getILIASVersion();
        $curlConnection->setOpt(CURLOPT_HTTPHEADER, array_map(function (string $key, string $value) : string {
            return ($key . ": " . $value);
        }, array_keys($headers), $headers));

        $curlConnection->setOpt(CURLOPT_FOLLOWLOCATION, true);

        $curlConnection->setOpt(CURLOPT_RETURNTRANSFER, true);

        $curlConnection->setOpt(CURLOPT_VERBOSE, false/*(intval(DEVMODE) === 1)*/);

        return $curlConnection;
    }
}
