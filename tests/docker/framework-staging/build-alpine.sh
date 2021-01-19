#!/bin/bash
cp docker/framework-staging-alpine.docker share/build/
docker build -f share/build/framework-staging-alpine.docker -t framework-staging-alpine share/build/
rm share/build/framework-staging-alpine.docker
