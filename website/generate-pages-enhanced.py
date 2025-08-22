#!/usr/bin/env python3
"""
Enhanced HTML page generator with Markdown conversion
"""

import os
import re
from pathlib import Path
import markdown

def convert_markdown_to_html(markdown_file_path):
    """Convert Markdown file to HTML with custom styling"""
    try:
        if not os.path.exists(markdown_file_path):
            return None, "File not found"
        
        with open(markdown_file_path, 'r', encoding='utf-8') as f:
            markdown_content = f.read()
        
        # Configure Markdown extensions
        extensions = [
            'markdown.extensions.codehilite',
            'markdown.extensions.fenced_code',
            'markdown.extensions.tables',
            'markdown.extensions.toc',
            'markdown.extensions.nl2br'
        ]
        
        # Convert Markdown to HTML
        html_content = markdown.markdown(markdown_content, extensions=extensions)
        
        # Add custom CSS classes for better styling
        html_content = re.sub(r'<h1>(.*?)</h1>', r'<h1 class="chapter-title">\1</h1>', html_content)
        html_content = re.sub(r'<h2>(.*?)</h2>', r'<h2 class="section-title">\1</h2>', html_content)
        html_content = re.sub(r'<h3>(.*?)</h3>', r'<h3 class="subsection-title">\1</h3>', html_content)
        
        return html_content, None
        
    except Exception as e:
        return None, str(e)

def create_enhanced_chapter_template(chapter_id, title, description, icon, markdown_file, markdown_content=None, error=None):
    """Create enhanced HTML template for a chapter"""
    
    # Content section
    if markdown_content:
        content_section = f"""
        <div class="markdown-content">
            {markdown_content}
        </div>
        """
    else:
        content_section = f"""
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>{title}</strong> {description}
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <h3>Chapter Overview</h3>
            </div>
            <div class="card-body">
                <p>This chapter covers the fundamentals of {title.lower()} in Laravel. You'll learn:</p>
                <ul>
                    <li>Key concepts and principles</li>
                    <li>Practical implementation examples</li>
                    <li>Best practices and common patterns</li>
                    <li>Real-world use cases</li>
                </ul>
            </div>
        </div>
        """
    
    template = f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter {chapter_id}: {title} - Laravel: From Zero to Production</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <style>
        .markdown-content {{
            line-height: 1.8;
            font-size: 1.1rem;
        }}
        .markdown-content h1 {{
            color: #ff2d20;
            border-bottom: 3px solid #ff2d20;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }}
        .markdown-content h2 {{
            color: #636b6f;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 40px;
            margin-bottom: 20px;
        }}
        .markdown-content h3 {{
            color: #495057;
            margin-top: 30px;
            margin-bottom: 15px;
        }}
        .markdown-content p {{
            margin-bottom: 1.2rem;
        }}
        .markdown-content ul, .markdown-content ol {{
            margin-bottom: 1.5rem;
            padding-left: 2rem;
        }}
        .markdown-content code {{
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }}
        .markdown-content pre {{
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            overflow-x: auto;
            margin: 20px 0;
        }}
        .markdown-content pre code {{
            background: none;
            padding: 0;
            border-radius: 0;
        }}
        .markdown-content blockquote {{
            border-left: 4px solid #ff2d20;
            margin: 20px 0;
            padding: 15px 20px;
            background-color: #f8f9fa;
            font-style: italic;
        }}
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.html">
                <i class="fab fa-laravel"></i> Laravel Book
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html#chapters">Chapters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html#projects">Projects</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Chapter Content -->
    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                        <li class="breadcrumb-item"><a href="../index.html#chapters">Chapters</a></li>
                        <li class="breadcrumb-item active">Chapter {chapter_id}: {title}</li>
                    </ol>
                </nav>

                <article class="chapter-content">
                    <h1 class="display-4 text-gradient mb-4">
                        <i class="{icon}"></i> Chapter {chapter_id}: {title}
                    </h1>
                    
                    {content_section}

                    <!-- Markdown Source Link -->
                    <div class="card mt-4">
                        <div class="card-body text-center">
                            <p class="mb-2">
                                <i class="fas fa-file-markdown text-primary"></i>
                                <strong>View Source:</strong>
                            </p>
                            <a href="{markdown_file}" class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Open Markdown File
                            </a>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <a href="../index.html" class="btn btn-outline-primary">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                        <a href="../chapters/index.html" class="btn btn-primary">
                            All Chapters <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2024 Laravel: From Zero to Production. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <script src="../script.js"></script>
</body>
</html>'''
    
    return template

def main():
    """Generate enhanced HTML pages with Markdown conversion"""
    # Create directories
    chapters_dir = Path("chapters")
    chapters_dir.mkdir(exist_ok=True)
    
    # Chapter data
    chapters = [
        {
            "id": "01",
            "title": "Introduction",
            "description": "Welcome to Laravel and overview of the framework",
            "icon": "fas fa-home",
            "markdown_file": "../docs/01-introduction.md"
        },
        {
            "id": "02", 
            "title": "Installation",
            "description": "Setting up your development environment",
            "icon": "fas fa-download",
            "markdown_file": "../docs/02-installation.md"
        }
    ]
    
    # Generate enhanced chapter pages
    print("Generating enhanced chapter pages with Markdown conversion...")
    for chapter in chapters:
        filename = f"{chapter['id']}-{chapter['title'].lower().replace(' ', '-')}.html"
        filepath = chapters_dir / filename
        
        # Convert Markdown to HTML
        markdown_content, error = convert_markdown_to_html(chapter["markdown_file"])
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(create_enhanced_chapter_template(
                chapter["id"],
                chapter["title"], 
                chapter["description"],
                chapter["icon"],
                chapter["markdown_file"],
                markdown_content,
                error
            ))
        
        if error:
            print(f"⚠ Created {filename} (with error: {error})")
        else:
            print(f"✓ Created {filename} (with Markdown content)")
    
    print(f"\n🎉 Successfully generated enhanced chapter pages!")
    print("Features:")
    print("- ✅ Automatic Markdown to HTML conversion")
    print("- ✅ Syntax highlighting for code blocks")
    print("- ✅ Links to original Markdown files")
    print("- ✅ Enhanced styling")

if __name__ == '__main__':
    main()
