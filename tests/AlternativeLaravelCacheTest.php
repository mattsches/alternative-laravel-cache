<?php

namespace Tests;

use AlternativeLaravelCache\Store\AlternativeFileCacheStore;
use AlternativeLaravelCache\Store\AlternativeRedisCacheStore;

class AlternativeLaravelCacheTest extends TestCase {

    /*
     * Config file cache.php:
        [
            'client' => 'predis' //< or 'phpredis'
            'stores' => [
                'test_file' => [
                    'driver' => 'file',
                    'path' => storage_path('framework/cache/data'),
                ],

                'test_hierarchial_file' => [
                    'driver' => 'hierarchial_file',
                    'path' => storage_path('framework/cache/data'),
                ],

                'test_redis' => [
                    'driver' => 'redis',
                    'connection' => 'cache',
                ],
            ]
            'prefix' => 'laravel/testcache'
        ]
     */

    public function testNormalCache() {
        \Cache::store('test_redis')->clear();
        \Cache::store('test_file')->clear();
        $key = 'key1|subkey/sskey\\ssskey';
        \Cache::store('test_redis')->put($key, 'value1', 1);
        $this->assertEquals('value1', \Cache::store('test_redis')->get($key));
        \Cache::store('test_file')->put($key, 'value2', 1);
        $this->assertEquals('value2', \Cache::store('test_file')->get($key));
    }

    public function testHierarchialFileCache() {
        \Cache::store('test_file')->clear();
        $key1 = 'key1|subkey1';
        \Cache::store('test_hierarchial_file')->put($key1, 'value1', 1);
        $this->assertEquals('value1', \Cache::store('test_hierarchial_file')->get($key1));
        $key2 = 'key1|subkey2';
        \Cache::store('test_hierarchial_file')->put($key2, 'value2', 1);
        $this->assertEquals('value2', \Cache::store('test_hierarchial_file')->get($key2));
        $key3 = 'key2|subkey1';
        \Cache::store('test_hierarchial_file')->put($key3, 'value3', 1);
        $this->assertEquals('value3', \Cache::store('test_hierarchial_file')->get($key3));
        \Cache::store('test_hierarchial_file')->delete('key1');
        $this->assertNull(\Cache::store('test_hierarchial_file')->get($key1));
        $this->assertNull(\Cache::store('test_hierarchial_file')->get($key2));
        $this->assertEquals('value3', \Cache::store('test_hierarchial_file')->get($key3));
    }

    public function testTaggedCache() {
        /** @var AlternativeRedisCacheStore $redisStore */
        $redisStore = \Cache::store('test_redis');
        /** @var AlternativeFileCacheStore $fileStore */
        $fileStore = \Cache::store('test_file');
        /** @var AlternativeFileCacheStore $hierarchialFileStore */
        $hierarchialFileStore = \Cache::store('test_hierarchial_file');

        $redisStore->flush();
        $fileStore->flush();
        $hierarchialFileStore->flush();

        $key1 = 'key1|subkey/sskey\\ssskey1';
        $key2 = 'key1|subkey/sskey\\ssskey2';

        $redisStore->tags(['tag1', 'tag2'])->put($key1, 'value1', 1);
        $this->assertEquals('value1', $redisStore->get($key1));
        $redisStore->tags(['tag3'])->put($key2, 'value11', 1);
        $this->assertEquals('value11', $redisStore->get($key2));

        $fileStore->tags(['tag1', 'tag2'])->put($key1, 'value2', 1);
        $this->assertEquals('value2', $fileStore->get($key1));
        $fileStore->tags(['tag3'])->put($key2, 'value22', 1);
        $this->assertEquals('value22', $fileStore->get($key2));

        $hierarchialFileStore->tags(['tag1', 'tag2'])->put($key1, 'value3', 1);
        $this->assertEquals('value3', $hierarchialFileStore->get($key1));
        $hierarchialFileStore->tags(['tag3'])->put($key2, 'value33', 1);
        $this->assertEquals('value33', $hierarchialFileStore->get($key2));

        $redisStore->tags(['tag1'])->flush();
        $fileStore->tags(['tag1'])->flush();
        $hierarchialFileStore->tags(['tag1'])->flush();

        $this->assertEquals('value11', $redisStore->get($key2));
        $this->assertEquals('value22', $fileStore->get($key2));
        $this->assertEquals('value33', $hierarchialFileStore->get($key2));
        $this->assertNull($redisStore->get($key1));
        $this->assertNull($fileStore->get($key1));
        $this->assertNull($hierarchialFileStore->get($key1));
    }
}
