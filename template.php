<?php global $product;

if (!$product->name) {
    return header('Location: /404');
}

//wp_title($product->name);
get_header();
wp_head();
?>
    <div style="display: flex; max-width: 100%; margin: 3em 1em">

        <?php if (!empty($product->image_url)) : ?>
            <div style="flex: 1">
                <img style="width: 100%" src="<?= $product->image_url ?>" alt="<?= $product->name ?>"/>
            </div>
        <?php endif; ?>
        <div style="flex: 2; padding: 0 1em">
            <h1><?= $product->name ?></h1>

            <?php if (!empty($product->brand)): ?>
                <h3><?= $product->brand ?></h3>
            <?php endif; ?>

            <?php if (!empty($product->description)): ?>
                <strong><?= __('Description', 'pifa') ?></strong>
                <p><?= $product->description ?></p>
            <?php endif; ?>

            <ul style="list-style: none; margin: 0">
                <?php if (!empty($product->sku)) : ?>
                    <li style="margin: 0"><?= __('SKU', 'pifa') ?>: <span class="sku"><?= $product->sku ?></span></li>
                <?php endif; ?>
                <?php if (!empty($product->ean)) : ?>
                    <li style="margin: 0"><?= __('EAN', 'pifa') ?>: <span class="ean"><?= $product->ean ?></span></li>
                <?php endif; ?>
            </ul>
        </div>

        <div style="flex: 1; padding: 0 1em">
            <?php if ($product->price < $product->regular_price) : ?>
                <p style="text-decoration: line-through; opacity: .25"><?php echo display_price($product->regular_price, $product->currency) ?></p>
            <?php endif; ?>
            <p style="font-size: 1.2em"><?php echo display_price($product->price, $product->currency) ?></p>
            <a class="pifa-button" rel="nofollow" href="<?= $product->product_url ?>" target="_blank"><?=get_option('pifa_external_buy_label'); ?></a>
        </div>
    </div>
<?php get_footer();