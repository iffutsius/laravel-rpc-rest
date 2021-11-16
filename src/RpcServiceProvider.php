<?php

namespace Iffutsius\LaravelRpc;

use Illuminate\Support\ServiceProvider;

class RpcServiceProvider extends ServiceProvider
{
    /**
     * Configuration file name.
     *
     * @var string
     */
    protected $configFileName = 'laravel-rpc-rest.php';

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig(__DIR__ . '/../config/' . $this->configFileName);
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path($this->configFileName);
    }

    /**
     * Publish the config file
     *
     * @param string $configPath
     */
    protected function publishConfig(string $configPath)
    {
        $this->publishes([$configPath => $this->getConfigPath()]);
    }
}
