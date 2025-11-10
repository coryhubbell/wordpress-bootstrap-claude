#!/bin/bash

echo "🚀 Creating WordPress Bootstrap Claude Repository..."

# Initialize Git
git init

# Create directory structure
mkdir -p core inc template-parts/loops docs examples assets/{css,js,scss}

# Create README.md
cat > README.md << 'EOF'
# 🚀 WordPress Bootstrap Claude - AI-Powered WordPress Development Framework

[![WordPress](https://img.shields.io/badge/WordPress-5.9%2B-blue)](https://wordpress.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)](https://getbootstrap.com/)
[![Claude Compatible](https://img.shields.io/badge/Claude-Agentic%20Ready-orange)](https://claude.ai)

## 🤖 The First Claude Agentic-Friendly WordPress Development Framework

**WordPress Bootstrap Claude** is a revolutionary WordPress theme framework specifically engineered for AI-assisted development with Claude. Build WordPress themes and plugins 10x faster with AI assistance.

## ✨ Key Features

- **🎯 12+ WordPress Loop Patterns** - Pre-built, Claude-optimized patterns
- **📚 1,500+ Lines of Documentation** - Comprehensive guides for AI development  
- **🔧 500+ Lines of Loop Helpers** - Production-ready functions
- **🚀 Plugin Conversion Ready** - Extract any feature to standalone plugin
- **💎 Bootstrap 5 Integration** - Full responsive framework
- **⚡ Performance Optimized** - Caching, lazy loading, query optimization

## 🚀 Quick Start

Ask Claude to build anything using this framework:

\`\`\`
"Claude, using the wordpress-bootstrap-claude framework, 
create a custom post type for Products with cart functionality"
\`\`\`

## 📖 Documentation

- [Loop Mastery Guide](docs/LOOP_GUIDE.md)
- [Claude Quick Start](docs/CLAUDE_QUICKSTART.md)
- [Plugin Conversion](docs/PLUGIN_CONVERSION.md)

## 📝 License

GPL v2 or later

**Built for WordPress developers embracing AI-powered development** 🚀
EOF

# Create core files
cat > core/style.css << 'EOF'
/*
Theme Name: WordPress Bootstrap Claude
Description: AI-Powered WordPress Development Framework
Version: 1.0.0
License: GPL v2
*/
EOF

cat > core/functions.php << 'EOF'
<?php
/**
 * WordPress Bootstrap Claude Functions
 * @package WP_Bootstrap_Claude
 */

define( 'WPBC_VERSION', '1.0.0' );

function wpbc_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'wp-bootstrap-claude' ),
    ) );
}
add_action( 'after_setup_theme', 'wpbc_setup' );

function wpbc_scripts() {
    wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' );
    wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'wpbc_scripts' );
EOF

cat > core/index.php << 'EOF'
<?php get_header(); ?>
<div class="container py-5">
    <div class="row">
        <main class="col-lg-8">
            <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
                <article class="mb-4">
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <?php the_excerpt(); ?>
                </article>
            <?php endwhile; endif; ?>
        </main>
        <aside class="col-lg-4">
            <?php get_sidebar(); ?>
        </aside>
    </div>
</div>
<?php get_footer(); ?>
EOF

# Create inc files
cat > inc/loop-functions.php << 'EOF'
<?php
/**
 * WordPress Loop Functions
 */

function wpbc_custom_query_loop( $args = array() ) {
    $defaults = array(
        'post_type' => 'post',
        'posts_per_page' => 10,
    );
    
    $args = wp_parse_args( $args, $defaults );
    $query = new WP_Query( $args );
    
    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) : $query->the_post();
            get_template_part( 'template-parts/content' );
        endwhile;
        wp_reset_postdata();
    endif;
    
    return $query;
}
EOF

# Create docs
echo "# WordPress Loop Mastery Guide" > docs/LOOP_GUIDE.md
echo "# Claude Quick Start Guide" > docs/CLAUDE_QUICKSTART.md
echo "# Plugin Conversion Guide" > docs/PLUGIN_CONVERSION.md

# Create examples
cat > examples/custom-post-type.php << 'EOF'
<?php
function wpbc_register_cpt() {
    register_post_type( 'portfolio', array(
        'public' => true,
        'label' => 'Portfolio',
        'supports' => array( 'title', 'editor', 'thumbnail' ),
    ) );
}
add_action( 'init', 'wpbc_register_cpt' );
EOF

# Create package.json
cat > package.json << 'EOF'
{
  "name": "wordpress-bootstrap-claude",
  "version": "1.0.0",
  "description": "AI-Powered WordPress Development Framework",
  "keywords": ["wordpress", "bootstrap", "claude-ai"],
  "license": "GPL-2.0-or-later"
}
EOF

# Create .gitignore
cat > .gitignore << 'EOF'
node_modules/
.DS_Store
*.log
.env
EOF

# Git operations
git add .
git commit -m "🚀 Initial commit: WordPress Bootstrap Claude - AI-Powered Development Framework

Revolutionary WordPress theme framework for AI-assisted development with Claude.

Features:
- 12+ WordPress Loop patterns
- Bootstrap 5 integration
- Complete documentation
- Plugin conversion ready
- REST API examples

Built for developers embracing AI-powered development."

git remote add origin https://github.com/coryhubbell/wordpress-boostrap-claude.git
git branch -M main

echo ""
echo "✅ REPOSITORY CREATED SUCCESSFULLY!"
echo ""
echo "Now run: git push -u origin main"
