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
 * A simple redis based session handler
 *
 * @author Charles Pick
 * @link https://github.com/phpnode/YiiRedis/blob/master/ARedisSession.php
 *
 * @author Alex Zet <zetdev@gmail.com>
 * @package yii2ext\redis
 * @since 1.0
 */
class Session extends \yii\web\Session
{
    /**
     * The prefix to use when storing and retrieving sessions
     * @var string
     */
    public $keyPrefix = "yii2ext.redisSession.";

    /**
     * The suffix to use when storing and retrieving sessions
     * @var string
     */
    public $keySuffix = '';

    /**
     * Holds the redis connection
     * @var Connection|string
     */
    protected $_connection='redis';

    /**
     * Initializes the application component.
     * This method overrides the parent implementation by checking if redis is available.
     */
    public function init()
    {
        $this->getConnection();
        parent::init();
    }

    /**
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     * @return boolean whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return true;
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
        $data = $this->_connection->getClient()->get($this->calculateKey($id));
        return $data === false ? '' : $data;
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return boolean whether session write is successful
     */
    public function writeSession($id, $data)
    {
        $key = $this->calculateKey($id);
        $this->_connection->getClient()->set($key, $data);
        $this->_connection->getClient()->expire($key, $this->getTimeout());
        return true;
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        $this->_connection->getClient()->delete($this->calculateKey($id));
        return true;
    }

    /**
     * Generates a unique key used for storing session data in cache.
     * @param string $id session variable name
     * @return string a safe cache key associated with the session variable name
     */
    protected function calculateKey($id)
    {
        return $this->keyPrefix.$id.$this->keySuffix;
    }

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


}