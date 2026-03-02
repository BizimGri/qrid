#!/bin/bash
# security-cleanup.sh
# Script to clean sensitive data from git history

# Function to check if a supplied command was successful
check_command() {
    if [ $? -ne 0 ]; then
        echo "Error: $1 failed."
        exit 1
    fi
}

# Define directories and files to clean sensitive data from
REPO_DIR="$(pwd)"

# Log start of the script execution
echo "Starting security cleanup on $(date)"

# Example: Clean password files
# Find all files that contain sensitive data (example: .env files)
find "$REPO_DIR" -name ".env" -exec rm -f {} \;
check_command "Removing .env files"

echo "Removed sensitive .env files"

# Remove specific sensitive terms from git history
# You can add more patterns as needed
SENSITIVE_TERMS=("password" "secret" "api_key")
for term in "${SENSITIVE_TERMS[@]}"; do
    git filter-repo --invert-paths --path-glob "*${term}*" --force
    check_command "Filtering out sensitive term: $term"
    echo "Filtered out: $term"
done

# Complete the cleanup
echo "Security cleanup completed on $(date)"