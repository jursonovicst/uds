#!/usr/bin/php
<?php
// -d xdebug.profiler_enable=1


define ( "INIFILE", "cli.ini");


/**
 * Timestamps and redirects logs to a logfile (if 'LOGFILE' defined), or to PHP's system logger.
 *
 * @param string $message
 *        	Message to be logged.
 */
function myLog(string $message) {
	if (defined ( 'LOGFILE' ))
		error_log ( "[" . date ( 'r' ) . "] " . trim ( $message ) . "\n", 3, LOGFILE );
	else
		error_log ( trim ( $message ) );
}




$__running = true;

/*#TODO: do I need that?
declare(ticks=1);*/


/**
 * Callback for catching signals
 *
 * @param $signo
 */
function sig_handler($signo)
{
    global $__running;

    switch ($signo) {
        case SIGTERM :
            $__running = FALSE;
            break;
        case SIGINT :
            $__running = FALSE;
            break;
        default :
            MyLog("Unknown signal (" . $signo . ") received");
    }
}


/**
 * Worker function for child processes. After forking, the children remain in this function and answers DNS queries. They can leave this function with an exit().
 *
 * @param string $server_socket_name The name of the unix domain socket, on which this child should listen for connections from DLZ driver.
 */
function listen_and_answer(string $server_socket_name)
{
    global $__running;

    $socket_pool = [];
    try {
        myLog("Worker on '$server_socket_name' started.");

        # listen for connections
        if (($server_socket = stream_socket_server("unix://" . $server_socket_name, $errno, $errstr)) === FALSE)
            throw new Exception ("Unable to create socket at " . $server_socket_name . ": $errstr ($errno)", $errno);

        $socket_pool[] = $server_socket;
        while ($__running) {

            # wait for IO to happen (either new connection or read
            $read_pool = $socket_pool;
            $_w = $_e = NULL;
            if (($mod_fd = stream_select($read_pool, $_w, $_e, 0, 200000)) === FALSE)
                #error or interrupt TODO: howto handle error messages and filter interrupts?
                throw new Exception("Interrupt", 0);

            # timeout
            if ($mod_fd == 0)
                continue;

            foreach ($read_pool as $socket) {

                if ($socket === $server_socket) {
                    # accept new connection

                    if (($conn_socket = stream_socket_accept($server_socket)) === FALSE) {
                        myLog("Error accepting connection, continue listening...");
                        continue;
                    }
                    if (!stream_set_blocking($conn_socket, FALSE)) {
                        myLog("Error setting non-blocking on client socket, closing connection.");
                        fclose($conn_socket);
                        continue;
                    }
                    $socket_pool[] = $conn_socket;
                    myLog("Connection accepted.");

                } else {
                    # do IO

                    # message handling on one connection must be synchronous, if a message sent, an answer must be arrived before sending a new message.
                    $sock_data = "";
                    while (($buff = fread($socket, 200)) !== FALSE && strlen($buff) != 0)
                        $sock_data .= $buff;

                    if ($buff === FALSE) {
                        # IO error

                        $key_to_del = array_search($socket, $socket_pool, TRUE);
                        fclose($socket);
                        unset($socket_pool[$key_to_del]);
                        myLog("Connection closed due to read error.");

                        #no reason to reply with on a closed socket...
                        continue;
                    }

                    if (strlen($sock_data) == 0) {
                        # connection closed

                        $key_to_del = array_search($socket, $socket_pool, TRUE);
                        fclose($socket);
                        unset($socket_pool[$key_to_del]);
                        myLog("Connection closed.");
                        continue;
                    }

                    # message received
                    $query = new dnsquery();
                    try {
                        $query->from_wire($sock_data);
                        myLog(";; QUESTION SECTION:\n" . $query->getString());

                        $answer = new dnsanswer();
                        $answer->add_answer($query->getName(), $query->getType(), $query->getClass(), 5, rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255));
                        $answer->add_answer($query->getName(), $query->getType(), $query->getClass(), 5, rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255));

                        myLog(";; ANSWER SECTION:\n" . $answer->getString());

                        if (($n = fwrite($socket, $data = $answer->to_wire())) === FALSE || $n != strlen($data)) {
                            # write error, connection close

                            $key_to_del = array_search($socket, $socket_pool, TRUE);
                            fclose($socket);
                            unset($socket_pool[$key_to_del]);
                            myLog("Connection closed due to write error.");
                        }
                    } catch (Exception $e) {
                        myLog($e->getMessage());
                    }
                }
            }
        }

        # close existing connections
        foreach ($socket_pool as $socket)
            fclose($socket);
        unlink($server_socket_name);

    } catch (Exception $e) {
        # close existing connections
        foreach ($socket_pool as $socket)
            fclose($socket);
        unlink($server_socket_name);

        myLog("Worker '$server_socket_name' exiting: " . $e->getMessage());
        exit($e->getCode());
    }
    exit(0);
}


