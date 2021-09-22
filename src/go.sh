#!/bin/bash

set -x;

rm -rf ./source/

mkdir ./source/ && cd ./source && wget https://github.com/jaywcjlove/linux-command/archive/refs/heads/master.zip && unzip master.zip

rm -rf ./html/*.md.html

composer install

cd ../ && php ./run.php
