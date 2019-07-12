<?php

namespace Spatie\LaravelEndpointResources\Tests\EndpointTypes;

use Spatie\LaravelEndpointResources\EndpointTypes\ControllerEndpointType;
use Spatie\LaravelEndpointResources\Formatters\LayeredFormatter;
use Spatie\LaravelEndpointResources\Tests\Fakes\TestController;
use Spatie\LaravelEndpointResources\Tests\Fakes\TestModel;
use Spatie\LaravelEndpointResources\Tests\Fakes\TestControllerWithSpecifiedEndpoints;
use Spatie\LaravelEndpointResources\Tests\TestCase;

class ControllerEndpointTypeTest extends TestCase
{
    /** @var \Spatie\LaravelEndpointResources\Tests\Fakes\TestModel */
    private $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testModel = TestModel::create([
            'id' => 1,
            'name' => 'TestModel',
        ]);
    }

    /** @test */
    public function it_will_only_give_local_endpoints()
    {
        $indexAction = [TestController::class, 'index'];
        $showAction = [TestController::class, 'show'];

        $this->fakeRouter->get('', $indexAction);
        $this->fakeRouter->get('{testModel}', $showAction);

        $endpointType = ControllerEndpointType::make(TestController::class);

        $endpoints = $endpointType->getEndpoints($this->testModel);

        $this->assertEquals([
            'show' => [
                'method' => 'GET',
                'action' => action($showAction, $this->testModel),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_will_only_give_collection_endpoints()
    {
        $indexAction = [TestController::class, 'index'];
        $showAction = [TestController::class, 'show'];

        $this->fakeRouter->get('', $indexAction);
        $this->fakeRouter->get('{testModel}', $showAction);

        $endpointType = ControllerEndpointType::make(TestController::class);

        $endpoints = $endpointType->getCollectionEndpoints();

        $this->assertEquals([
            'index' => [
                'method' => 'GET',
                'action' => action($indexAction),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_will_create_all_possible_routes_when_a_model_is_available()
    {
        $showAction = [TestController::class, 'show'];
        $updateAction = [TestController::class, 'update'];

        $this->fakeRouter->get('{testModel}', $showAction);
        $this->fakeRouter->route('PATCH', '{testModel}', $updateAction);

        $testModel = TestModel::create([
            'name' => 'TestModel',
        ]);

        $endpointType = ControllerEndpointType::make(TestController::class);

        $endpoints = $endpointType->getEndpoints($testModel);

        $this->assertEquals([
            'show' => [
                'method' => 'GET',
                'action' => action($showAction, $testModel),
            ],
            'update' => [
                'method' => 'PATCH',
                'action' => action($updateAction, $testModel),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_can_specify_which_methods_to_use()
    {
        $indexAction = [TestController::class, 'index'];
        $showAction = [TestController::class, 'show'];

        $this->fakeRouter->get('', $indexAction);
        $this->fakeRouter->get('{testModel}', $showAction);

        $endpoints = ControllerEndpointType::make(TestController::class)
            ->methods(['index', 'show'])
            ->getEndpoints($this->testModel);

        $this->assertEquals([
            'show' => [
                'method' => 'GET',
                'action' => action($showAction, $this->testModel),
            ],
            'index' => [
                'method' => 'GET',
                'action' => action($indexAction),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_can_alias_endpoints()
    {
        $indexAction = [TestController::class, 'index'];

        $this->fakeRouter->get('', $indexAction);

        $endpoints = ControllerEndpointType::make(TestController::class)
            ->methods(['index'])
            ->names([
                'index' => 'home',
            ])
            ->getEndpoints($this->testModel);

        $this->assertEquals([
            'home' => [
                'method' => 'GET',
                'action' => action($indexAction),
            ],
        ], $endpoints);
    }

    /** @test */
    public function it_can_prefix_endpoints()
    {
        $indexAction = [TestController::class, 'index'];

        $this->fakeRouter->get('', $indexAction);

        $endpoints = ControllerEndpointType::make(TestController::class)
            ->methods(['index'])
            ->prefix('this-')
            ->getEndpoints($this->testModel);

        $this->assertEquals([
            'this-index' => [
                'method' => 'GET',
                'action' => action($indexAction),
            ],
        ], $endpoints);
    }
    
    /** @test */
    public function it_will_merge_layered_formatted_endpoints()
    {
        $this->fakeRouter->get('/users/{testModel}', [TestController::class, 'show']);
        $this->fakeRouter->put('/users/{testModel}', [TestController::class, 'update']);

        $endpoints = ControllerEndpointType::make(TestController::class)
            ->methods(['show', 'update'])
            ->prefix('filter')
            ->formatter(LayeredFormatter::class)
            ->getEndpoints($this->testModel);

        $this->assertEquals([
            'filter' => [
                'show' => [
                    'method' => 'GET',
                    'action' => action([TestController::class, 'show'], $this->testModel),
                ],
                'update' => [
                    'method' => 'PUT',
                    'action' => action([TestController::class, 'update'], $this->testModel),
                ],
            ]
        ], $endpoints);
    }
    
    /** @test */
    public function a_controller_endpoint_type_can_have_no_endpoints()
    {
        $endpoints = ControllerEndpointType::make(TestController::class)
            ->getEndpoints($this->testModel);

        $this->assertEquals([], $endpoints);
    }
}
