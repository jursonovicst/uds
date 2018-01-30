#!/usr/bin/python

from flup.server.fcgi import WSGIServer
import sys
import re
import json
import ipaddress
from random import randint
from time import sleep
from flup.server.fcgi import WSGIServer


class dnsmessage(object):
    _data = {}      #this can hold anything...

    def __init__(self):
        self._data['messagetype'] = self.__class__.__name__

    def to_wire(self):
        return json.JSONEncoder().encode(self._data)

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
        if not re.match("^(?:[a-zA-Z][a-zA-Z0-9-]*\.)+[a-zA-Z][a-zA-Z0-9-]*$", self._data['query']['name']):
            raise ValueError("Domain name '%s' is invalid!" % self._data['query']['name'])

        if not 'type' in self._data['query']:
            raise Exception("type value is missing from %s" % self.__class__.__name__)
        if self._data['query']['type'] != "A" and self._data['query']['type'] != "AAAA":
            raise ValueError("type '%s' is not supported." % self._data['query']['type'])

        if not 'class' in self._data['query']:
            raise Exception("class value is missing from %s" % self.__class__.__name__)
        if self._data['query']['class'] != "IN":
            raise ValueError("class '%s'is not supported." % self._data['query']['class'])

        # optional attributes
        if 'clientinfo' in self._data and 'ip' in self._data['clientinfo']:
            try:
                ipaddress.ip_address(self._data['clientinfo']['ip'])
            except ValueError:
                print("IP '%s' is not a valid IP (v4 or v6) address, continue without IP." % self._data['clientinfo']['ip'])
                del self._data['clientinfo']['ip']

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


def app(environ, start_response):
    response_body = ""
    try:
        try:
            request_body_size = int(environ.get('CONTENT_LENGTH', 0))
        except (ValueError):
            request_body_size = 0
        
        query = dnsquery()
        query.from_wire(environ['wsgi.input'].read(request_body_size))
        answer = dnsanswer()

        answer.add_answer(query.getName(), query.getType(), query.getClass(), 5, "%d.%d.%d.%d" % (randint(1, 255), randint(1, 255), randint(1, 255), randint(1, 255)))
        answer.add_answer(query.getName(), query.getType(), query.getClass(), 5, "%d.%d.%d.%d" % (randint(1, 255), randint(1, 255), randint(1, 255), randint(1, 255)))

        # simulate api access
        sleep(0.01)

        response_body = answer.to_wire()

    except Exception as e:
        start_response('500 ERROR', [('Content-Type', 'text/plain')])
        return [e.message]

    start_response('200 OK', [('Content-Type', 'application/json')])
    return [response_body]


if __name__ == '__main__':

    #app()
    print("STarting server...")
    WSGIServer(app,bindAddress="/var/run/flup/flup.sock").run()
