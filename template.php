<?php global $product;

if (!$product->name) {
    return header('Location: /404');
}

//wp_title($product->name);
get_header();
wp_head();
?>
    <div class="product-page-item" itemtype="http://schema.org/Product" itemscope>
        <meta itemprop="name" content="<?= $product->name ?>" />
        <?php if (!empty($product->image_url)) : ?>
            <link itemprop="image" href="<?= $product->image_url ?>>" />
            <div class="product-page-image">
                <img style="width: 100%" src="<?= $product->image_url ?>" alt="<?= $product->name ?>"/>
            </div>
        <?php endif; ?>
        <div class="product-page-content">
            <h1><?= $product->name ?></h1>

            <?php if (!empty($product->brand)) : ?>
                <div itemprop="brand" itemtype="http://schema.org/Brand" itemscope>
                    <meta itemprop="name" content="<?= $product->brand ?>" />
                </div>
                <h3><?= $product->brand ?></h3>
            <?php endif; ?>

            <?php if (!empty($product->description)) : ?>
                <meta itemprop="description" content="<?= $product->description ?>" />
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

        <div class="product-page-triggers" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <?php if ($product->price < $product->regular_price) : ?>
                <p itemprop="price" style="text-decoration: line-through; opacity: .25"><?php echo display_price($product->regular_price, $product->currency) ?></p>
                <p style="font-size: 1.2em"><?php echo display_price($product->price, $product->currency) ?></p>
            <?php else : ?>
                <p itemprop="price" style="font-size: 1.2em"><?php echo display_price($product->price, $product->currency) ?></p>
            <?php endif; ?>
            <meta itemprop="priceCurrency" content="<?= $product->currency ?>">
            <a class="pifa-button" rel="nofollow" href="<?= $product->product_url ?>" target="_blank"><?=get_option('pifa_external_buy_label'); ?></a>
        </div>
    </div>
<?php get_footer();