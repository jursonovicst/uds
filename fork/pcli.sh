#!/bin/bash

if [ $# -ne 1 ]; then
  echo "Usage: $0 <number of paralell cli>"
  exit 0;
fi

re='^[0-9]+$'
if ! [[ $1 =~ $re ]]; then
  echo "$1 is not a number!"
  exit 0;
fi

seq 1 $1 |xargs -n 1 -P $1 -I {} ./cli cli{}.example.com