try {
    global $__running;

    # set up signal catchers
    if (!pcntl_signal(SIGTERM, "sig_handler"))
        throw new Exception ("Cannot register SIGTERM signal: '" . pcntl_strerror(pcntl_get_last_error()) . "'.", 1);
    if (!pcntl_signal(SIGINT, "sig_handler"))
        throw new Exception ("Cannot register SIGINT signal: '" . pcntl_strerror(pcntl_get_last_error()) . "'.", 1);

    # parse config
    if (!($config = parse_ini_file(INIFILE, true)))
        throw new exception ("Cannot load ini file: " . INIFILE, 1);

    # acquire lock
    if (!$fp = fopen($config ['dnsresponder'] ['lockfile'], "w"))
        throw new Exception ("Cannot open my lock file '" . $config ['dnsresponder'] ['lockfile'] . "'", 1);
    if (!flock($fp, LOCK_EX | LOCK_NB))
        throw new Exception ("Process is already running.", 0);
    if (!fwrite($fp, getmypid()))
        myLog("Cannot write my PID into the lock file, continue...");

    $children = array();
    foreach ($config['dnsresponder']['socket'] as $socket_name) {
        if (($pid = pcntl_fork()) == -1)
            throw new Exception("Cannot fork children.", 1);

        if ($pid == 0) {
            # I am child x, then exit, my parent process will respawn me!

            exit(254);
        } else {
            # I am the parent, register child for respawn

            $children[$socket_name] = $pid;
        }
    }


    # I must be a parent, otherwise I have already exited with exit(0). I will monitor all children, and respawn them, if they exited due to some error.
    while (!empty($children) && $__running) {
        # wait a bit to avoid high polling frequency
        sleep(1);

        foreach ($children as $socket_name => $pid) {
            $status = NULL;
            $res = pcntl_waitpid($pid, $status, WNOHANG);

            if ($res > 0) {
                # a child has exited

                unset($children[$socket_name]);

                # check if the child exited due to an error --> respawn
                if (pcntl_wexitstatus($status) > 0) {

                    if (($newpid = pcntl_fork()) == -1) {
                        myLog("Cannot respawn child $pid, let's try it again.");
                        continue;
                    }

                    if ($newpid == 0) {
                        # I am the child, let's do some work.

                        listen_and_answer($socket_name);
                        # This function above will never return, instead the child will exit, so watch for respawn.
                    } else {
                        # I am the parent, register child for respawn

                        $children[$socket_name] = $newpid;
                    }
                }

            } elseif ($res == -1) {
                # internal error

                myLog("Error watching children: " . pcntl_strerror(pcntl_get_last_error()) . ", continue watching...");
            }
        }
    }

    myLog("I am terminating normally, systemd should not restart me!");
    # kill children
    foreach ($children as $pid)
        posix_kill($pid, SIGTERM);

    # close lockfile
    ftruncate($fp, 0);
    flock($fp, LOCK_UN);
    fclose($fp);

} catch (Exception $e) {
    myLog("Error: " . $e->getMessage() . ", I am exiting, but systemd should restart me!");

    ftruncate($fp, 0);
    flock($fp, LOCK_UN);
    fclose($fp);

    exit ($e->getCode());
}







/**
 * Class dnsmessage is a generalized class for messages.
 */
class dnsmessage
{
    protected $data = array();      #this can hold anything...

