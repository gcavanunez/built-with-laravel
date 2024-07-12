<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class OrganizationController extends Controller
{
    public function index()
    {
        return view('organizations.index', [
            'organizations' => $this->organizations(),
            'technologies' => $this->technologies(),
            'filterTechnology' => null,
        ]);
    }

    public function indexByTechnology(string $filterTechnology)
    {
        return view('organizations.index', [
            'organizations' => $this->organizations($filterTechnology),
            'technologies' => $this->technologies(),
            'filterTechnology' => $filterTechnology,
        ]);
    }

    private function organizations(?string $filterTechnology = null)
    {
        return Cache::remember('orgs-list-filter[' . $filterTechnology . ']', 3600, function () use ($filterTechnology) {
            return Organization::when(! is_null($filterTechnology), function (Builder $query) use ($filterTechnology) {
                $query->whereHas('technologies', function (Builder $query) use ($filterTechnology) {
                    $query->where('slug', $filterTechnology);
                });
            })->with('sites') // @todo: Do a subquery for just the first site aaron francis style?
                ->orderBy('featured_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    private function technologies()
    {
        return Cache::remember('active-organizations', 3600, function () {
            return Technology::whereHas('organizations')->orderBy('name')->get();
        });
    }
}
