<?php

class MemcachedCacheStore extends AbstractCacheStore implements CacheStoreInterface, AdvancedCacheStoreInterface
{
    /** @var bool */
    protected $enabled = false;

    /** @var Memcached */
    protected $memcached;

    /** @var LoggerInterface */
    protected $Logger;

    /**
     * @link http://php.net/manual/en/memcached.addservers.php
     * @var array
     */
    protected $servers = [];

    /**
     * Sets the client mode to Memcached::DYNAMIC_CLIENT_MODE, which is only possible
     * if the aws php memcached extension is installed.  Option is ignored
     * if the constant DYNAMIC_CLIENT_MODE is not available.
     *
     * @see Memcached::OPT_CLIENT_MODE
     * @link http://docs.aws.amazon.com/AmazonElastiCache/latest/UserGuide/AutoDiscovery.html
     *
     * @var bool
     */
    protected $dynamicClientMode = false;

    /**
     * Sets the serializer option to Memcached::SERIALIZER_IGBINARY if the Memcached::HAVE_IGBINARY
     * return true AND this option is enabled.
     *
     * @see Memcached::OPT_SERIALIZER
     * @link http://php.net/manual/en/memcached.constants.php
     *
     * @var bool
     */
    protected $binary = false;

    /**
     * Enables the compression option
     * @see Memcached::OPT_COMPRESSION
     *
     * @var bool
     */
    protected $compression = false;

    /**
     * Sets the distribution option to Memcached::DISTRIBUTION_CONSISTENT
     * @see Memcached::OPT_DISTRIBUTION
     *
     * @var bool
     */
    protected $distributionConsistent = true;

    /**
     * @link http://php.net/manual/en/memcached.construct.php
     *
     * @var string
     */
    protected $persistentId;

    /**
     * @link http://php.net/manual/en/memcached.addservers.php
     *
     * @param LoggerInterface $Logger
     * @param array  $servers
     * @param string $cachePrefixKey
     * @param bool   $enabled
     * @param string $persistentId
     * @param bool   $compression
     * @param bool   $dynamicClientMode
     * @param bool   $distributionConsistent
     * @param bool   $binary
     *
     * @throws CacheException
     */
    public function __construct(
            LoggerInterface $Logger,
            array $servers,
            $cachePrefixKey,
            $enabled = false,
            $persistentId = null,
            $compression = false,
            $dynamicClientMode = false,
            $distributionConsistent = true,
            $binary = false
    ) {
        $this->Logger                 = $Logger;
        $this->cachePrefixKey         = str_replace(' ', '', $cachePrefixKey);
        $this->enabled                = $enabled;
        $this->persistentId           = $persistentId;
        $this->compression            = $compression;
        $this->dynamicClientMode      = $dynamicClientMode;
        $this->distributionConsistent = $distributionConsistent;
        $this->binary                 = $binary;

        /*
         * combine all options and append to persistentId so if the options
         * happen to change, a new persistent connection is created immediately.
         */
        if (null !== $this->persistentId) {
            $this->persistentId .= "-c{$this->compression}-dcm{$this->dynamicClientMode}-dc{$this->distributionConsistent}-b{$this->binary}";
        }

        /*
         * normalize the server list and options and de-duplicate
         */
        foreach ($servers as $server) {
            $host = isset($server['host']) ? (string) $server['host'] : '127.0.0.1';
            if ('localhost' == $host) {
                $host = '127.0.0.1';
            }
            $port = isset($server['port']) ? (int) $server['port'] : 11211;
            $weight = isset($server['weight']) ? (int) $server['weight'] : 1;
            $this->servers[$host . $port . $weight] = ['host' => $host, 'port' => $port, 'weight' => $weight];
        }

        if ($this->enabled) {
            if (!class_exists('Memcached')) {
                throw new CacheException("[{$this->persistentId}] Memcached extension not installed.");
            }
        } else {
            $this->enabled = false;
        }
    }

