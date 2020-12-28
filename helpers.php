<?php

function display_price($price, $currency, $igoreSchema = false)
{
    if ($igoreSchema) {
        return $price . ' ' . $currency;
    }

    if ($currency === 'SEK') {
        return '<span itemprop="price" content="' . $price . '">' .
            number_format($price, 0, '', ' ') . '</span> ' .
            '<span itemprop="priceCurrency" content="' . $currency . '">kr</span>';
    } else if ($currency === 'USD') {
        return '<span itemprop="priceCurrency" content="USD" >$</span><span itemprop="price" content="' . $price . '">'
            . number_format($price, 2, '.', ',') . '</span>';
    } else if ($currency === 'EUR') {
        return '<span itemprop="priceCurrency" content="EUR">€</span><span itemprop="price" content="' . $price . '">' . number_format($price, 2, '.', ',') . '</span>';
    }

    return '<span itemprop="price" content="' . $price . '">' . $price . '</span> <span itemprop="priceCurrency" content="' . $currency . '">' . $currency . '</span>';
}