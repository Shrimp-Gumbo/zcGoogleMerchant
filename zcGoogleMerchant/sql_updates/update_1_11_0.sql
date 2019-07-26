UPDATE configuration SET configuration_title = 'Google Product Category Default', configuration_description = 'Enter a default Google product category from the <a href="http://www.google.com/support/merchants/bin/answer.py?answer=160081" target="_blank">Google Category Taxonomy</a> or leave blank (note: you can override this default setting by creating a Google Product Category attribute as per the documentation):' WHERE configuration_key = 'GOOGLE_PRODUCTS_DEFAULT_PRODUCT_CATEGORY' LIMIT 1;