#!/bin/sh
set -euf

./tests/run.sh 5.4
./tests/run.sh 5.6
./tests/run.sh 7
./tests/run.sh 7.1
./tests/run.sh 7.2
./tests/run.sh 7.3
