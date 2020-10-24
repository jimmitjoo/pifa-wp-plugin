<?php

function display_price($price, $currency) {
    if ($currency === 'SEK') {
        return number_format($price, 0, '', ' ') . ' kr';
    } else if ($currency === 'USD') {
        return '$' . number_format($price, 2, '.', ',');
    } else if ($currency === 'EUR') {
        return number_format($price, 2, '.', ',') . '€';
    }

    return $price . ' ' . $currency;
}