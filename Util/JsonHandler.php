<?php

declare(strict_types=1);

namespace Antom\Core\Util;

use InvalidArgumentException;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Antom\Core\Logger\AntomLogger;

class JsonHandler
{
    /**
     * @var JsonValidator
     */
    protected $validator;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var AntomLogger
     */
    private $logger;

    /**
     * JsonHandler constructor.
     *
     * @param Json $serializer
     * @param JsonValidator $validator
     * @param AntomLogger $logger
     */
    public function __construct(
        Json $serializer,
        JsonValidator $validator,
        AntomLogger $logger
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * @param $json
     *
     * @return array
     */
    public function readJSON($json): array
    {
        if ($this->validator->isValid((string)$json)) {
            try {
                return (array)$this->serializer->unserialize($json);
            } catch (InvalidArgumentException $invalidArgumentException) {
                $this->logger->logJsonHandlerException($invalidArgumentException);
            }
        }

        return [];
    }

    /**
     * Convert array into JSON
     *
     * @param array $data
     *
     * @return string
     */
    public function convertToJSON(array $data): string
    {
        try {
            $json = $this->serializer->serialize($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->logger->logJsonHandlerException($invalidArgumentException);
            $json = '{}';
        }

        return $json;
    }

    /**
     * @param array $data
     * @return string
     */
    public function convertToPrettyJSON(array $data): string
    {
        try {
            $prettyJson = (string)json_encode($data, JSON_PRETTY_PRINT);

            if (!$prettyJson) {
                throw new InvalidArgumentException(
                    "Unable to serialize value. Error: " . json_last_error_msg()
                );
            }
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->logger->logJsonHandlerException($invalidArgumentException);
            $prettyJson = '{}';
        }

        return $prettyJson;
    }
}
