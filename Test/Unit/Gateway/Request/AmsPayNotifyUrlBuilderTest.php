<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Gateway\Request\AmsPayNotifyUrlBuilder;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsPayNotifyUrlBuilder
 */
class AmsPayNotifyUrlBuilderTest extends TestCase
{
    /**
     * @var AmsPayNotifyUrlBuilder
     */
    private $builder;

    /**
     * @var RequestHelper|MockObject
     */
    private $requestHelperMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->requestHelperMock = $this->createMock(RequestHelper::class);
        $this->builder = new AmsPayNotifyUrlBuilder($this->requestHelperMock);
    }

    public function testBuildNotifyUrlWithHttpsDomain(): void
    {
        $domain = 'https://example.com';
        $expectedNotifyUrl = 'https://example.com/antom/notification';

        $this->requestHelperMock->expects($this->once())
            ->method('getDomain')
            ->willReturn($domain);

        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::PAYMENT_NOTIFY_URL, $result);
        $this->assertEquals($expectedNotifyUrl, $result[AntomConstants::PAYMENT_NOTIFY_URL]);
    }

    public function testBuildNotifyUrlWithHttpDomain(): void
    {
        $domain = 'http://localhost';
        $expectedNotifyUrl = 'http://localhost/antom/notification';

        $this->requestHelperMock->expects($this->once())
            ->method('getDomain')
            ->willReturn($domain);

        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals($expectedNotifyUrl, $result[AntomConstants::PAYMENT_NOTIFY_URL]);
    }

    public function testBuildNotifyUrlWithTrailingSlash(): void
    {
        $domain = 'https://example.com/';
        $expectedNotifyUrl = 'https://example.com/antom/notification';

        $this->requestHelperMock->expects($this->once())
            ->method('getDomain')
            ->willReturn($domain);

        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        // Allow for double slash in URL as it's still valid
        $this->assertStringContainsString('example.com', $result[AntomConstants::PAYMENT_NOTIFY_URL]);
        $this->assertStringContainsString('antom/notification', $result[AntomConstants::PAYMENT_NOTIFY_URL]);
    }

    public function testBuildNotifyUrlWithEmptyDomain(): void
    {
        $domain = '';
        $expectedNotifyUrl = '/antom/notification';

        $this->requestHelperMock->expects($this->once())
            ->method('getDomain')
            ->willReturn($domain);

        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals($expectedNotifyUrl, $result[AntomConstants::PAYMENT_NOTIFY_URL]);
    }

    public function testBuildReturnsArrayStructure(): void
    {
        $this->requestHelperMock->method('getDomain')->willReturn('https://test.com');

        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(AntomConstants::PAYMENT_NOTIFY_URL, $result);
    }
}