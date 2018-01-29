#!/usr/bin/python

from flup.server.fcgi import WSGIServer
import sys
import json
from random import randint
from time import sleep

def myapp(environ, start_response):
    start_response('200 OK', [('Content-Type', 'text/plain')])
    return ['Hello World!\n']


class dnsmessage(object):
    _data = {}

    def __init__(self):
        self._data['messagetype'] = self.__class__.__name__

    def to_wire(self):
        return json.JSONEncoder.encode(self._data)

    def from_wire(self, serialdata):
        self._data = json.JSONDecoder().decode(serialdata)

        if not 'messagetype' in self._data or self._data['messagetype'] != self.__class__.__name__:
            raise Exception("Unexpected message type '%s' for %s" % (self._data['messagetype'], self.__class__.__name__))

class dnsquery(dnsmessage):

    def from_wire(self, serialdata):
        super(dnsquery, self).from_wire(serialdata)

        # mandatory attributes
        if not 'query' in self._data:
            raise Exception("query is missing from %s " % self.__class__.__name__)

        if not 'name' in self._data['query']:
            raise Exception("name value is missing from %s" % self.__class__.__name__)
#        if (!filter_var($this->data['query']['name'], FILTER_VALIDATE_REGEXP, array(
#            "options" => array(
#                "regexp" => "/^(?:[a-zA-Z][a-zA-Z0-9-]*\.)+[a-zA-Z][a-zA-Z0-9-]*$/"
#            )
#        )))
#            raise ValueError ("Domain name '" . $this->data['query']['name'] . "' is invalid!");

        if not 'type' in self._data['query']:
            raise Exception("type value is missing from %s" % self.__class__.__name__)
#        if ($this->data['query']['type'] != "A" && $this->data['query']['type'] != "AAAA")
#            raise ValueError("type '" . $this->data['query']['type'] . "' is not supported.");

        if not 'class' in self._data['query']:
            raise Exception("class value is missing from %s" % self.__class__.__name__)
#        if ($this->data['query']['class'] != "IN")
#            raise ValueError("class '" . $this->data['query']['class'] . "'is not supported.");

        # optional attributes
        #if not 'clientinfo' in self._data: # and  isset($this->data['clientinfo']['ip']) && !filter_var($this->data['clientinfo']['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            #myLog("IP '" . $this->data['clientinfo']['ip'] . "' is not a valid IP (v4 or v6) address, continue without IP.");
            #unset($this->data['clientinfo']['ip']);
            #unset($this->data['clientinfo']);

    def getString(self):
        return self._data['query']['name'] + " " + self._data['query']['class'] + " " + self._data['query']['type']

    def getName(self):
        return self._data['query']['name']

    def getType(self):
        return self._data['query']['type']

    def getClass(self):
        return self._data['query']['class']




class dnsanswer(dnsmessage):

    def __init__(self):
        super(dnsanswer,self).__init__()
        self._data['answers'] = []

    def add_answer(self, name, type, dnsclass, ttl, address):
        answer = {}
        answer['name'] = name
        answer['type'] = type
        answer['class'] = dnsclass
        answer['ttl'] = ttl
        answer['address'] = address
        self._data['answers'].append(answer)

    def getString(self):
        buff = ""
        for answer in self._data['answers']:
            buff += answer['name'] + " " + answer['ttl'] + " " + answer['class'] + " " + answer['type'] + " " + answer['address'] + "\n"
        return buff.rstrip()


if __name__ == '__main__':

    query = dnsquery()
#    query.from_wire(sys.stdin.read())
    query.from_wire("{\"messagetype\": \"dnsquery\",\"query\": {\"name\": \"service.example.com\",\"type\": \"A\",\"class\": \"IN\"},\"clientinfo\": {\"ip\": \"1.2.3.4\"}}")
    answer = dnsanswer()

    answer.add_answer(query.getName(), query.getType(), query.getClass(), 5, "%d.%d.%d.%d" % (randint(1, 255),randint(1, 255),randint(1, 255),randint(1, 255)))
    answer.add_answer(query.getName(), query.getType(), query.getClass(), 5, "%d.%d.%d.%d" % (randint(1, 255),randint(1, 255),randint(1, 255),randint(1, 255)))

    # simulate api access
    sleep(0.01)

    print(answer.to_wire())

    #from flup.server.fcgi import WSGIServer
    #WSGIServer(myapp,bindAddress="/var/run/flup/flup.sock").run()
