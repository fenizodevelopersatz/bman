<div class="row" id="product-listing">
    <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
            <div class="col-md-4 col-sm-6 col-xs-6 m-b-24 mn-product-box pro-gl-content">
                <div class="mn-product-card">
                    <div class="mn-product-img">
                        <div class="mn-img">
                            <a href="<?= base_url('user/shop/product-view/' . $product['id']) ?>" class="image">
                                <img class="main-img" src="<?= base_url('assets/images/'.$product['product_image']) ?>" alt="<?= $product['name'] ?>">
                                <img class="hover-img" src="<?= base_url('assets/images/'.$product['product_image']) ?>" alt="<?= $product['name'] ?>">
                            </a>
                            <div class="mn-pro-loader"></div>
                             <div class="mn-options">
                                    <ul>
                                        <?php if ($this->session->userdata('userid')): ?>
                                            <li>
                                                <a href="javascript:void(0)" title="Wishlist" class="add-to-wishlist" data-product="<?= $product['id'] ?>">
                                                    <i class="ri-heart-line"></i>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0)" title="Add To Cart" class="add-to-cart" data-product="<?= $product['id'] ?>">
                                                    <i class="ri-shopping-cart-line"></i>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <a href="<?= base_url('user/in') ?>" title="Login to add to Wishlist">
                                                    <i class="ri-heart-line"></i>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="<?= base_url('user/in') ?>" title="Login to add to Cart">
                                                    <i class="ri-shopping-cart-line"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                        </div>
                    </div>
                    <div class="mn-product-detail">
                        <div class="cat">
                            <a href="#"><?= $product['category_name'] ?></a>
                        </div>
                        <h5><a href="#"><?= $product['name'] ?></a></h5>
                        <p class="mn-info"><?= word_limiter(strip_tags($product['description']), 15) ?></p>
                        <div class="mn-price">
                            <?php if ($product['offer_price'] > 0 && $product['offer_status'] == "1"): ?>
                            <div class="mn-price-new"><?php echo currency_info()->currency_symbol; ?> <?= $product['offer_price'] ?></div>
                            <?php else: ?>
                            <div class="mn-price-new"><?php echo currency_info()->currency_symbol; ?> <?= $product['price'] ?></div>
                            <?php endif; ?>
                            <?php if ($product['offer_price'] > 0 && $product['offer_status'] == "1"): ?>
                                <div class="mn-price-old"><?php echo currency_info()->currency_symbol; ?> <?= $product['price'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12"><p>No products found.</p></div>
    <?php endif; ?>
</div>