    public function __construct()
    {
        $this->data['messagetype'] = get_class($this);
    }

    /**
     * Creates the json representation of the internal data structure. Overload this function to handle message specific data formatting.
     *
     * @return string Returns the wire format (JSON) of the message, or FALSE on failure.
     */
    public function to_wire()
    {
        return json_encode($this->data);
    }

    /**
     * Parses the json representation of the message and builds the internal data structure. Overload this function to handle message specific data parsing.
     *
     * @param string $serialdata Wire format (JSON) of the message .
     * @throws Exception
     */
    public function from_wire($serialdata)
    {
        # decode json
        if (($this->data = json_decode($serialdata, true)) === NULL)
            throw new Exception("Unable to decode data '" . $serialdata . "' as " . get_class($this) . "!");

        # check message type
        if (!isset($this->data['messagetype']) || $this->data['messagetype'] != get_class($this))
            throw new UnexpectedValueException("Unexpected message type '" . isset($this->data['messagetype']) ? $this->data['messagetype'] : "" . "' for " . get_class($this));
    }
}

/**
 * Class dnsquery Inherited class for Query messages.
 */
class dnsquery extends dnsmessage
{

    /**
     * @param string $serialdata
     * @throws Exception
     */
    public function from_wire($serialdata)
    {
        dnsmessage::from_wire($serialdata);

        # mandatory attributes
        if (!isset($this->data['query']))
            throw new Exception("query is missing from " . get_class($this));

        if (!isset($this->data['query']['name']))
            throw new Exception("name value is missing from " . get_class($this));
        if (!filter_var($this->data['query']['name'], FILTER_VALIDATE_REGEXP, array(
            "options" => array(
                "regexp" => "/^(?:[a-zA-Z][a-zA-Z0-9-]*\.)+[a-zA-Z][a-zA-Z0-9-]*$/"
            )
        )))
            throw new UnexpectedValueException ("Domain name '" . $this->data['query']['name'] . "' is invalid!");

        if (!isset($this->data['query']['type']))
            throw new Exception("type value is missing from " . get_class($this));
        if ($this->data['query']['type'] != "A" && $this->data['query']['type'] != "AAAA")
            throw new UnexpectedValueException("type '" . $this->data['query']['type'] . "' is not supported.");

        if (!isset($this->data['query']['class']))
            throw new Exception("class value is missing from " . get_class($this));
        if ($this->data['query']['class'] != "IN")
            throw new UnexpectedValueException("class '" . $this->data['query']['class'] . "'is not supported.");

        # optional attributes
        if (isset($this->data['clientinfo']) && isset($this->data['clientinfo']['ip']) && !filter_var($this->data['clientinfo']['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            myLog("IP '" . $this->data['clientinfo']['ip'] . "' is not a valid IP (v4 or v6) address, continue without IP.");
            unset($this->data['clientinfo']['ip']);
            unset($this->data['clientinfo']);
        }
    }

    public function getString()
    {
        return $this->data['query']['name'] . " " . $this->data['query']['class'] . " " . $this->data['query']['type'];
    }

    public function getName()
    {
        return $this->data['query']['name'];
    }

    public function getType()
    {
        return $this->data['query']['type'];
    }

    public function getClass()
    {
        return $this->data['query']['class'];
    }

}

/**
 * Class dnsanswer Inherited class for Answer messages.
 */
class dnsanswer extends dnsmessage
{
    public function __construct()
    {
        dnsmessage::__construct();
        $this->data['answers'] = array();
    }

    public function add_answer(string $name, string $type, string $class, int $ttl, string $address)
    {
        $answer = array();
        $answer['name'] = $name;
        $answer['type'] = $type;
        $answer['class'] = $class;
        $answer['ttl'] = $ttl;
        $answer['address'] = $address;
        $this->data['answers'][] = $answer;
    }

    public function getString()
    {
        $buff = "";
        foreach ($this->data['answers'] as $answer)
            $buff .= $answer['name'] . " " . $answer['ttl'] . " " . $answer['class'] . " " . $answer['type'] . " " . $answer['address'] . "\n";
        return rtrim($buff);
    }

}
