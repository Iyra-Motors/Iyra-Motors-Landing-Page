<?php defined('ABSPATH') || exit; ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<header class="header"><div class="wrap header-inner"><a href="<?php echo esc_url(home_url('/')); ?>" class="brand"><img src="<?php echo esc_url(get_template_directory_uri().'/images/iyra-logo.png'); ?>" alt="Iyra Motors"></a><a class="btn light" href="<?php echo esc_url(home_url('/')); ?>">Back to Iyra Motors</a></div></header>
<main class="wrap privacy-content"><?php while (have_posts()) : the_post(); ?><h1><?php the_title(); ?></h1><div style="margin-top:28px"><?php the_content(); ?></div><?php endwhile; ?></main>
<footer class="footer"><div class="wrap"><p>© <?php echo esc_html(wp_date('Y')); ?> Iyra Motors</p></div></footer><?php wp_footer(); ?></body></html>
