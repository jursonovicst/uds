<?php
header ( "Content-Type: application/json ");
$query = new dnsquery();
try {
    $query->from_wire(file_get_contents("php://input"));
    #echo $query->getString();
    $answer = new dnsanswer();
    $answer->add_answer($query->getName(), $query->getType(), $query->getClass(), 5, rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255));
    $answer->add_answer($query->getName(), $query->getType(), $query->getClass(), 5, rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255));
    
    # simulate api access
    usleep(10*1000);
    
    echo $answer->to_wire();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
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
?>
