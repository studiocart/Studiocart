<?php
/**
 * The Template for displaying an archive item
 * This template can be overridden by copying it to <active-theme-folder>/studiocart/archive/item.php.
 */
?>

<li class="cards__item">
    <a href="<?php the_permalink(); ?>" class="card">
        <?php if(has_post_thumbnail()): ?>
        <div class="card__image">
            <?php the_post_thumbnail( 'medium_large' ); ?>
        </div>
        <?php endif; ?>
        <div class="card__content">
            <div class="card__title"><?php the_title(); ?></div>
            <div class="card__text"><?php the_excerpt(); ?></div>
            <span class="btn btn--block card__btn"><?php esc_html_e($attr['button_text']); ?></span>
        </div>
    </a>
</li>