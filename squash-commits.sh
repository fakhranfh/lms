#!/bin/bash

# Squash Commits Script
# Usage: ./squash-commits.sh <number-of-commits> [commit-message]

set -e

if [ $# -lt 1 ]; then
    echo "Usage: $0 <number-of-commits> [commit-message]"
    echo ""
    echo "Examples:"
    echo "  $0 3                    # Squash last 3 commits (interactive)"
    echo "  $0 2 'My new commit'    # Squash last 2 commits with message"
    exit 1
fi

COMMITS_COUNT=$1
COMMIT_MESSAGE=${2:-""}

# Validate input
if ! [[ "$COMMITS_COUNT" =~ ^[0-9]+$ ]] || [ "$COMMITS_COUNT" -lt 1 ]; then
    echo "Error: Number of commits must be a positive integer"
    exit 1
fi

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "Error: Not in a git repository"
    exit 1
fi

echo "Squashing last $COMMITS_COUNT commit(s)..."

# Get the current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "Current branch: $CURRENT_BRANCH"

# Soft reset to squash commits
git reset --soft HEAD~"$COMMITS_COUNT"

echo ""
echo "Commits squashed! Staged changes ready for commit."
echo ""
git status

if [ -n "$COMMIT_MESSAGE" ]; then
    echo ""
    echo "Creating commit with message: $COMMIT_MESSAGE"
    git commit -m "$COMMIT_MESSAGE"
    echo "✓ Commit created successfully!"
else
    echo ""
    echo "Run: git commit -m 'Your commit message'"
fi
