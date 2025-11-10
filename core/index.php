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
