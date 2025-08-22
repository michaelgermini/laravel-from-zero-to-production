#!/usr/bin/env python3
"""
Complete HTML page generator with Markdown conversion for all chapters
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

def create_chapter_template(chapter, markdown_content=None, error=None):
    """Create HTML template for a chapter"""
    prev_chapter = None
    next_chapter = None
    
    # Find previous and next chapters
    for i, ch in enumerate(chapters):
        if ch["id"] == chapter["id"]:
            if i > 0:
                prev_chapter = chapters[i-1]
            if i < len(chapters) - 1:
                next_chapter = chapters[i+1]
            break
    
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
            <strong>{chapter["title"]}</strong> {chapter["description"]}
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <h3>Chapter Overview</h3>
            </div>
            <div class="card-body">
                <p>This chapter covers the fundamentals of {chapter["title"].lower()} in Laravel. You'll learn:</p>
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
    <title>Chapter {chapter["id"]}: {chapter["title"]} - Laravel: From Zero to Production</title>
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
        .markdown-content h4 {{
            color: #6c757d;
            margin-top: 25px;
            margin-bottom: 10px;
        }}
        .markdown-content p {{
            margin-bottom: 1.2rem;
        }}
        .markdown-content ul, .markdown-content ol {{
            margin-bottom: 1.5rem;
            padding-left: 2rem;
        }}
        .markdown-content li {{
            margin-bottom: 0.5rem;
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
        .markdown-content table {{
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }}
        .markdown-content th, .markdown-content td {{
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }}
        .markdown-content th {{
            background-color: #ff2d20;
            color: white;
            font-weight: bold;
        }}
        .markdown-content tr:nth-child(even) {{
            background-color: #f8f9fa;
        }}
        .markdown-content strong {{
            font-weight: 600;
            color: #495057;
        }}
        .markdown-content em {{
            color: #6c757d;
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
                        <li class="breadcrumb-item active">Chapter {chapter["id"]}: {chapter["title"]}</li>
                    </ol>
                </nav>

                <article class="chapter-content">
                    <h1 class="display-4 text-gradient mb-4">
                        <i class="{chapter["icon"]}"></i> Chapter {chapter["id"]}: {chapter["title"]}
                    </h1>
                    
                    {content_section}

                    <!-- Markdown Source Link -->
                    <div class="card mt-4">
                        <div class="card-body text-center">
                            <p class="mb-2">
                                <i class="fas fa-file-markdown text-primary"></i>
                                <strong>View Source:</strong>
                            </p>
                            <a href="{chapter["markdown_file"]}" class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Open Markdown File
                            </a>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <a href="../index.html" class="btn btn-outline-primary">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                        <div>
                            {f'<a href="{prev_chapter["id"]}.html" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left"></i> Previous</a>' if prev_chapter else ''}
                            {f'<a href="{next_chapter["id"]}.html" class="btn btn-primary">Next <i class="fas fa-arrow-right"></i></a>' if next_chapter else ''}
                        </div>
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

def create_project_template(project):
    """Create HTML template for a project"""
    template = f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{project["title"]} - Laravel: From Zero to Production</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
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

    <!-- Project Content -->
    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                        <li class="breadcrumb-item"><a href="../index.html#projects">Projects</a></li>
                        <li class="breadcrumb-item active">{project["title"]}</li>
                    </ol>
                </nav>

                <article class="project-content">
                    <h1 class="display-4 text-gradient mb-4">
                        <i class="{project["icon"]}"></i> {project["title"]}
                    </h1>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>{project["title"]}</strong> {project["description"]}
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-signal"></i> Difficulty</h5>
                                    <span class="badge bg-{'success' if project['difficulty'] == 'Beginner' else 'warning' if project['difficulty'] == 'Intermediate' else 'danger'}">{project["difficulty"]}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-clock"></i> Estimated Time</h5>
                                    <p class="card-text">
                                        {{
                                            'Beginner': '2-4 hours',
                                            'Intermediate': '4-8 hours',
                                            'Advanced': '8-16 hours'
                                        }}[project['difficulty']]
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Project Overview</h3>
                        </div>
                        <div class="card-body">
                            <p>This project will teach you how to build a {project["title"].lower()} using Laravel. You'll learn:</p>
                            <ul>
                                <li>Real-world application development</li>
                                <li>Laravel best practices</li>
                                <li>Database design and relationships</li>
                                <li>User interface development</li>
                                <li>Testing and deployment</li>
                            </ul>
                        </div>
                    </div>

                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Ready to start?</strong> This project will help you apply all the concepts learned in the previous chapters.
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <a href="../index.html" class="btn btn-outline-primary">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                        <a href="../chapters/01-introduction.html" class="btn btn-primary">
                            Start Learning <i class="fas fa-arrow-right"></i>
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
    <script src="../script.js"></script>
</body>
</html>'''
    
    return template

# Chapter data
chapters = [
    {
        "id": "01-introduction",
        "title": "Introduction",
        "description": "Welcome to Laravel and overview of the framework",
        "icon": "fas fa-home",
        "markdown_file": "../docs/01-introduction.md"
    },
    {
        "id": "02-installation",
        "title": "Installation",
        "description": "Setting up your development environment",
        "icon": "fas fa-download",
        "markdown_file": "../docs/02-installation.md"
    },
    {
        "id": "03-routing",
        "title": "Routing",
        "description": "Understanding Laravel's routing system",
        "icon": "fas fa-route",
        "markdown_file": "../docs/03-routing.md"
    },
    {
        "id": "04-controllers",
        "title": "Controllers",
        "description": "Creating and managing controllers",
        "icon": "fas fa-cogs",
        "markdown_file": "../docs/04-controllers.md"
    },
    {
        "id": "05-blade",
        "title": "Blade Templates",
        "description": "Working with Laravel's templating engine",
        "icon": "fas fa-file-code",
        "markdown_file": "../docs/05-blade.md"
    },
    {
        "id": "06-eloquent",
        "title": "Eloquent ORM",
        "description": "Database operations with Eloquent",
        "icon": "fas fa-database",
        "markdown_file": "../docs/06-eloquent.md"
    },
    {
        "id": "07-migrations",
        "title": "Migrations",
        "description": "Database schema management",
        "icon": "fas fa-table",
        "markdown_file": "../docs/07-migrations.md"
    },
    {
        "id": "08-middleware",
        "title": "Middleware",
        "description": "Request filtering and processing",
        "icon": "fas fa-filter",
        "markdown_file": "../docs/08-middleware.md"
    },
    {
        "id": "09-auth",
        "title": "Authentication",
        "description": "User authentication and authorization",
        "icon": "fas fa-user-shield",
        "markdown_file": "../docs/09-auth.md"
    },
    {
        "id": "10-events-queues",
        "title": "Events & Queues",
        "description": "Event handling and background jobs",
        "icon": "fas fa-tasks",
        "markdown_file": "../docs/10-events-queues.md"
    },
    {
        "id": "11-testing",
        "title": "Testing",
        "description": "Writing tests for your application",
        "icon": "fas fa-vial",
        "markdown_file": "../docs/11-testing.md"
    },
    {
        "id": "12-deployment",
        "title": "Deployment",
        "description": "Deploying Laravel applications",
        "icon": "fas fa-rocket",
        "markdown_file": "../docs/12-deployment.md"
    },
    {
        "id": "13-caching",
        "title": "Caching",
        "description": "Performance optimization with caching",
        "icon": "fas fa-bolt",
        "markdown_file": "../docs/13-caching.md"
    },
    {
        "id": "14-performance",
        "title": "Performance",
        "description": "Optimizing application performance",
        "icon": "fas fa-tachometer-alt",
        "markdown_file": "../docs/14-performance.md"
    },
    {
        "id": "15-microservices",
        "title": "Microservices",
        "description": "Building scalable microservices",
        "icon": "fas fa-network-wired",
        "markdown_file": "../docs/15-microservices.md"
    }
]

# Project data
projects = [
    {
        "id": "todo-app",
        "title": "Todo Application",
        "description": "Basic CRUD operations with Laravel",
        "icon": "fas fa-tasks",
        "difficulty": "Beginner"
    },
    {
        "id": "blog-platform",
        "title": "Blog Platform",
        "description": "Content management with user authentication",
        "icon": "fas fa-blog",
        "difficulty": "Intermediate"
    },
    {
        "id": "shop-app",
        "title": "E-commerce Shop",
        "description": "Online store with payment integration",
        "icon": "fas fa-shopping-cart",
        "difficulty": "Advanced"
    },
    {
        "id": "rest-api",
        "title": "REST API",
        "description": "Building RESTful APIs with Laravel",
        "icon": "fas fa-code",
        "difficulty": "Intermediate"
    },
    {
        "id": "wizard-form",
        "title": "Multi-Step Wizard",
        "description": "Complex form handling with validation",
        "icon": "fas fa-magic",
        "difficulty": "Advanced"
    }
]

def main():
    """Generate all HTML pages with Markdown conversion"""
    # Create directories
    chapters_dir = Path("chapters")
    projects_dir = Path("projects")
    
    chapters_dir.mkdir(exist_ok=True)
    projects_dir.mkdir(exist_ok=True)
    
    # Generate chapter pages
    print("Generating chapter pages with Markdown conversion...")
    for chapter in chapters:
        filename = f"{chapter['id']}.html"
        filepath = chapters_dir / filename
        
        # Convert Markdown to HTML
        markdown_content, error = convert_markdown_to_html(chapter["markdown_file"])
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(create_chapter_template(chapter, markdown_content, error))
        
        if error:
            print(f"⚠ Created {filename} (with error: {error})")
        else:
            print(f"✓ Created {filename} (with Markdown content)")
    
    # Generate project pages
    print("\nGenerating project pages...")
    for project in projects:
        filename = f"{project['id']}.html"
        filepath = projects_dir / filename
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(create_project_template(project))
        
        print(f"✓ Created {filename}")
    
    print(f"\n🎉 Successfully generated {len(chapters)} chapter pages and {len(projects)} project pages!")
    print("Features:")
    print("- ✅ Automatic Markdown to HTML conversion")
    print("- ✅ Syntax highlighting for code blocks")
    print("- ✅ Links to original Markdown files")
    print("- ✅ Enhanced styling and navigation")
    print("- ✅ Previous/Next chapter navigation")
    print("\nYou can now navigate to:")
    print("- Chapters: http://localhost:8000/website/chapters/")
    print("- Projects: http://localhost:8000/website/projects/")

if __name__ == '__main__':
    main()