    /**
     * @return Memcached
     * @throws CacheException
     */
    protected function getConnection()
    {
        if (null === $this->memcached) {
            $memcached = new Memcached($this->persistentId);
            $this->setMemcachedOptions($memcached);

            if (!count($this->servers)) {
                throw new CacheException("[{$this->persistentId}] At least one server must be specified.");
            }

            if (null === $this->persistentId) {
                if (!$memcached->addServers($this->servers)) {
                    throw new CacheException("[{$this->persistentId}] Unable to add memcache servers [{$memcached->getResultCode()}:{$memcached->getResultMessage()}].");
                }
            } else {
                $currentServers = $memcached->getServerList();
                if (!count($currentServers)) {
                    $attempts = 0;
                    while (true) {
                        $attempts++;
                        if ($attempts > 3) {
                            throw new CacheException("[{$this->persistentId}] Unable to add persistent memcache servers [{$memcached->getResultCode()}:{$memcached->getResultMessage()}].");
                        }
                        if (!$memcached->addServers($this->servers)) {
                            $this->Logger->error("[{$this->persistentId}] Unable to add persistent memcache servers [{$memcached->getResultCode()}:{$memcached->getResultMessage()}].");
                            $memcached->resetServerList();
                            $this->setMemcachedOptions($memcached);
                            usleep(pow(2, $attempts) * 100000);
                        } else {
                            break;
                        }
                    }
                } else {
                    $this->Logger->debug("[{$this->persistentId}] Servers already on persistent connection: " . print_r($currentServers, true));
                }
            }

            $this->memcached = $memcached;
        }

        return $this->memcached;
    }

    /**
     * @param Memcached $memcached
     * @throws CacheException
     */
    private function setMemcachedOptions(Memcached $memcached)
    {
        $memcached->setOption(Memcached::OPT_COMPRESSION, $this->compression);

        if ($this->distributionConsistent) {
            $memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
            $memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            $this->Logger->debug("[{$this->persistentId}] Set OPT_DISTRIBUTION to DISTRIBUTION_CONSISTENT");
        } else {
            $memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_MODULA);
            $this->Logger->debug("[{$this->persistentId}] Set OPT_DISTRIBUTION to DISTRIBUTION_MODULA");
        }

        $this->Logger->debug("[{$this->persistentId}] HAVE_IGBINARY = " . (Memcached::HAVE_IGBINARY ? 'yes' : 'no'));
        if (Memcached::HAVE_IGBINARY && $this->binary) {
            $memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
            $this->Logger->debug("[{$this->persistentId}] Set OPT_SERIALIZER to SERIALIZER_IGBINARY");
        }

