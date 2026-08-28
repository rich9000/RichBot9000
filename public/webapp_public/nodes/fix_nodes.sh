#!/bin/bash

# Function to process a node file
fix_node_file() {
    local file=$1
    local filename=$(basename "$file")
    local classname="${filename%.*}"
    
    # Remove import statements and export default
    sed -i '/^import.*from/d' "$file"
    sed -i '/^export default/d' "$file"
    
    # Add any missing class properties
    if ! grep -q "this.icon" "$file"; then
        sed -i "/super(data);/a\        this.icon = 'fa-circle';" "$file"
    fi
    if ! grep -q "this.name" "$file"; then
        sed -i "/super(data);/a\        this.name = '$classname';" "$file"
    fi
    if ! grep -q "this.description" "$file"; then
        sed -i "/super(data);/a\        this.description = '';" "$file"
    fi
}

# Process all node files
for file in *.js; do
    if [[ "$file" != "NodeFactory.js" && "$file" != "NodeLoader.js" && "$file" != "fix_nodes.sh" ]]; then
        echo "Processing $file..."
        fix_node_file "$file"
    fi
done

echo "All node files have been processed." 