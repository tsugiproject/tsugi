# Lessons Items Array Format

This document describes the new "items" array format for lessons.json and how to use it.

## Overview

The new format allows all module content (videos, lti tools, discussions, references, etc.) to be combined into a single ordered `items` array. Each item has a `type` field that determines how it's rendered.

## Benefits

- **Flexible ordering**: Content can be presented in any order
- **Custom labeling**: Add headers and text between items
- **Better UI control**: Mix different content types as needed
- **Backward compatible**: Old format still works

## Item Types

### Basic Types
- `header` - Render a heading (supports `level`, `text`, `class`)
- `text` - Render text content (supports `text`, `tag`, `class`)
- `video` - Single video item
- `slide` - Single slide item
- `reference` - Single reference link
- `discussion` - Single discussion item
- `lti` - Single LTI tool
- `assignment` - Assignment specification link
- `solution` - Assignment solution link
- `chapters` - Chapter numbers

### Grouped Types
- `videos` - Group of videos (contains `items` array of video objects)
- `references` - Group of references (contains `items` array of reference objects)
- `discussions` - Group of discussions (contains `items` array of discussion objects)
- `ltis` - Group of LTI tools (contains `items` array of lti objects)
- `slides` - Group of slides (contains `items` array of slide objects)
- `carousel` - Video carousel (contains `items` array of video objects)

## Example Module with Items Array

```json
{
    "title": "Why Program?",
    "anchor": "intro",
    "icon": "fa-hand-o-right",
    "description": "We learn why one might want to learn to program.",
    "items": [
        {
            "type": "header",
            "level": 2,
            "text": "Learning Materials"
        },
        {
            "type": "references",
            "title": "References",
            "items": [
                {
                    "type": "reference",
                    "title": "Chapter 1: Introduction",
                    "href": "{apphome}/html3/01-intro"
                }
            ]
        },
        {
            "type": "videos",
            "title": "Videos",
            "items": [
                {
                    "type": "video",
                    "title": "Why Program - Part 1 (12:31)",
                    "youtube": "fvhNadKjE8g"
                },
                {
                    "type": "video",
                    "title": "Why Program - Part 2 (12:16)",
                    "youtube": "VQZTZsXk8sA"
                }
            ]
        },
        {
            "type": "text",
            "text": "After watching the videos, try the exercises below.",
            "tag": "p",
            "class": "instruction-text"
        },
        {
            "type": "discussions",
            "title": "Discussions",
            "items": [
                {
                    "type": "discussion",
                    "title": "Why Program?",
                    "launch": "mod/tdiscus/",
                    "resource_link_id": "discuss_01_hello"
                }
            ]
        },
        {
            "type": "ltis",
            "title": "Tools",
            "items": [
                {
                    "type": "lti",
                    "title": "Autograder: Write Hello World",
                    "launch": "tools/pythonauto/",
                    "resource_link_id": "pythonauto_01_hello",
                    "custom": [
                        {
                            "key": "exercise",
                            "value": "hello"
                        }
                    ]
                }
            ]
        }
    ]
}
```

## Conversion

Use the conversion script to convert existing lessons.json files:

```bash
php convert-lessons-to-items.php lessons.json lessons-items.json
```

The script will:
1. Read the input JSON file
2. Convert all modules to use the items array format
3. Preserve old arrays for backward compatibility
4. Write the output to a new file

## Implementation

The extended `Lessons2.php` class supports both formats:
- If a module has an `items` array, it uses the new format
- Otherwise, it falls back to the old format (videos[], lti[], etc.)

## Integration

The `Lessons2.php` file in `tsugi/vendor/tsugi/lib/src/UI/Lessons2.php` has been updated to support the items array format.

### How It Works
The `renderSingle()` method checks for `items` array first:
- If `items` array exists and has content → use new format
- Otherwise → fall back to old format (backward compatible)

The implementation includes:
- `renderItem()` - Main method to render items based on type
- Private render methods for each item type (header, text, video, lti, etc.)
- Support for both individual items and grouped items (videos[], references[], etc.)

## Migration Path

1. Convert your lessons.json using the conversion script
2. Test the converted file
3. Once verified, you can manually remove the old arrays from modules
4. The system will continue to work with either format