        // If we're using AWS auto discovery client, set the client mode.
        if (defined('Memcached::DYNAMIC_CLIENT_MODE')) {
            if ($this->dynamicClientMode) {
                if (count($this->servers) > 1) {
                    throw new CacheException("[{$this->persistentId}] There should be only 1 server (the configuration endpoint) when using auto discovery.");
                }
                $memcached->setOption(Memcached::OPT_CLIENT_MODE, Memcached::DYNAMIC_CLIENT_MODE);
                $this->Logger->debug("[{$this->persistentId}] Set OPT_CLIENT_MODE to DYNAMIC_CLIENT_MODE");
            } else {
                $memcached->setOption(Memcached::OPT_CLIENT_MODE, Memcached::STATIC_CLIENT_MODE);
                $this->Logger->debug("[{$this->persistentId}] Set OPT_CLIENT_MODE to STATIC_CLIENT_MODE");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey($key)
    {
        return false !== $this->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!$this->enabled) {
            return false;
        }

        $data = @$this->getConnection()->get($this->key($key));

        if ($data !== false) {
            // if going to expire in 10 seconds, extend the cache and let this request refresh it
            $duration = $data['duration'];
            $value    = $data['value'];
            if (!empty($value) && $duration > 0) {
                $expire = $data['expires'];
                $now    = time();

                if ($now > ($expire - 10) ) {
                    $this->Logger->debug("[{$this->persistentId}] Dogpile prevention on key [{$key}]");
                    $this->put($key, $value, $duration);
                    return false;
                }
            }
            $data = $value;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function multiGet(array $keys)
    {
        if (!$this->enabled) {
            return false;
        }

        foreach ($keys as &$key) {
            $key = $this->key($key);
        }

        $data = @$this->getConnection()->getMulti($keys);

        if ($data !== false) {
            $results = array();
            foreach ((array) $data as $storedArray) {
                $results[$storedArray['key']] = $storedArray['value'];
            }

            unset($data);
            unset($keys);
            return $results;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCachedObject($key)
    {
        if (!$this->enabled) {
            return false;
        }

        $data = @$this->getConnection()->get($this->key($key));

        if (false === $data) {
            return false;
        }

        $obj = new CachedObject($data['key'], $data['value'], $data['created'], $data['expires'], $data['duration']);
        return $obj;
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $data, $ttl)
    {
        if (!$this->enabled) {
            return true;
        }

        $hasReplaced = $hasSet = false;

        try {
            $data = $this->storageFormat($key, $data, $ttl);
            $nKey = $this->key($key);

            $hasReplaced = @$this->getConnection()->replace($nKey, $data, $ttl);
            if (!$hasReplaced) {
                $hasSet = @$this->getConnection()->set($nKey, $data, $ttl);
            }
        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

        $success = $hasReplaced || $hasSet;
        $this->Logger->debug("[{$this->persistentId}] " . ($success ? "Successful" : "Failed to") . " set: '{$key}', Hash: '{$nKey}'");

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDuration($key, $duration)
    {
        if (!$this->enabled) {
            return true;
        }

        if (!is_numeric($duration) || $duration < 0) {
            throw new CacheException("[{$this->persistentId}] Invalid Duration: {$duration}");
        }

        $value = $this->get($key);

        if (!$value) {
            return false;
        }

        return $this->put($key, $value, $duration);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        if (!$this->enabled) {
            return true;
        }

        $ret = @$this->getConnection()->delete($this->key($key));
        $this->Logger->debug("[{$this->persistentId}] " . ($ret ? "Successful" : "Failed to") . " delete: '{$key}'");

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        if (!$this->enabled) {
            return [];
        }

        $stats = @$this->getConnection()->getStats();
        $this->Logger->info($stats);
        return $stats;
    }

    /**
     * {@inheritdoc}
     */
    public function expireAll()
    {
        if (!$this->enabled) {
            return true;
        }

        if (@$this->getConnection()->flush()) {
            $time = time() + 1;
            while (time() < $time) {
                // Wait until the next second, so we can be assured our flush() won't carry icky side effects.
                // See: http://us3.php.net/manual/en/function.memcache-flush.php#81420
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flushExpired()
    {
        $this->Logger->info('Handled automatically.');
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function increment($key, $step = 1)
    {
        if (!$this->enabled) {
            return $step;
        }

        $hasAdded = $hasSet = false;

        try {
            $nKey = $this->key($key);
            $hasAdded = @$this->getConnection()->add($nKey, $step, 0);
            if (!$hasAdded) {
                $hasSet = @$this->getConnection()->increment($nKey, $step);
            }
        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

        $success = $hasAdded || $hasSet;
        $this->Logger->debug("[{$this->persistentId}] " . ($success ? "Successful" : "Failed to") . " increment key: '{$key}', Hash: '{$nKey}'");

        return $hasAdded ? $step : $hasSet;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement($key, $step = 1)
    {
        if (!$this->enabled) {
            return 0;
        }

        $hasSet = false;

        try {
            $nKey = $this->key($key);
            $hasSet = @$this->getConnection()->decrement($nKey, $step);
        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

        $success = false !== $hasSet;
        $this->Logger->debug("[{$this->persistentId}] " . ($hasSet ? "Successful" : "Failed to") . " decrement key: '{$key}', Hash: '{$nKey}'");

        return $success ? $hasSet : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getIncrement($key)
    {
        if (!$this->enabled) {
            return false;
        }

        $value = @$this->getConnection()->get($this->key($key));
        return false === $value ? $value : (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function add($key, $data, $ttl)
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $data = $this->storageFormat($key, $data, $ttl);
            $nKey = $this->key($key);
            $ret = @$this->getConnection()->add($nKey, $data, $ttl);
            $this->Logger->debug("[{$this->persistentId}] " . ($ret ? "Successful" : "Failed to") . " add: '{$key}', Hash: '{$nKey}'");
            return $ret;
        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }
    }
}
