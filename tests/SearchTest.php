<?php

namespace Spatie\Searchable\Tests;

use ReflectionObject;
use Illuminate\Support\Arr;
use Spatie\Searchable\Search;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\Tests\Models\TestModel;
use Spatie\Searchable\Tests\stubs\CustomNameSearchAspect;

class SearchTest extends TestCase
{
    /** @test */
    public function it_can_search_a_model_search_aspect()
    {
        TestModel::createWithName('john doe');
        TestModel::createWithName('alex');

        $search = new Search();

        $search->registerModel(TestModel::class, 'name');

        $results = $search->perform('doe');

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('test_models', $results->groupByType());
        $this->assertCount(1, $results->aspect('test_models'));
    }

    /** @test */
    public function it_can_register_a_model_search_aspect_attribute_thats_also_a_global_function()
    {
        $search = new Search();

        $search->registerModel(TestModel::class, 'phpinfo');

        $this->assertCount(1, $search->getSearchAspects());
    }

    /** @test */
    public function a_model_search_aspect_can_be_configured_using_a_closure()
    {
        TestModel::createWithName('john doe');
        TestModel::createWithName('alex');

        $search = new Search();

        $search->registerModel(TestModel::class, function (ModelSearchAspect $modelSearchAspect) {
            return $modelSearchAspect->addSearchableAttribute('name');
        });

        $results = $search->perform('doe');

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('test_models', $results->groupByType());
        $this->assertCount(1, $results->aspect('test_models'));
    }

    /** @test */
    public function it_can_search_a_custom_search_aspect()
    {
        $search = new Search();

        $search->registerAspect(CustomNameSearchAspect::class);

        $results = $search->perform('doe');

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('custom_names', $results->groupByType());
        $this->assertCount(2, $results->aspect('custom_names'));
    }

    /** @test */
    public function it_can_search_multiple_aspects_together()
    {
        TestModel::createWithName('alex doe');
        TestModel::createWithName('jenna');

        $search = new Search();

        $search->registerAspect(CustomNameSearchAspect::class);
        $search->registerModel(TestModel::class, 'name');

        $results = $search->perform('doe');

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('custom_names', $results->groupByType());
        $this->assertArrayHasKey('test_models', $results->groupByType());
        $this->assertCount(2, $results->aspect('custom_names'));
        $this->assertCount(1, $results->aspect('test_models'));
    }

    /** @test */
    public function it_can_register_a_class_name_as_search_aspect()
    {
        $search = (new Search())->registerAspect(CustomNameSearchAspect::class);

        $aspects = $search->getSearchAspects();

        $this->assertCount(1, $aspects);
        $this->assertInstanceOf(CustomNameSearchAspect::class, Arr::first($aspects));
    }

    /** @test */
    public function it_can_register_search_aspect()
    {
        $aspect = new CustomNameSearchAspect();

        $search = (new Search())->registerAspect($aspect);

        $aspects = $search->getSearchAspects();

        $this->assertCount(1, $aspects);
        $this->assertInstanceOf(CustomNameSearchAspect::class, Arr::first($aspects));
    }

    /** @test */
    public function it_can_register_a_model_search_aspect()
    {
        $search = new Search();

        $search->registerModel(TestModel::class);

        $aspects = $search->getSearchAspects();

        $this->assertCount(1, $aspects);
        $this->assertInstanceOf(ModelSearchAspect::class, Arr::first($aspects));
        $this->assertEquals('test_models', Arr::first($aspects)->getType());
    }


    /** @test */
    function it_can_fluently_register_a_model_search_aspect()
    {
        $search = new Search();

        $search->model(TestModel::class, 'name')->register();

        $aspects = $search->getSearchAspects();

        $this->assertCount(1, $aspects);
        $this->assertInstanceOf(ModelSearchAspect::class, Arr::first($aspects));
        $this->assertEquals('test_models', Arr::first($aspects)->getType());
    }

    /** @test */
    public function it_can_register_a_model_search_aspect_with_attributes()
    {
        $search = new Search();

        $search->registerModel(TestModel::class, 'name', 'email');

        $aspect = Arr::first($search->getSearchAspects());

        $refObject = new ReflectionObject($aspect);
        $refProperty = $refObject->getProperty('attributes');
        $refProperty->setAccessible(true);
        $attributes = $refProperty->getValue($aspect);

        $this->assertCount(2, $attributes);
    }

    /** @test */
    public function it_can_register_a_model_search_aspect_with_an_array_of_attributes()
    {
        $search = new Search();

        $search->registerModel(TestModel::class, ['name', 'email']);

        $aspect = Arr::first($search->getSearchAspects());

        $refObject = new ReflectionObject($aspect);
        $refProperty = $refObject->getProperty('attributes');
        $refProperty->setAccessible(true);
        $attributes = $refProperty->getValue($aspect);

        $this->assertCount(2, $attributes);
    }

    /** @test */
    public function it_can_register_a_model_search_aspect_with_a_attributes_from_a_callback()
    {
        $search = new Search();

        $search->registerModel(TestModel::class, function (ModelSearchAspect $modelSearchAspect) {
            $modelSearchAspect
                ->addSearchableAttribute('name')
                ->addExactSearchableAttribute('email');
        });

        $aspect = Arr::first($search->getSearchAspects());

        $refObject = new ReflectionObject($aspect);
        $refProperty = $refObject->getProperty('attributes');
        $refProperty->setAccessible(true);
        $attributes = $refProperty->getValue($aspect);

        $this->assertCount(2, $attributes);
    }
}
