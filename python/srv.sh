#!/bin/bash

UDSOCKET="/tmp/py.sock"
PYSCRIPT="$(pwd)/test.py"
PROCCOUNT=50

if [ -S "${UDSOCKET}" ]; then
  # remove potentially existing domain socket
  rm "${UDSOCKET}"
fi

spawn-fcgi -s "${UDSOCKET}" -- /usr/bin/multiwatch -f $PROCCOUNT -- /usr/bin/python "${PYSCRIPT}"
