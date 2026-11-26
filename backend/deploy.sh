#!/bin/bash
# Delegate to the unified root deployment script
cd "$(dirname "$0")/.."
exec ./deploy.sh "$@"
