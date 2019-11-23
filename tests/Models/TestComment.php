<?php

namespace Spatie\Searchable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class TestComment extends Model implements Searchable
{
    protected $guarded = [];

    public function testModel()
    {
        return $this->belongsTo(TestModel::class);
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
