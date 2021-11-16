<?php

namespace Iffutsius\LaravelRpc;

use Carbon\Carbon;
use Iffutsius\LaravelRpc\Traits\KnowsOwnName;

abstract class BaseClient
{
    use KnowsOwnName;

    /** @var static */
    protected $connection;

    /**
     * Rest constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @return static
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Initializes object and its settings (eg. loads connection)
     */
    protected function init()
    {
        $this->setup();
        $this->connection = $this->createConnection();
    }

    /**
     * Setup method for setting all required parameters for connecting to the service
     */
    protected function setup()
    {
    }

    /**
     * @param Carbon $date|null
     * @return string
     */
    public static function formatDateWithTimeOffset(Carbon $date = null)
    {
        $date = is_null($date) ? new Carbon() : $date;
        return $date->format('Y-m-d\TH:i:sP');
    }

    /**
     * @return static
     */
    abstract protected function createConnection();
}
