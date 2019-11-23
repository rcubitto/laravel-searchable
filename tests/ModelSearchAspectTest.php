<?php

namespace Spatie\Searchable\Tests;

use ReflectionObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\Tests\Models\TestModel;
use Spatie\Searchable\Exceptions\InvalidSearchableModel;
use Spatie\Searchable\Exceptions\InvalidModelSearchAspect;
use Spatie\Searchable\Tests\Models\TestComment;

class ModelSearchAspectTest extends TestCase
{
    /** @test */
    public function it_can_perform_a_search()
    {
        TestModel::createWithName('john');
        TestModel::createWithName('jane');

        $searchAspect = ModelSearchAspect::forModel(TestModel::class, 'name');

        $results = $searchAspect->getResults('john');

        $this->assertCount(1, $results);
        $this->assertInstanceOf(TestModel::class, $results[0]);
    }

    /** @test */
    public function it_can_perform_a_search_on_multiple_columns()
    {
        TestModel::createWithNameAndLastName('jane', 'doe');
        TestModel::createWithNameAndLastName('Taylor', 'Otwell');

        $searchAspect = ModelSearchAspect::forModel(TestModel::class, 'name', 'last_name');

        $results = $searchAspect->getResults('Taylor Otwell');

        $this->assertCount(1, $results);
        $this->assertInstanceOf(TestModel::class, $results[0]);
    }

    /** @test */
    public function it_can_add_searchable_attributes()
    {
        $searchAspect = ModelSearchAspect::forModel(TestModel::class)
            ->addSearchableAttribute('name', true)
            ->addSearchableAttribute('email', false);

        $refObject = new ReflectionObject($searchAspect);
        $refProperty = $refObject->getProperty('attributes');
        $refProperty->setAccessible(true);
        $attributes = $refProperty->getValue($searchAspect);

        $this->assertTrue($attributes[0]->isPartial());
        $this->assertEquals('name', $attributes[0]->getAttribute());

        $this->assertFalse($attributes[1]->isPartial());
        $this->assertEquals('email', $attributes[1]->getAttribute());
    }

    /** @test */
    function it_can_add_relationships_to_be_eager_loaded()
    {
        $searchAspect = ModelSearchAspect::forModel(TestModel::class)
            ->with('foo', 'bar')
            ->with('baz');

        $refObject = new ReflectionObject($searchAspect);
        $refProperty = $refObject->getProperty('eagerLoad');
        $refProperty->setAccessible(true);
        $eagerLoads = $refProperty->getValue($searchAspect);

        $this->assertEquals('foo', $eagerLoads[0]);
        $this->assertEquals('bar', $eagerLoads[1]);
        $this->assertEquals('baz', $eagerLoads[2]);
    }

    /** @test */
    function it_can_add_scopes()
    {
        $searchAspect = ModelSearchAspect::forModel(TestModel::class)
            ->active()
            ->role('admin');

        $refObject = new ReflectionObject($searchAspect);
        $refProperty = $refObject->getProperty('scopes');
        $refProperty->setAccessible(true);
        $scopes = $refProperty->getValue($searchAspect);

        $this->assertArrayHasKey('active', $scopes);
        $this->assertEquals([], $scopes['active']);
        $this->assertArrayHasKey('role', $scopes);
        $this->assertEquals(['admin'], $scopes['role']);
    }

    /** @test */
    public function it_can_build_an_eloquent_query()
    {
        $searchAspect = ModelSearchAspect::forModel(TestModel::class)
            ->addSearchableAttribute('name', true)
            ->addExactSearchableAttribute('email');

        DB::enableQueryLog();

        $searchAspect->getResults('john');

        $expectedQuery = 'select * from "test_models" where (LOWER(name) LIKE ? or "email" = ?)';

        $executedQuery = Arr::get(DB::getQueryLog(), '0.query');

        $this->assertEquals($expectedQuery, $executedQuery);
    }

    /** @test */
    function it_can_build_an_eloquent_query_with_eager_loads()
    {
        $model = TestModel::createWithName('john');

        $searchAspect = ModelSearchAspect::forModel(TestModel::class)
            ->addSearchableAttribute('name')
            ->with('comments');

        DB::enableQueryLog();

        $searchAspect->getResults('john');

        $expectedModelQuery = 'select * from "test_models" where (LOWER(name) LIKE ?)';
        $expectedEagersQuery = 'select * from "test_comments" where "test_comments"."test_model_id" in ('.$model->id.')';

        $executedModelQuery = Arr::get(DB::getQueryLog(), '0.query');
        $executedEagersQuery = Arr::get(DB::getQueryLog(), '1.query');

        $this->assertEquals($expectedModelQuery, $executedModelQuery);
        $this->assertEquals($expectedEagersQuery, $executedEagersQuery);
    }

    /** @test */
    function it_can_build_an_eloquent_query_with_scopes()
    {
        $searchAspect = ModelSearchAspect::forModel(TestModel::class)
            ->addSearchableAttribute('name', true)
            ->addExactSearchableAttribute('email')
            ->active();

        DB::enableQueryLog();

        $searchAspect->getResults('john');

        $expectedQuery = 'select * from "test_models" where "active" = ? and (LOWER(name) LIKE ? or "email" = ?)';

        $executedQuery = Arr::get(DB::getQueryLog(), '0.query');
        $scopeBinding = Arr::get(DB::getQueryLog(), '0.bindings.0');

        $this->assertEquals($expectedQuery, $executedQuery);
        $this->assertEquals(1, $scopeBinding);
    }

    /** @test */
    public function it_has_a_type()
    {
        $searchAspect = ModelSearchAspect::forModel(TestModel::class);

        $this->assertEquals('test_models', $searchAspect->getType());
    }

    /** @test */
    public function it_throws_an_exception_when_given_a_class_that_is_not_a_model()
    {
        $notEvenAModel = new class {
        };

        $this->expectException(InvalidSearchableModel::class);

        ModelSearchAspect::forModel(get_class($notEvenAModel));
    }

    /** @test */
    public function it_throws_an_exception_when_given_an_unsearchable_model()
    {
        $modelWithoutSearchable = new class extends Model {
        };

        $this->expectException(InvalidSearchableModel::class);

        ModelSearchAspect::forModel(get_class($modelWithoutSearchable));
    }

    /** @test */
    public function it_throws_an_exception_if_there_are_no_searchable_attributes()
    {
        $searchAspect = ModelSearchAspect::forModel(TestModel::class);

        $this->expectException(InvalidModelSearchAspect::class);

        $searchAspect->getResults('john');
    }
}
