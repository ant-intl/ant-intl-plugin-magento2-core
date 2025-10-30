<?php

namespace Antom\Core\Test\Unit\Gateway\Request;

use Antom\Core\Gateway\Request\AmsEnvBuilder;
use Antom\Core\Gateway\AntomConstants;
use Antom\Core\Helper\RequestHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmsEnvBuilder
 */
class AmsEnvBuilderTest extends TestCase
{
    /**
     * @var AmsEnvBuilder
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
        $this->builder = new AmsEnvBuilder($this->requestHelperMock);
    }

    public function testBuildEnvInfo(): void
    {
        $expectedEnv = [
            'terminalType' => 'WEB',
            'clientIp' => '127.0.0.1',
            'language' => 'en-US',
            'acceptHeader' => 'text/html'
        ];

        $this->requestHelperMock->expects($this->once())
            ->method('composeEnvInfo')
            ->willReturn($expectedEnv);

        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::ENV, $result);
        $this->assertEquals($expectedEnv, $result[AntomConstants::ENV]);
    }

    public function testBuildReturnsEmptyEnvWhenHelperReturnsEmpty(): void
    {
        $this->requestHelperMock->expects($this->once())
            ->method('composeEnvInfo')
            ->willReturn([]);

        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertArrayHasKey(AntomConstants::ENV, $result);
        $this->assertEmpty($result[AntomConstants::ENV]);
    }

    public function testBuildWithMinimalEnvData(): void
    {
        $minimalEnv = [
            'terminalType' => 'WEB'
        ];

        $this->requestHelperMock->expects($this->once())
            ->method('composeEnvInfo')
            ->willReturn($minimalEnv);

        $buildSubject = ['payment' => $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)];
        $result = $this->builder->build($buildSubject);

        $this->assertEquals($minimalEnv, $result[AntomConstants::ENV]);
    }
}