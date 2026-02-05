# Remove Vite References and Add Quality Gates

**Date:** 2026-02-05
**Status:** Approved

## Overview

Remove all Vite-related files and references from the project, as the build system has been migrated to `@wordpress/scripts` (Webpack-based). Additionally, add quality gates documentation to CLAUDE.md to enforce build, lint, and test requirements.

## Current State

- Project uses `@wordpress/scripts` for builds (Webpack-based)
- Vite dependencies already removed from `package.json`
- `vite.config.js` exists but is unused
- Documentation incorrectly references Vite

## Changes

### 1. File Deletion
- Delete `vite.config.js` (42 lines, completely unused)

### 2. Documentation Updates

**README.md (line 88-89):**
- Before: "This will run Vite in development mode..."
- After: "This will run `@wordpress/scripts` in development mode with hot module replacement enabled..."

**CLAUDE.md (line 32-35):**
- Before: "This runs Vite in development mode..."
- After: "This runs `@wordpress/scripts` (Webpack with hot module replacement)..."

**CLAUDE.md (new section after "Coding Standards"):**
- Add comprehensive Quality Gates section
- Requires build, lint, and test verification before claiming completion
- Enforces "evidence before assertions" principle

### 3. Configuration Updates

**`.distignore` (line 26):**
- Remove `vite.config.js` entry

**`.github/workflows/release.yml` (line 24):**
- Remove `vite.config.js` from `CLEAN_TARGETS` list

## Impact

- Zero runtime impact (Vite already unused)
- Zero dependency impact (packages already removed)
- Improved documentation accuracy
- Cleaner release process
- Enforced quality standards via documentation

## Verification

After implementation:
1. Run `npm run build` - must succeed
2. Run `npm run lint` - must pass
3. Run `npm run test-unit-php` - must pass
4. Verify no remaining "vite" references in codebase
