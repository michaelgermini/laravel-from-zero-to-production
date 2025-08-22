#!/usr/bin/env python3
"""
Laravel Book Ebook Generator
Converts markdown files to PDF ebook format
"""

import os
import sys
import markdown
import argparse
from pathlib import Path
from datetime import datetime
import subprocess

try:
    from weasyprint import HTML, CSS
    WEASYPRINT_AVAILABLE = True
except ImportError:
    WEASYPRINT_AVAILABLE = False

try:
    import pdfkit
    PDFKIT_AVAILABLE = True
except ImportError:
    PDFKIT_AVAILABLE = False

class EbookGenerator:
    def __init__(self, docs_dir="docs", output_dir="ebook"):
        self.docs_dir = Path(docs_dir)
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(exist_ok=True)
        
        # Chapter order
        self.chapters = [
            "01-introduction.md",
            "02-installation.md", 
            "03-routing.md",
            "04-controllers.md",
            "05-blade.md",
            "06-eloquent.md",
            "07-migrations.md",
            "08-middleware.md",
            "09-auth.md",
            "10-events-queues.md",
            "11-testing.md",
            "12-deployment.md",
            "13-caching.md",
            "14-performance.md",
            "15-microservices.md"
        ]
        
        # CSS for styling
        self.css = """
        @page {
            size: A4;
            margin: 2cm;
            @top-center {
                content: "Laravel: From Zero to Production";
                font-size: 10pt;
                color: #666;
            }
            @bottom-center {
                content: counter(page);
                font-size: 10pt;
                color: #666;
            }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 100%;
        }
        
        h1 {
            color: #ff2d20;
            border-bottom: 3px solid #ff2d20;
            padding-bottom: 10px;
            page-break-after: avoid;
        }
        
        h2 {
            color: #636b6f;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            page-break-after: avoid;
        }
        
        h3 {
            color: #495057;
            page-break-after: avoid;
        }
        
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        pre {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            overflow-x: auto;
            page-break-inside: avoid;
        }
        
        pre code {
            background: none;
            padding: 0;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        
        th {
            background-color: #ff2d20;
            color: white;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        blockquote {
            border-left: 4px solid #ff2d20;
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #f8f9fa;
            font-style: italic;
        }
        
        .toc {
            page-break-after: always;
        }
        
        .toc h1 {
            text-align: center;
            border: none;
            margin-bottom: 30px;
        }
        
        .toc ul {
            list-style: none;
            padding: 0;
        }
        
        .toc li {
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .toc a {
            text-decoration: none;
            color: #333;
        }
        
        .chapter {
            page-break-before: always;
        }
        
        .chapter:first-child {
            page-break-before: avoid;
        }
        
        .cover {
            text-align: center;
            page-break-after: always;
        }
        
        .cover h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            border: none;
        }
        
        .cover .subtitle {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 40px;
        }
        
        .cover .author {
            font-size: 1.1em;
            margin-bottom: 60px;
        }
        
        .cover .date {
            font-size: 1em;
            color: #666;
        }
        """
    
    def generate_toc(self):
        """Generate table of contents"""
        toc_html = """
        <div class="toc">
            <h1>Table of Contents</h1>
            <ul>
        """
        
        for chapter in self.chapters:
            if chapter.endswith('.md'):
                chapter_name = chapter.replace('.md', '').replace('-', ' ').title()
                chapter_number = chapter.split('-')[0]
                toc_html += f'<li><a href="#{chapter}">{chapter_number}. {chapter_name}</a></li>'
        
        toc_html += """
            </ul>
        </div>
        """
        
        return toc_html
    
    def generate_cover(self):
        """Generate cover page"""
        cover_html = f"""
        <div class="cover">
            <h1>Laravel: From Zero to Production</h1>
            <div class="subtitle">A comprehensive guide to building modern web applications with Laravel</div>
            <div class="author">By Laravel Book Team</div>
            <div class="date">Generated on {datetime.now().strftime('%B %d, %Y')}</div>
        </div>
        """
        
        return cover_html
    
    def convert_markdown_to_html(self, markdown_file):
        """Convert markdown file to HTML"""
        if not markdown_file.exists():
            print(f"Warning: {markdown_file} not found")
            return ""
        
        with open(markdown_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Convert markdown to HTML
        html = markdown.markdown(
            content,
            extensions=[
                'markdown.extensions.codehilite',
                'markdown.extensions.tables',
                'markdown.extensions.toc',
                'markdown.extensions.fenced_code'
            ]
        )
        
        return f'<div class="chapter" id="{markdown_file.stem}">{html}</div>'
    
    def generate_html(self):
        """Generate complete HTML document"""
        html_content = f"""
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Laravel: From Zero to Production</title>
            <style>{self.css}</style>
        </head>
        <body>
        """
        
        # Add cover
        html_content += self.generate_cover()
        
        # Add table of contents
        html_content += self.generate_toc()
        
        # Add chapters
        for chapter in self.chapters:
            chapter_file = self.docs_dir / chapter
            if chapter_file.exists():
                html_content += self.convert_markdown_to_html(chapter_file)
        
        html_content += """
        </body>
        </html>
        """
        
        return html_content
    
    def generate_pdf_weasyprint(self, html_content, output_file):
        """Generate PDF using WeasyPrint"""
        if not WEASYPRINT_AVAILABLE:
            raise ImportError("WeasyPrint not available")
        
        html = HTML(string=html_content)
        css = CSS(string=self.css)
        
        html.write_pdf(output_file, stylesheets=[css])
    
    def generate_pdf_pdfkit(self, html_content, output_file):
        """Generate PDF using pdfkit (wkhtmltopdf)"""
        if not PDFKIT_AVAILABLE:
            raise ImportError("pdfkit not available")
        
        options = {
            'page-size': 'A4',
            'margin-top': '2cm',
            'margin-right': '2cm',
            'margin-bottom': '2cm',
            'margin-left': '2cm',
            'encoding': 'UTF-8',
            'no-outline': None
        }
        
        pdfkit.from_string(html_content, output_file, options=options)
    
    def generate_pdf_pandoc(self, output_file):
        """Generate PDF using pandoc"""
        try:
            # Create a combined markdown file
            combined_md = self.output_dir / "combined.md"
            
            with open(combined_md, 'w', encoding='utf-8') as f:
                f.write("# Laravel: From Zero to Production\n\n")
                f.write(f"*Generated on {datetime.now().strftime('%B %d, %Y')}*\n\n")
                f.write("## Table of Contents\n\n")
                
                for chapter in self.chapters:
                    if chapter.endswith('.md'):
                        chapter_name = chapter.replace('.md', '').replace('-', ' ').title()
                        chapter_number = chapter.split('-')[0]
                        f.write(f"{chapter_number}. [{chapter_name}](#{chapter.replace('.md', '')})\n")
                
                f.write("\n---\n\n")
                
                for chapter in self.chapters:
                    chapter_file = self.docs_dir / chapter
                    if chapter_file.exists():
                        f.write(f"\n# {chapter.replace('.md', '').replace('-', ' ').title()}\n\n")
                        with open(chapter_file, 'r', encoding='utf-8') as cf:
                            f.write(cf.read())
                        f.write("\n\n---\n\n")
            
            # Convert to PDF using pandoc
            cmd = [
                'pandoc',
                str(combined_md),
                '-o', str(output_file),
                '--pdf-engine=xelatex',
                '--toc',
                '--number-sections',
                '--variable=geometry:margin=2cm',
                '--variable=fontsize:11pt',
                '--variable=mainfont:Segoe UI',
                '--variable=monofont:Courier New'
            ]
            
            subprocess.run(cmd, check=True)
            
            # Clean up
            combined_md.unlink()
            
        except subprocess.CalledProcessError as e:
            print(f"Error running pandoc: {e}")
            raise
        except FileNotFoundError:
            print("pandoc not found. Please install pandoc first.")
            raise
    
    def generate_epub(self, output_file):
        """Generate EPUB using pandoc"""
        try:
            # Create a combined markdown file
            combined_md = self.output_dir / "combined.md"
            
            with open(combined_md, 'w', encoding='utf-8') as f:
                f.write("# Laravel: From Zero to Production\n\n")
                f.write(f"*Generated on {datetime.now().strftime('%B %d, %Y')}*\n\n")
                
                for chapter in self.chapters:
                    chapter_file = self.docs_dir / chapter
                    if chapter_file.exists():
                        f.write(f"\n# {chapter.replace('.md', '').replace('-', ' ').title()}\n\n")
                        with open(chapter_file, 'r', encoding='utf-8') as cf:
                            f.write(cf.read())
                        f.write("\n\n---\n\n")
            
            # Convert to EPUB using pandoc
            cmd = [
                'pandoc',
                str(combined_md),
                '-o', str(output_file),
                '--toc',
                '--number-sections',
                '--epub-cover-image=cover.png' if (self.output_dir / "cover.png").exists() else ''
            ]
            
            subprocess.run(cmd, check=True)
            
            # Clean up
            combined_md.unlink()
            
        except subprocess.CalledProcessError as e:
            print(f"Error running pandoc: {e}")
            raise
        except FileNotFoundError:
            print("pandoc not found. Please install pandoc first.")
            raise
    
    def generate_all(self):
        """Generate all formats"""
        print("Generating Laravel Book Ebook...")
        
        # Generate HTML
        html_content = self.generate_html()
        html_file = self.output_dir / "laravel-book.html"
        
        with open(html_file, 'w', encoding='utf-8') as f:
            f.write(html_content)
        
        print(f"✓ HTML generated: {html_file}")
        
        # Generate PDF
        pdf_file = self.output_dir / "laravel-book.pdf"
        
        try:
            if WEASYPRINT_AVAILABLE:
                self.generate_pdf_weasyprint(html_content, pdf_file)
                print(f"✓ PDF generated (WeasyPrint): {pdf_file}")
            elif PDFKIT_AVAILABLE:
                self.generate_pdf_pdfkit(html_content, pdf_file)
                print(f"✓ PDF generated (pdfkit): {pdf_file}")
            else:
                self.generate_pdf_pandoc(pdf_file)
                print(f"✓ PDF generated (pandoc): {pdf_file}")
        except Exception as e:
            print(f"✗ PDF generation failed: {e}")
        
        # Generate EPUB
        epub_file = self.output_dir / "laravel-book.epub"
        
        try:
            self.generate_epub(epub_file)
            print(f"✓ EPUB generated: {epub_file}")
        except Exception as e:
            print(f"✗ EPUB generation failed: {e}")
        
        print("\nEbook generation complete!")

def main():
    parser = argparse.ArgumentParser(description='Generate Laravel Book Ebook')
    parser.add_argument('--docs-dir', default='docs', help='Directory containing markdown files')
    parser.add_argument('--output-dir', default='ebook', help='Output directory for generated files')
    parser.add_argument('--format', choices=['html', 'pdf', 'epub', 'all'], default='all', 
                       help='Output format')
    
    args = parser.parse_args()
    
    generator = EbookGenerator(args.docs_dir, args.output_dir)
    
    if args.format == 'all':
        generator.generate_all()
    elif args.format == 'html':
        html_content = generator.generate_html()
        html_file = generator.output_dir / "laravel-book.html"
        with open(html_file, 'w', encoding='utf-8') as f:
            f.write(html_content)
        print(f"✓ HTML generated: {html_file}")
    elif args.format == 'pdf':
        html_content = generator.generate_html()
        pdf_file = generator.output_dir / "laravel-book.pdf"
        try:
            if WEASYPRINT_AVAILABLE:
                generator.generate_pdf_weasyprint(html_content, pdf_file)
            elif PDFKIT_AVAILABLE:
                generator.generate_pdf_pdfkit(html_content, pdf_file)
            else:
                generator.generate_pdf_pandoc(pdf_file)
            print(f"✓ PDF generated: {pdf_file}")
        except Exception as e:
            print(f"✗ PDF generation failed: {e}")
    elif args.format == 'epub':
        epub_file = generator.output_dir / "laravel-book.epub"
        try:
            generator.generate_epub(epub_file)
            print(f"✓ EPUB generated: {epub_file}")
        except Exception as e:
            print(f"✗ EPUB generation failed: {e}")

if __name__ == '__main__':
    main()
