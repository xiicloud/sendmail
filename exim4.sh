#!/bin/sh
set -e
exec /usr/sbin/exim4 -v -bdf -q30m
