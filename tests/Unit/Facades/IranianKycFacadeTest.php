<?php

namespace Saman9074\IranianValidationSuite\Tests\Unit\Facades;

use Saman9074\IranianValidationSuite\Facades\IranianKyc;
use Saman9074\IranianValidationSuite\Services\Kyc\KycManager;
use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\UIdShahkarDriver;
use Saman9074\IranianValidationSuite\Contracts\Kyc\ShahkarInterface;
use Saman9074\IranianValidationSuite\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class IranianKycFacadeTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Configuration for tests - these are still needed for the KycManager if it were real
        $app['config']->set('iranian-validation-suite.kyc.default_driver', 'uid');
        $app['config']->set('iranian-validation-suite.kyc.services.shahkar.default_driver', 'uid');
        $app['config']->set('iranian-validation-suite.kyc.drivers.uid.business_id', 'test_business_id');
        $app['config']->set('iranian-validation-suite.kyc.drivers.uid.business_token', 'test_business_token');
    }

    /** @test */
    public function kyc_manager_is_bound_in_container(): void
    {
        $this->assertTrue($this->app->bound(KycManager::class));
        $this->assertInstanceOf(KycManager::class, $this->app->make(KycManager::class));
    }

    /**
     * @test
     * @depends kyc_manager_is_bound_in_container
     */
    public function facade_resolves_to_kyc_manager_instance(): void
    {
        $this->assertInstanceOf(KycManager::class, IranianKyc::getFacadeRoot());
    }

    /**
     * @test
     * @depends kyc_manager_is_bound_in_container
     */
    public function facade_can_call_service_and_get_correct_driver(): void
    {
        /** @var UIdShahkarDriver|MockInterface $mockedDriver */
        $mockedDriver = Mockery::mock(UIdShahkarDriver::class); // This driver should implement ShahkarInterface

        /** @var KycManager|MockInterface $managerMock */
        $managerMock = Mockery::mock(KycManager::class); // Create a full mock of KycManager
        // We expect the 'service' method on our KycManager mock to be called with 'shahkar'
        // and it should return our $mockedDriver.
        $managerMock->shouldReceive('service')->with('shahkar')->once()->andReturn($mockedDriver);

        IranianKyc::swap($managerMock); // Swap the Facade to use our mocked KycManager

        $shahkarService = IranianKyc::service('shahkar'); // This will call service() on $managerMock

        $this->assertInstanceOf(ShahkarInterface::class, $shahkarService);
        $this->assertSame($mockedDriver, $shahkarService);
    }

    /**
     * @test
     * @depends kyc_manager_is_bound_in_container
     */
    public function facade_can_call_method_on_resolved_service_driver(): void
    {
        $nationalId = '0012345678';
        $mobile = '09120000000';

        /** @var UIdShahkarDriver|MockInterface $mockedDriver */
        $mockedDriver = Mockery::mock(UIdShahkarDriver::class); // This driver should implement ShahkarInterface
        $mockedDriver->shouldReceive('matchMobileNationalId')
            ->with($nationalId, $mobile)
            ->once()
            ->andReturn(true);

        /** @var KycManager|MockInterface $managerMock */
        $managerMock = Mockery::mock(KycManager::class);
        // We expect the 'service' method on $managerMock to be called, returning $mockedDriver
        $managerMock->shouldReceive('service')->with('shahkar')->once()->andReturn($mockedDriver);

        IranianKyc::swap($managerMock);

        // When IranianKyc::service('shahkar') is called, it calls $managerMock->service('shahkar'),
        // which returns $mockedDriver. Then, matchMobileNationalId is called on $mockedDriver.
        $result = IranianKyc::service('shahkar')->matchMobileNationalId($nationalId, $mobile);

        $this->assertTrue($result);
    }

    /**
     * @test
     * @depends kyc_manager_is_bound_in_container
     */
    public function facade_can_call_default_driver_method_via_magic_call(): void
    {
        $nationalId = '0087654321';
        $mobile = '09350000000';

        /** @var KycManager|MockInterface $kycManagerMock */
        $kycManagerMock = Mockery::mock(KycManager::class);
        // We are testing the Facade's __call. The Facade's __call will invoke the method
        // on the KycManager instance. Since KycManager extends Illuminate\Support\Manager,
        // its __call method will try to resolve the default driver and call the method on it.
        // Instead of mocking the whole chain (getDefaultDriver, driver, createDriver),
        // we can directly mock the expected method call on the KycManager mock itself,
        // assuming the default driver would handle 'matchMobileNationalId'.
        // This simplifies the test to focus on the Facade-to-Manager proxying.
        $kycManagerMock->shouldReceive('matchMobileNationalId')
            ->with($nationalId, $mobile)
            ->once()
            ->andReturn(false); // Simulate a non-match from the (default) driver

        IranianKyc::swap($kycManagerMock);

        $result = IranianKyc::matchMobileNationalId($nationalId, $mobile);
        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        if (class_exists(IranianKyc::class)) {
            IranianKyc::clearResolvedInstances();
        }
        Mockery::close();
        parent::tearDown();
    }
}
