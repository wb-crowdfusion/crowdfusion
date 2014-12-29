<?php

abstract class AbstractCacheStore
{
    /** @var string */
    protected $cachePrefixKey = '';

    /**
     * Generates the key to store based on the cachePrefix and given key
     *
     * @param string $key The key we'll use to generate our key
     *
     * @return string A unique key with cacheprefix
     */
    protected function key($key)
    {
        return md5($this->cachePrefixKey . $key);
    }

    /**
     * Defines the array that will be used to store data.
     * Changing this will require updating all functions that also fetch data.
     *
     * At no point after this function is called should the data be manipulated,
     * only inserted directly into cache.
     *
     * @param string $key  Key name
     * @param string $data Data to store
     * @param int $ttl  Duration in seconds for data to live in cache
     *
     * @return array An that will be stored in the cache.
     */
    protected function storageFormat($key, $data, $ttl)
    {
        $start  = time();
        $expire = $start + $ttl;

        return array('key' => $key, 'value' => $data, 'duration' => $ttl, 'created' => $start, 'expires' => $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        throw new CacheException('getStats is not supported for this method');
    }
}
