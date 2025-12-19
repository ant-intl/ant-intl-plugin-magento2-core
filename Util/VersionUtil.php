<?php

declare(strict_types=1);

namespace Antom\Core\Util;

use Antom\Core\Client\Response;
use Exception;
use Magento\Framework\HTTP\Adapter\CurlFactory;

class VersionUtil
{
    private const ANTOM_MAGENTO_GITHUB_REPO_LINK
        = 'https://api.github.com/repos/ant-intl/ant-intl-plugin-magento2/releases/latest';

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var Response
     */
    private $response;

    /**
     * VersionUtil constructor.
     *
     * @param JsonHandler $jsonHandler
     * @param CurlFactory $curlFactory
     * @param Response $response
     */
    public function __construct(
        JsonHandler $jsonHandler,
        CurlFactory $curlFactory,
        Response $response
    ) {
        $this->jsonHandler = $jsonHandler;
        $this->curlFactory = $curlFactory;
        $this->response = $response;
    }

    /**
     * Get the current meta package version
     *
     * @return string
     */
    public function getPluginVersion(): string
    {
        // TODO: review this version for each release
        return '1.1.0';
    }

    /**
     * Try to get the latest version through a GitHub API request
     *
     * @return array
     */
    public function getNewVersionsDataIfExist(): array
    {
        try {
            $headers = [
                "Accept-language: en\r\n" .
                "Cookie: foo=bar\r\n" .
                "User-Agent: PHP\r\n",
            ];

            $curl = $this->curlFactory->create();
            $curl->write(
                "GET",
                self::ANTOM_MAGENTO_GITHUB_REPO_LINK,
                "1.1",
                $headers
            );
            $curlData = $this->response->fromString($curl->read());
            $curl->close();

            $content = $curlData['body'] ?? '';

            if ($content) {
                $pluginData = $this->jsonHandler->readJSON($content);
                $latestVersionRelease = $pluginData['tag_name'] ?? null;

                if ($latestVersionRelease !== null) {
                    // Remove all non-digit and non-dot characters
                    $latestVersionRelease = preg_replace('/[^0-9.]/', '', $latestVersionRelease);
                }

                if ($latestVersionRelease && version_compare($latestVersionRelease, $this->getPluginVersion(), '>')) {
                    return [
                        'version' => (string)$latestVersionRelease,
                        'changelog' => $pluginData['body'] ?? '',
                        'url' => $pluginData['html_url'] ?? '',
                    ];
                }
            }
        } catch (Exception $exception) {
            return [];
        }

        return [];
    }
}
