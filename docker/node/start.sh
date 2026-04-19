#!/bin/sh
set -eu

cd /srv/frontend

exec npm run dev -- --host 0.0.0.0
