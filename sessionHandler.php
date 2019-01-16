<?php

use Carbon\Carbon;

class DatastoreSessionHandler implements SessionHandlerInterface
{
    /**
     * $expire
     *
     * @var mixed
     *
     * @access private
     */
    private $expire;

    /**
     * $lastaccess
     *
     * @var mixed
     *
     * @access private
     */
    private $lastaccess;

    /**
     * $deleteTime
     *
     * @var mixed
     *
     * @access private
     */
    private $deleteTime;

    /**
     * $obj_schema
     *
     * @var mixed
     *
     * @access private
     */
    private $obj_schema;

    /**
     * $obj_store
     *
     * @var mixed
     *
     * @access private
     */
    private $obj_store;

    /**
     * $orig_data
     *
     * @var mixed
     *
     * @access private
     */
    private $orig_data;

    /**
     * $orig_id
     *
     * @var mixed
     *
     * @access private
     */
    private $orig_id;

    /**
     * __construct
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct()
    {
        // Get session max lifetime to leverage Memcache expire functionality.
        $this->expire = ini_get("session.gc_maxlifetime");
        $this->lastaccess = $this->getTimeStamp();
        $this->deleteTime = Carbon::now()->subSeconds($this->expire)->toDateTimeString();

        $obj_gateway = getHandler();

        $this->obj_schema = (new GDS\Schema('sessions'))
            ->addString('data', false)
            ->addDateTime('lastaccess');

        $this->obj_store = new GDS\Store($this->obj_schema, $obj_gateway);
    }

    /**
     * open - Re-initializes existing session, or creates a new one.
     *
     * @param string $savePath    Save path
     * @param string $sessionName Session name
     *
     * @access public
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * close - Closes the current session.
     *
     * @access public
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * read - Reads the session data.
     *
     * @param string $id Session ID.
     *
     * @access public
     *
     * @return string
     */
    public function read($id)
    {
        $start = microtime(true);
        $obj_sess = $this->obj_store->fetchByName($id);
        $end = microtime(true);

        error_log('session read: ' . (($end - $start)*1000) . 'ms');

        if($obj_sess instanceof GDS\Entity) {
            $this->orig_id = $id;
            $this->orig_data = $obj_sess->data;

            return $obj_sess->data;
        }

        return "";
    }

    /**
     * write - Writes the session data to the storage
     *
     * @param string $id   Session ID
     * @param string $data Serialized session data to save
     *
     * @access public
     *
     * @return string
     */
    public function write($id, $data)
    {
        $obj_sess = $this->obj_store->createEntity([
            'data'          => $data,
            'lastaccess'    => $this->lastaccess
        ])->setKeyName($id);

        if ( ($this->orig_id != $id) || ($this->orig_data != $data) ){
            $start = microtime(true);
            $this->obj_store->upsert($obj_sess);
            $end = microtime(true);

            error_log('session write: ' . (($end - $start)*1000) . 'ms');
        }

        return true;
    }

    /**
     * destroy - Destroys a session.
     *
     * @param tring $id Session ID
     *
     * @access public
     *
     * @return bool
     */
    public function destroy($id)
    {
        $obj_sess = $this->obj_store->fetchByName($id);

        if($obj_sess instanceof GDS\Entity) {
            $this->obj_store->delete($obj_sess);
        }

        return true;
    }

    /**
     * gc - Cleans up expired sessions (garbage collection).
     *
     * @param string|int $maxlifetime Sessions that have not updated for the last maxlifetime seconds will be removed
     *
     * @access public
     *
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }


    private function getTimeStamp()
    {
        $timeStamp = Carbon::now()->toDateTimeString();
        return $timeStamp;
    }
}