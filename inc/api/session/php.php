<?php

/* Licensed under the Apache License, Version 2.0
 * See the LICENSE and NOTICE file for further information
 */

/**
 * base session driver that uses the default php
 * session storage through $_SESSION
 */
class api_session_php implements api_session_Idriver {
    /**
     * current request data
     *
     * @var array
     */
    protected $request;

    /**
     * data that is going to be saved at the end of the request
     *
     * @var array
     */
    protected $store;

    /**
     * session namespace of this instance
     *
     * @var string
     */
    protected $namespace;

    /**
     * session id
     *
     * @var string
     */
    protected $sessId;

    /**
     * @param string $namespace where in the $_SESSION var the data will be saved
     */
    public function __construct($namespace = 'okapi') {
        if (!session_id()) {
            session_start();
        }

        $this->sessId = session_id();
        $this->namespace = $namespace;

        $this->init();
    }

    protected function init() {
        $this->request = $this->store = $this->getCurrentSession();
        if (!is_array($this->request)) {
            $this->request = $this->store = array('flash' => array(), 'data' => array());
        }

        // clear old flash messages so they don't propagate to the next request
        // they remain readable in $this->request though
        $this->store['flash'] = array();
        if (!isset($this->request['flash'])) {
            $this->request['flash'] = array();
        }
    }

    protected function getCurrentSession() {
        return isset($_SESSION[$this->namespace]) ? $_SESSION[$this->namespace] : false;
    }

    /**
     * reads a value from the session
     *
     * @param string $key
     * @param int $mode api_session::FLASH to read from the flash vars,
     *                  anything else reads from standard storage
     * @return mixed value or null if not present
     */
    public function read($key = null, $mode = 0) {
        $target = $mode & api_session::FLASH ? 'flash' : 'data';
        if ($key === null) {
            return $this->request[$target];
        }
        return isset($this->request[$target][$key]) ? $this->request[$target][$key] : null;
    }

    /**
     * reads a value from the session
     *
     * @param string $key
     * @param mixed $value
     * @param int $mode bitmask made of api_session constants to define where to write
     * @return bool success
     */
    public function write($key, $value, $mode = 0) {
        $target = $mode & api_session::FLASH ? 'flash' : 'data';
        if ($mode & api_session::STORE) {
            $this->store[$target][$key] = $value;
        }
        if ($mode & api_session::REQUEST) {
            $this->request[$target][$key] = $value;
        }
        return true;
    }

    /**
     * deletes a value from the session
     *
     * @param string $key
     * @param int $mode bitm
     * @param int $mode bitmask made of api_session constants to define where to delete
     * @return bool success
     */
    public function delete($key, $mode = 0) {
        $target = $mode & api_session::FLASH ? 'flash' : 'data';
        if ($mode & api_session::STORE) {
            unset($this->store[$target][$key]);
        }
        if ($mode & api_session::REQUEST) {
            unset($this->request[$target][$key]);
        }
        return true;
    }

    /**
     * saves the changes from this request into the real session storage
     *
     * @return bool success
     */
    public function commit() {
        $_SESSION[$this->namespace] = $this->store;
        return true;
    }

    /**
     * regenerates the session id
     *
     * @param bool $deleteOld if true, deletes the old session
     * @return bool success
     */
    public function regenerateId($deleteOld = false) {
        $res = session_regenerate_id($deleteOld);
        $this->sessId = session_id();
        return $res;
    }

    /**
     * returns the session id
     *
     * @return string
     */
    protected function getSessId() {
        return $this->sessId;
    }
}