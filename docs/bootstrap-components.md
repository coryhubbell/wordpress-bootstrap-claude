# 📕 Bootstrap Components Guide

Complete guide to using Bootstrap 5 components with WordPress Bootstrap Claude framework.

## Table of Contents
- [Introduction](#introduction)
- [Component System](#component-system)
- [Layout Components](#layout-components)
- [Content Components](#content-components)
- [Form Components](#form-components)
- [Navigation Components](#navigation-components)
- [Interactive Components](#interactive-components)
- [Custom Components](#custom-components)
- [Best Practices](#best-practices)

---

## Introduction

WordPress Bootstrap Claude seamlessly integrates Bootstrap 5 components with WordPress functionality, providing:

- **WordPress-aware components** - Integrated with WP data
- **Reusable templates** - DRY principle
- **Accessibility built-in** - WCAG 2.1 AA compliant
- **Responsive by default** - Mobile-first approach
- **Customizable** - Easy to extend and modify

---

## Component System

### Using Components

```php
// Basic component usage
wpbc_component('card', [
    'title' => 'Card Title',
    'content' => 'Card content goes here',
    'link' => 'https://example.com'
]);

// Component with WordPress data
wpbc_component('post-card', [
    'post' => get_post(),
    'show_excerpt' => true,
    'thumbnail_size' => 'medium'
]);
```

### Component Structure

Components are stored in `/templates/components/`:

```php
// templates/components/card.php
<?php
$defaults = [
    'title' => '',
    'content' => '',
    'image' => '',
    'link' => '',
    'link_text' => 'Read More',
    'classes' => ''
];
$args = wp_parse_args($args, $defaults);
?>
<div class="card <?php echo esc_attr($args['classes']); ?>">
    <?php if ($args['image']) : ?>
        <img src="<?php echo esc_url($args['image']); ?>" class="card-img-top" alt="">
    <?php endif; ?>
    <div class="card-body">
        <?php if ($args['title']) : ?>
            <h5 class="card-title"><?php echo esc_html($args['title']); ?></h5>
        <?php endif; ?>
        <?php if ($args['content']) : ?>
            <p class="card-text"><?php echo wp_kses_post($args['content']); ?></p>
        <?php endif; ?>
        <?php if ($args['link']) : ?>
            <a href="<?php echo esc_url($args['link']); ?>" class="btn btn-primary">
                <?php echo esc_html($args['link_text']); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
```

---

## Layout Components

### Container

```php
// Standard container
<div class="container">
    <!-- Content -->
</div>

// Fluid container
<div class="container-fluid">
    <!-- Full width content -->
</div>

// Responsive containers
<div class="container-md"> <!-- 100% wide until md breakpoint -->
    <!-- Content -->
</div>
```

### Grid System

```php
// Basic grid
<div class="container">
    <div class="row">
        <div class="col-md-8">Main Content</div>
        <div class="col-md-4">Sidebar</div>
    </div>
</div>

// WordPress Loop with grid
<?php
$loop = new WPBC_Loop(['posts_per_page' => 9]);
if ($loop->have_posts()) : ?>
    <div class="row">
        <?php while ($loop->have_posts()) : $loop->the_post(); ?>
            <div class="col-md-4 mb-4">
                <?php wpbc_component('card'); ?>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif;
```

### Columns

```php
// Auto-layout columns
<div class="row">
    <div class="col">1 of 3</div>
    <div class="col">2 of 3</div>
    <div class="col">3 of 3</div>
</div>

// Responsive columns
<div class="row">
    <div class="col-12 col-md-6 col-lg-4">
        <!-- Full width mobile, half tablet, third desktop -->
    </div>
</div>

// Column ordering
<div class="row">
    <div class="col-md-8 order-2 order-md-1">Main</div>
    <div class="col-md-4 order-1 order-md-2">Sidebar</div>
</div>
```

---

## Content Components

### Cards

```php
// Basic card
wpbc_component('card', [
    'title' => get_the_title(),
    'content' => get_the_excerpt(),
    'image' => get_the_post_thumbnail_url(),
    'link' => get_permalink()
]);

// Card variations
?>
<div class="card text-center">
    <div class="card-header">Featured</div>
    <div class="card-body">
        <h5 class="card-title"><?php the_title(); ?></h5>
        <p class="card-text"><?php the_excerpt(); ?></p>
        <a href="<?php the_permalink(); ?>" class="btn btn-primary">Go somewhere</a>
    </div>
    <div class="card-footer text-muted">
        <?php echo get_the_date(); ?>
    </div>
</div>

<!-- Card with list group -->
<div class="card">
    <div class="card-header">Categories</div>
    <ul class="list-group list-group-flush">
        <?php wp_list_categories([
            'title_li' => '',
            'walker' => new WPBC_Bootstrap_Walker()
        ]); ?>
    </ul>
</div>
```

### Accordion

```php
// FAQ Accordion
<?php
$faqs = new WPBC_Loop([
    'post_type' => 'faq',
    'posts_per_page' => -1
]);
?>
<div class="accordion" id="faqAccordion">
    <?php $i = 0; while ($faqs->have_posts()) : $faqs->the_post(); ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                <button class="accordion-button <?php echo $i > 0 ? 'collapsed' : ''; ?>" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#collapse<?php echo $i; ?>" 
                        aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                    <?php the_title(); ?>
                </button>
            </h2>
            <div id="collapse<?php echo $i; ?>" 
                 class="accordion-collapse collapse <?php echo $i === 0 ? 'show' : ''; ?>" 
                 data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>
    <?php $i++; endwhile; ?>
</div>
```

### Alerts

```php
// Success message after form submission
<?php if (isset($_GET['success'])) : ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Your message has been sent.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

// WordPress admin notices style
<?php if (current_user_can('edit_posts')) : ?>
    <div class="alert alert-info" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        You are viewing this as an administrator.
    </div>
<?php endif; ?>
```

### Badges

```php
// Post meta with badges
<div class="post-meta">
    <span class="badge bg-primary"><?php the_category(', '); ?></span>
    <span class="badge bg-secondary"><?php the_tags('', ', '); ?></span>
    <?php if (get_comments_number() > 0) : ?>
        <span class="badge bg-info">
            <?php comments_number('0', '1', '%'); ?> Comments
        </span>
    <?php endif; ?>
</div>
```

---

## Form Components

### Contact Form

```php
// Bootstrap form with WordPress processing
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="needs-validation" novalidate>
    <?php wp_nonce_field('contact_form', 'contact_nonce'); ?>
    <input type="hidden" name="action" value="process_contact_form">
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="firstName" class="form-label">First Name</label>
            <input type="text" class="form-control" id="firstName" name="first_name" required>
            <div class="invalid-feedback">Please provide your first name.</div>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="lastName" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="lastName" name="last_name" required>
            <div class="invalid-feedback">Please provide your last name.</div>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
        <div class="invalid-feedback">Please provide a valid email.</div>
    </div>
    
    <div class="mb-3">
        <label for="message" class="form-label">Message</label>
        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
        <div class="invalid-feedback">Please enter your message.</div>
    </div>
    
    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="subscribe" name="subscribe">
            <label class="form-check-label" for="subscribe">
                Subscribe to newsletter
            </label>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary">Send Message</button>
</form>

<script>
// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>
```

### Search Form

```php
// Enhanced WordPress search form
<form role="search" method="get" action="<?php echo home_url('/'); ?>" class="search-form">
    <div class="input-group">
        <input type="search" 
               class="form-control" 
               placeholder="Search..." 
               value="<?php echo get_search_query(); ?>" 
               name="s">
        <button class="btn btn-outline-secondary" type="submit">
            <i class="bi bi-search"></i>
        </button>
    </div>
</form>
```

---

## Navigation Components

### Navbar

```php
// WordPress menu with Bootstrap navbar
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="<?php echo home_url(); ?>">
            <?php bloginfo('name'); ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'menu_class' => 'navbar-nav ms-auto',
                'container' => false,
                'walker' => new WPBC_Bootstrap_Nav_Walker()
            ]);
            ?>
        </div>
    </div>
</nav>
```

### Breadcrumb

```php
// WordPress breadcrumb with Bootstrap styling
function wpbc_breadcrumb() {
    if (!is_home()) {
        echo '<nav aria-label="breadcrumb">';
        echo '<ol class="breadcrumb">';
        echo '<li class="breadcrumb-item"><a href="' . home_url() . '">Home</a></li>';
        
        if (is_category() || is_single()) {
            $categories = get_the_category();
            if ($categories) {
                echo '<li class="breadcrumb-item">';
                echo '<a href="' . get_category_link($categories[0]->term_id) . '">';
                echo $categories[0]->name . '</a></li>';
            }
            
            if (is_single()) {
                echo '<li class="breadcrumb-item active" aria-current="page">';
                the_title();
                echo '</li>';
            }
        } elseif (is_page()) {
            echo '<li class="breadcrumb-item active" aria-current="page">';
            the_title();
            echo '</li>';
        }
        
        echo '</ol>';
        echo '</nav>';
    }
}
```

### Pagination

```php
// Bootstrap pagination for WordPress
function wpbc_pagination($query = null) {
    global $wp_query;
    $query = $query ?: $wp_query;
    
    $big = 999999999;
    $paginate_links = paginate_links([
        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $query->max_num_pages,
        'type' => 'array',
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;'
    ]);
    
    if ($paginate_links) {
        echo '<nav aria-label="Page navigation">';
        echo '<ul class="pagination justify-content-center">';
        foreach ($paginate_links as $link) {
            $active = strpos($link, 'current') ? ' active' : '';
            echo '<li class="page-item' . $active . '">';
            echo str_replace('page-numbers', 'page-link', $link);
            echo '</li>';
        }
        echo '</ul>';
        echo '</nav>';
    }
}
```

### Tabs

```php
// Content tabs
<ul class="nav nav-tabs" id="contentTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description">
            Description
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews">
            Reviews (<?php echo get_comments_number(); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#related">
            Related
        </button>
    </li>
</ul>

<div class="tab-content" id="contentTabContent">
    <div class="tab-pane fade show active" id="description">
        <?php the_content(); ?>
    </div>
    <div class="tab-pane fade" id="reviews">
        <?php comments_template(); ?>
    </div>
    <div class="tab-pane fade" id="related">
        <?php wpbc_related_posts(); ?>
    </div>
</div>
```

---

## Interactive Components

### Modal

```php
// Login modal
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
    Login
</button>

<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Login</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php wp_login_form([
                    'redirect' => home_url(),
                    'form_id' => 'loginform-modal',
                    'label_username' => 'Username',
                    'label_password' => 'Password',
                    'label_remember' => 'Remember Me',
                    'label_log_in' => 'Log In',
                    'remember' => true
                ]); ?>
            </div>
            <div class="modal-footer">
                <a href="<?php echo wp_lostpassword_url(); ?>">Forgot Password?</a>
            </div>
        </div>
    </div>
</div>
```

### Carousel

```php
// Featured posts carousel
<?php
$featured = new WPBC_Loop([
    'meta_key' => 'featured',
    'meta_value' => 'yes',
    'posts_per_page' => 5
]);

if ($featured->have_posts()) : ?>
    <div id="featuredCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php for ($i = 0; $i < $featured->found_posts(); $i++) : ?>
                <button type="button" 
                        data-bs-target="#featuredCarousel" 
                        data-bs-slide-to="<?php echo $i; ?>" 
                        <?php echo $i === 0 ? 'class="active"' : ''; ?>>
                </button>
            <?php endfor; ?>
        </div>
        
        <div class="carousel-inner">
            <?php $i = 0; while ($featured->have_posts()) : $featured->the_post(); ?>
                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                    <?php if (has_post_thumbnail()) : ?>
                        <img src="<?php the_post_thumbnail_url('large'); ?>" 
                             class="d-block w-100" 
                             alt="<?php the_title(); ?>">
                    <?php endif; ?>
                    <div class="carousel-caption d-none d-md-block">
                        <h5><?php the_title(); ?></h5>
                        <p><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                        <a href="<?php the_permalink(); ?>" class="btn btn-primary">Read More</a>
                    </div>
                </div>
            <?php $i++; endwhile; ?>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#featuredCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#featuredCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
<?php endif;
$featured->reset();
```

### Tooltips & Popovers

```php
// Tooltips on post meta
<div class="post-meta">
    <span data-bs-toggle="tooltip" title="Published Date">
        <i class="bi bi-calendar"></i> <?php the_date(); ?>
    </span>
    <span data-bs-toggle="tooltip" title="Author">
        <i class="bi bi-person"></i> <?php the_author(); ?>
    </span>
    <span data-bs-toggle="tooltip" title="Reading Time">
        <i class="bi bi-clock"></i> <?php echo wpbc_reading_time(); ?>
    </span>
</div>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});
</script>
```

---

## Custom Components

### Creating Custom Components

```php
// templates/components/testimonial.php
<?php
$defaults = [
    'author' => '',
    'company' => '',
    'content' => '',
    'rating' => 5,
    'image' => ''
];
$args = wp_parse_args($args, $defaults);
?>
<div class="testimonial-card card h-100">
    <div class="card-body">
        <div class="testimonial-rating mb-2">
            <?php for ($i = 1; $i <= 5; $i++) : ?>
                <i class="bi bi-star<?php echo $i <= $args['rating'] ? '-fill' : ''; ?> text-warning"></i>
            <?php endfor; ?>
        </div>
        
        <blockquote class="blockquote">
            <p><?php echo wp_kses_post($args['content']); ?></p>
        </blockquote>
        
        <div class="testimonial-author d-flex align-items-center">
            <?php if ($args['image']) : ?>
                <img src="<?php echo esc_url($args['image']); ?>" 
                     class="rounded-circle me-3" 
                     width="50" height="50" 
                     alt="<?php echo esc_attr($args['author']); ?>">
            <?php endif; ?>
            <div>
                <h6 class="mb-0"><?php echo esc_html($args['author']); ?></h6>
                <?php if ($args['company']) : ?>
                    <small class="text-muted"><?php echo esc_html($args['company']); ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
```

### Using Custom Components

```php
// Display testimonials
$testimonials = new WPBC_Loop([
    'post_type' => 'testimonial',
    'posts_per_page' => 3
]);

if ($testimonials->have_posts()) : ?>
    <div class="row">
        <?php while ($testimonials->have_posts()) : $testimonials->the_post(); ?>
            <div class="col-md-4 mb-4">
                <?php wpbc_component('testimonial', [
                    'author' => get_the_title(),
                    'company' => get_field('company'),
                    'content' => get_the_content(),
                    'rating' => get_field('rating'),
                    'image' => get_the_post_thumbnail_url('thumbnail')
                ]); ?>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif;
```

---

## Best Practices

### 1. Accessibility

```php
// Always include ARIA labels
<nav aria-label="Main navigation">
    <!-- Navigation content -->
</nav>

// Use semantic HTML
<article class="card">
    <header class="card-header">
        <h2>Title</h2>
    </header>
    <div class="card-body">
        <!-- Content -->
    </div>
</article>

// Provide alt text
<img src="<?php echo $image; ?>" 
     alt="<?php echo esc_attr($alt_text); ?>" 
     class="img-fluid">
```

### 2. Responsive Design

```php
// Use responsive utilities
<div class="d-none d-md-block">Desktop only</div>
<div class="d-block d-md-none">Mobile only</div>

// Responsive tables
<div class="table-responsive">
    <table class="table">
        <!-- Table content -->
    </table>
</div>

// Responsive embeds
<div class="ratio ratio-16x9">
    <iframe src="video-url"></iframe>
</div>
```

### 3. Performance

```php
// Lazy load images
<img data-src="<?php echo $image; ?>" 
     class="lazyload img-fluid" 
     alt="">

// Load components conditionally
<?php if (is_front_page()) : ?>
    <?php wpbc_component('hero'); ?>
<?php endif; ?>

// Minimize DOM elements
<?php if (has_post_thumbnail()) : // Only output if needed ?>
    <div class="post-thumbnail">
        <?php the_post_thumbnail(); ?>
    </div>
<?php endif; ?>
```

### 4. WordPress Integration

```php
// Use WordPress functions
wp_kses_post($content);  // Sanitize
esc_html($text);         // Escape HTML
esc_url($url);           // Escape URLs
esc_attr($attribute);    // Escape attributes

// Respect user capabilities
<?php if (current_user_can('edit_post', get_the_ID())) : ?>
    <a href="<?php echo get_edit_post_link(); ?>" class="btn btn-sm btn-outline-secondary">
        Edit
    </a>
<?php endif; ?>

// Use WordPress hooks
add_filter('wpbc_card_classes', function($classes) {
    $classes[] = 'shadow-sm';
    return $classes;
});
```

---

## Component Reference

### Quick Component List

| Component | Function | File |
|-----------|----------|------|
| Card | `wpbc_component('card', $args)` | `/templates/components/card.php` |
| Hero | `wpbc_component('hero', $args)` | `/templates/components/hero.php` |
| CTA | `wpbc_component('cta', $args)` | `/templates/components/cta.php` |
| Testimonial | `wpbc_component('testimonial', $args)` | `/templates/components/testimonial.php` |
| Team Member | `wpbc_component('team-member', $args)` | `/templates/components/team-member.php` |
| Pricing Table | `wpbc_component('pricing', $args)` | `/templates/components/pricing.php` |
| Timeline | `wpbc_component('timeline', $args)` | `/templates/components/timeline.php` |
| Stats Counter | `wpbc_component('stats', $args)` | `/templates/components/stats.php` |

---

## Next Steps

1. 📓 **[API Development](api-development.md)** - Create REST endpoints
2. 📗 **[Claude Integration](claude-integration.md)** - AI development
3. 📙 **[The Loop Mastery](the-loop.md)** - WordPress queries

---

**Build beautiful, responsive WordPress sites with Bootstrap components! 🎨**
