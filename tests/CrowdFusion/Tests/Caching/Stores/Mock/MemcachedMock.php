<?php

namespace CrowdFusion\Tests\Caching\Stores\Mock;

use Symfony\Component\HttpKernel\Tests\Profiler\Mock\MemcachedMock as BaseMemcachedMock;

class MemcachedMock extends BaseMemcachedMock
{
    /** @var array */
    private $servers = [];

    /**
     * Creates the mock object and includes it.  eliminates the need to modify the
     * cache store class in the CF framework.
     *
     * @param int $haveIgbinary
     * @param bool $awsClient
     */
    public static function create($haveIgbinary = 0, $awsClient = false)
    {
        if ($awsClient) {
            $awsConstants = <<<CODE
    const OPT_CLIENT_MODE     = -5000;
    const DYNAMIC_CLIENT_MODE = 1;
    const STATIC_CLIENT_MODE  = 2;
CODE;
        } else {
            $awsConstants = '// not simulating aws client' . PHP_EOL;
        }

        $php = <<<CODE
<?php
class Memcached extends CrowdFusion\Tests\Caching\Stores\Mock\MemcachedMock
{
    const OPT_PREFIX_KEY           = -1002;
    const OPT_COMPRESSION          = -1001;
    const OPT_DISTRIBUTION         = 9;
    const DISTRIBUTION_CONSISTENT  = 1;
    const OPT_LIBKETAMA_COMPATIBLE = 16;
    const DISTRIBUTION_MODULA      = 0;

    const HAVE_IGBINARY            = {$haveIgbinary};
    const OPT_SERIALIZER           = -1003;
    const SERIALIZER_IGBINARY      = 2;

    {$awsConstants}
}
CODE;
        $filename = sys_get_temp_dir() . '/' . uniqid() . '.tmp';
        file_put_contents($filename, $php);
        include $filename;
        unlink($filename);
    }

    /**
     * Adds memcached servers to connection pool
     *
     * @param array $servers
     *
     * @return bool
     */
    public function addServers(array $servers = [])
    {
        $this->servers = $servers;
        foreach ($this->servers as $server) {
            $this->addServer($server['host'], $server['port'], $server['weight']);
        }

        return true;
    }

    /**
     * Returns an array of keys or false on failure.
     *
     * @param array $keys
     * @return array|false
     */
    public function getMulti(array $keys)
    {
        $results = [];
        foreach ($keys as $key) {
            $value = $this->get($key);
            if (false !== $value) {
                $results[$key] = $value;
            }
        }

        if (!count($results)) {
            return false;
        }

        return $results;
    }

    /**
     * Returns the servers in the connection pool.
     *
     * @return array
     */
    public function getServerList()
    {
        return $this->servers;
    }

    /**
     * @link http://php.net/manual/en/memcached.getresultcode.php
     *
     * @return int
     */
    public function getResultCode()
    {
        return 0;
    }
}
