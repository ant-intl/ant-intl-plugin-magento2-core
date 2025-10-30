<?php

namespace Antom\Core\Helper;

use InvalidArgumentException;

class AssertHelper
{
    /**
     * 检查参数非空，如果为空抛出 param_illegal 异常
     *
     * @param mixed $value
     * @param string $paramName
     * @return void
     * @throws InvalidArgumentException
     */
    public static function notEmpty($value, string $paramName): void
    {
        if (empty($value) && $value !== '0') {
            throw new InvalidArgumentException("param_illegal: {$paramName} is required");
        }
    }

    /**
     * 检查参数为字符串
     *
     * @param mixed $value
     * @param string $paramName
     * @return void
     * @throws InvalidArgumentException
     */
    public static function isString($value, string $paramName): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("param_illegal: {$paramName} must be a string");
        }
    }

    /**
     * 检查参数为整数
     *
     * @param mixed $value
     * @param string $paramName
     * @return void
     * @throws InvalidArgumentException
     */
    public static function isInt($value, string $paramName): void
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException("param_illegal: {$paramName} must be an integer");
        }
    }

    /**
     * 检查参数为数组
     *
     * @param mixed $value
     * @param string $paramName
     * @return void
     * @throws InvalidArgumentException
     */
    public static function isArray($value, string $paramName): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException("param_illegal: {$paramName} must be an array");
        }
    }

    /**
     * 检查参数为布尔值
     *
     * @param mixed $value
     * @param string $paramName
     * @return void
     * @throws InvalidArgumentException
     */
    public static function isBool($value, string $paramName): void
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException("param_illegal: {$paramName} must be a boolean");
        }
    }
}
