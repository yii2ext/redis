<?php
/*
* This file is part of the yii2ext\redis library.
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace yii2ext\redis;
use Yii;
use yii\base\InvalidConfigException;

/**
 * A cache component that allows items to be cached using redis.
 *
 * @author Charles Pick
 * @link https://github.com/phpnode/YiiRedis/blob/master/ARedisCache.php
 *
 * @author Alex Zet <zetdev@gmail.com>
 * @package yii2ext\redis
 * @since 1.0
 */
class Cache extends \yii\caching\Cache
{
    /**
     * Holds the redis connection
     * @var Connection|string
     */
    protected $_connection='redis';

    /**
     * Sets the redis connection to use for caching
     * @param Connection|string $connection the redis connection, if a string is provided,
     *        it is presumed to be a the name of an application component
     */
    public function setConnection($connection)
    {
        if (is_string($connection)) {
            $connection = Yii::$app->{$connection};
        }
        $this->_connection = $connection;
    }

    /**
     * Gets the redis connection to use for caching
     * @return Connection
     * @throws InvalidConfigException
     */
    public function getConnection()
    {
        if (is_string($this->_connection)) {
            $this->_connection = Yii::$app->getComponent($this->_connection);
        }
        if (!$this->_connection instanceof Connection) {
            throw new InvalidConfigException('RedisSession::connection must refer to the application component ID of a connection object.');
        }
        return $this->_connection;
    }

    /**
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key a unique key identifying the cached value
     * @return string|boolean the value stored in cache, false if the value is not in the cache or expired.
     */
    protected function getValue($key)
    {
        return $this->getConnection()->getClient()->get($key);
    }

    /**
     * Retrieves multiple values from cache with the specified keys.
     * This is the implementation of the method declared in the parent class.
     * @param array $keys a list of keys identifying the cached values
     * @return array a list of cached values indexed by the keys
     */
    protected function getValues($keys)
    {
        return $this->getConnection()->getClient()->mget($keys);
    }

    /**
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key, $value, $expire=0)
    {
        $result = $this->getConnection()->getClient()->set($key, $value);
        if ($result && $expire) {
            $expire += time();
            $this->getConnection()->getClient()->expire($key, $expire);
        }
        return $result;
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    protected function addValue($key, $value, $expire=0)
    {
        $result = $this->getConnection()->getClient()->setnx($key, $value);
        if ($result && $expire) {
            $expire += time();
            $this->getConnection()->getClient()->expire($key, $expire);
        }
        return $result;
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key of the value to be deleted
     * @return boolean if no error happens during deletion
     */
    protected function deleteValue($key)
    {
        return $this->getConnection()->getClient()->delete($key);
    }

    /**
     * Deletes all values from cache.
     * Child classes may implement this method to realize the flush operation.
     * @return boolean whether the flush operation was successful.
     */
    protected function flushValues()
    {
        return (bool)$this->getConnection()->getClient()->flushDb();
    }
}