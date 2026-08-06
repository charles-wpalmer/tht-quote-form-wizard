<?php

namespace App\Providers;

use App\Domains\Journey\Adapters\EloquentJourneyRepository;
use App\Domains\Journey\Ports\JourneyRepository;
use App\Domains\Product\Adapters\EloquentProductRequirementsProvider;
use App\Domains\Product\Ports\ProductRequirementsProvider;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRequirementsProvider::class, EloquentProductRequirementsProvider::class);
        $this->app->bind(JourneyRepository::class, EloquentJourneyRepository::class);
    }
}
