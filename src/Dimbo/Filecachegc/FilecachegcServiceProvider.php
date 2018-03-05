<?php namespace Dimbo\Filecachegc;

use Illuminate\Support\ServiceProvider;

class FilecachegcServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->package('dimbo/filecachegc');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('command.cache.gc', function($app)
        {
            return new CollectFileCacheGarbageCommand();
        });

        $this->commands('command.cache.gc');
    }
}
