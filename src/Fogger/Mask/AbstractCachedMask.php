<?php

namespace App\Fogger\Mask;

use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractCachedMask extends AbstractMask
{
    private const LOCK_VALUE = 'fogger::pending';

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    abstract protected function getSubstitution(array $options = []): ?string;

    /**
     * @param null|string $value
     * @param array $options
     * @return null|string
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function apply(?string $value, array $options = []): ?string
    {
        if (null === $value) {
            return $value;
        }

        do {
            $originalValueCacheItem = $this->cache->getItem(md5($value));
        } while ($originalValueCacheItem->get() === self::LOCK_VALUE);

        if ($originalValueCacheItem->isHit()) {
            return $originalValueCacheItem->get();
        } else {
            $originalValueCacheItem->set(self::LOCK_VALUE);
            $this->cache->save($originalValueCacheItem);
        }

        do {
            $substitution = $this->getSubstitution($options);
            $substitutionCacheItem = $this->cache->getItem(md5($substitution));
        } while ($substitutionCacheItem->isHit());
        $this->cache->save($substitutionCacheItem);

        $originalValueCacheItem->set($substitution);
        $this->cache->save($originalValueCacheItem);

        return $substitution;
    }
}
