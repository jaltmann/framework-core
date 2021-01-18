#!/bin/bash
cp docker/framework-staging.docker share/build/
docker build -f share/build/framework-staging.docker -t framework-staging share/build/
rm share/build/framework-staging.docker
