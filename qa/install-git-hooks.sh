#!/usr/bin/env bash
set -e

HOOK_DIR=".git/hooks"
HOOK="$HOOK_DIR/pre-commit"

echo "🔧 Installing git pre-commit hook"

mkdir -p "$HOOK_DIR"

cat > "$HOOK" <<'EOF'
#!/usr/bin/env bash
set -e

echo "🚦 Running pre-commit checks..."
qa/pre-commit-version.sh
qa/pre-commit-vendor-check.sh
EOF

chmod +x "$HOOK"

echo "✅ pre-commit hook installed"
echo "   Runs qa/pre-commit-version.sh and qa/pre-commit-vendor-check.sh before each commit."
echo "   Re-run this script after every fresh git clone or checkout without hooks."

