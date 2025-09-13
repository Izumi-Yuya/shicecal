#!/bin/bash

# Interactive AWS Deployment Setup Script

echo "🔧 AWS Deployment Setup for Shise-Cal"
echo "====================================="
echo ""

# Check if configuration already exists
if [ -f "aws-server-config.sh" ]; then
    echo "📋 Existing configuration found:"
    source aws-server-config.sh
    echo ""
    read -p "Do you want to update the configuration? (y/N): " update_config
    if [[ ! $update_config =~ ^[Yy]$ ]]; then
        exit 0
    fi
fi

echo "Please provide your AWS server details:"
echo ""

# Get server IP
read -p "🌐 Enter your EC2 server IP address: " server_ip
while [[ ! $server_ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; do
    echo "❌ Invalid IP address format"
    read -p "🌐 Enter your EC2 server IP address: " server_ip
done

# Get SSH key path
read -p "🔑 Enter path to your SSH key file: " key_path
key_path="${key_path/#\~/$HOME}"  # Expand ~ to home directory
while [ ! -f "$key_path" ]; do
    echo "❌ SSH key file not found: $key_path"
    read -p "🔑 Enter path to your SSH key file: " key_path
    key_path="${key_path/#\~/$HOME}"
done

# Get server username (default: ec2-user)
read -p "👤 Enter server username (default: ec2-user): " server_user
server_user="${server_user:-ec2-user}"

# Get project path (default: /home/ec2-user/shicecal)
read -p "📁 Enter project path on server (default: /home/$server_user/shicecal): " project_path
project_path="${project_path:-/home/$server_user/shicecal}"

# Get branch (default: production)
read -p "🌿 Enter Git branch to deploy (default: production): " branch
branch="${branch:-production}"

echo ""
echo "📋 Configuration Summary:"
echo "  Server: $server_user@$server_ip"
echo "  SSH Key: $key_path"
echo "  Project Path: $project_path"
echo "  Branch: $branch"
echo ""

read -p "Is this configuration correct? (y/N): " confirm
if [[ ! $confirm =~ ^[Yy]$ ]]; then
    echo "❌ Configuration cancelled"
    exit 1
fi

# Create configuration file
cat > aws-server-config.sh << EOF
#!/bin/bash

# AWS Server Configuration
# Generated on $(date)

export SERVER_HOST="$server_ip"
export SERVER_USER="$server_user"
export KEY_PATH="$key_path"
export PROJECT_PATH="$project_path"
export BRANCH="$branch"

echo "AWS Server Configuration loaded:"
echo "  Server: \${SERVER_USER}@\${SERVER_HOST}"
echo "  Key: \${KEY_PATH}"
echo "  Path: \${PROJECT_PATH}"
echo "  Branch: \${BRANCH}"
EOF

chmod +x aws-server-config.sh

echo "✅ Configuration saved to aws-server-config.sh"
echo ""

# Test SSH connection
echo "🧪 Testing SSH connection..."
if ssh -i "$key_path" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$server_user@$server_ip" "echo 'Connection test successful'" 2>/dev/null; then
    echo "✅ SSH connection successful"
    echo ""
    echo "🚀 You can now deploy using:"
    echo "  ./scripts/aws-deploy.sh full    # Full deployment"
    echo "  ./scripts/aws-deploy.sh quick   # Quick deployment"
    echo "  ./scripts/aws-deploy.sh health  # Health check only"
else
    echo "❌ SSH connection failed"
    echo "Please check:"
    echo "  - Server IP address is correct"
    echo "  - SSH key file has correct permissions (chmod 600 $key_path)"
    echo "  - Security group allows SSH access (port 22)"
    echo "  - Server is running and accessible"
fi