UPDATE configuration SET set_function = 'google_cfg_pull_down_currencies(' WHERE configuration_key = 'GOOGLE_PRODUCTS_CURRENCY' LIMIT 1;
UPDATE configuration SET set_function = 'google_cfg_pull_down_languages_list(', configuration_description = 'Set your feed language (required):', configuration_value = 1 WHERE configuration_key = 'GOOGLE_PRODUCTS_LANGUAGE' LIMIT 1;
UPDATE configuration SET set_function = 'google_cfg_pull_down_country_list(' WHERE configuration_key = 'GOOGLE_PRODUCTS_SHIPPING_COUNTRY' LIMIT 1;