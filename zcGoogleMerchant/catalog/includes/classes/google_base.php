<?php
/**
 * @package google base feeder
 * @copyright Copyright 2007-2008 Numinix Technology http://www.numinix.com
 * @copyright Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: google_base.php 64 2011-08-31 16:07:57Z numinix $
 * @author Numinix Technology
 */
 
  class google_base {
    
    function additional_images($products_image, $products_id) {
      if ($products_image != '') {
        $images_array = array();
        if (is_array($this->additional_images_array[$products_id])) {
          $images_array = $this->additional_images_array[$products_id];  
        } else {
          // prepare image name
          $products_image_extension = substr($products_image, strrpos($products_image, '.'));
          $products_image_base = str_replace($products_image_extension, '', $products_image);

          // if in a subdirectory
          if (strrpos($products_image, '/')) {
            $products_image_match = substr($products_image, strrpos($products_image, '/')+1);
            $products_image_match = str_replace($products_image_extension, '', $products_image_match) . '_';
            $products_image_base = $products_image_match;
          }

          $products_image_directory = str_replace($products_image, '', substr($products_image, strrpos($products_image, '/')));
          if ($products_image_directory != '') {
            $products_image_directory = DIR_WS_IMAGES . str_replace($products_image_directory, '', $products_image) . "/";
          } else {
            $products_image_directory = DIR_WS_IMAGES;
          }

          // Check for additional matching images
          //$file_extension = $products_image_extension;
          //$products_image_match_array = array();
          if (is_array($this->image_files[$products_image_directory])) {
            $image_files = $this->image_files[$products_image_directory];
          } else {
            $image_files = scandir($products_image_directory);
            $this->image_files[$products_image_directory] = $image_files;
          }
          //print_r($this->image_files);
          //die();
          if (is_array($image_files) && sizeof($image_files) > 0) {
            foreach($this->image_files[$products_image_directory] as $file) {
              $file_extension = substr($file, strrpos($file, '.'));
              $file_base = str_replace($file_extension, '', $file);
              // skip the main image and make sure the base and extension match the main image
              if (($file != $products_image) && (preg_match("/" . $products_image_base . "/i", $file) == 1)) {
                $images_array[] = $this->google_base_image_url(($products_image_directory != '' ? str_replace(DIR_WS_IMAGES, '', $products_image_directory) : ''). $file);
                if (count($images_array) >= 9) break; // Google Supports up to 10 images
              }
            }
          }
          $this->additional_images_array[$products_id] = $images_array;
        }
        return $images_array;
      } else {
        // default
        return false;
      }
    }   
   
    // writes out the code into the feed file
    function google_base_fwrite($output='', $mode, $products_id = '') { // added products id for debugging
      global $outfile;
      $output = implode("\n", $output);
      //if(strtolower(CHARSET) != 'utf-8') {
        //$output = utf8_encode($output);
      //}
      //$fp = fopen($outfile, $mode);
      //$retval = fwrite($fp, $output, GOOGLE_PRODUCTS_OUTPUT_BUFFER_MAXSIZE);
      if ($mode == 'a') $mode = 'FILE_APPEND';
      file_put_contents($outfile, $output, $mode);
      //return $retval;
    }
    
    // gets the Google Base Feeder version number from the Module Versions file
    function google_base_version() {
      return trim(GOOGLE_PRODUCTS_VERSION);
    }  
    
    // trims the value of each element of an array
    function trim_array($x) {
      if (is_array($x)) {
         return array_map('trim_array', $x);
      } else {
       return trim($x);
      }
    } 

    // determines if the feed should be generated
    function get_feed($feed_parameter) {
      switch($feed_parameter) {
        case 'fy':
          $feed = 'yes';
          break;
        case 'fn':
          $feed = 'no';
          break;
        default:
          $feed = 'no';
          break;
      }
      return $feed;
    }

    // determines if the feed should be automatically uploaded to Google Base
    function get_upload($upload_parameter) {
      switch($upload_parameter) {
        case 'uy':
          $upload = 'yes';
          break;
        case 'un':
          $upload = 'no';
          break;
        default:
          $upload = 'no';
          break;
      }
      return $upload;
    }
    
    // returns the type of feed
    function get_type($type_parameter) {
      switch($type_parameter) {
        case 'tp':
          $type = 'products';
          break;
        case 'td':
          $type = 'documents';
          break;
        case 'tn':
          $type = 'news';
          break;
        default:
          $type = 'products';
          break;
      }
      return $type;
    }
    
    // performs a set of functions to see if a product is valid
    function check_product($products_id) {
      if ($this->included_categories_check(GOOGLE_PRODUCTS_POS_CATEGORIES, $products_id) && !$this->excluded_categories_check(GOOGLE_PRODUCTS_NEG_CATEGORIES, $products_id) && $this->included_manufacturers_check(GOOGLE_PRODUCTS_POS_MANUFACTURERS, $products_id) && !$this->excluded_manufacturers_check(GOOGLE_PRODUCTS_NEG_MANUFACTURERS, $products_id)) {
        return true;
      } else {
        return false;
      }
    }
    
    // check to see if a product is inside an included category
    function included_categories_check($categories_list, $products_id) {
      if ($categories_list == '') {
        return true;
      } else {
        $categories_array = explode(',', $categories_list);
        $match = false;
        foreach($categories_array as $category_id) {
          if (zen_product_in_category($products_id, $category_id)) {
            $match = true;
            break;
          }
        }
        if ($match == true) {
          return true;
        } else {
          return false;
        }
      }
    }
    
    // check to see if a product is inside an excluded category
    function excluded_categories_check($categories_list, $products_id) {
      if ($categories_list == '') {
        return false;
      } else {
        $categories_array = explode(',', $categories_list);
        $match = false;
        foreach($categories_array as $category_id) {
          if (zen_product_in_category($products_id, $category_id)) {
            $match = true;
            break;
          }
        }
        if ($match == true) {
          return true;
        } else {
          return false;
        }
      }
    }
    
    // check to see if a product is from an included manufacturer
    function included_manufacturers_check($manufacturers_list, $products_id) {
      if ($manufacturers_list == '') {
        return true;
      } else {
        $manufacturers_array = explode(',', $manufacturers_list);
        $products_manufacturers_id = zen_get_products_manufacturers_id($products_id);
        if (in_array($products_manufacturers_id, $manufacturers_array)) {
          return true;
        } else {
          return false;
        }
      }
    }
    
    function excluded_manufacturers_check($manufacturers_list, $products_id) {
      if ($manufacturers_list == '') {
        return false;
      } else {
        $manufacturers_array = explode(',', $manufacturers_list);
        $products_manufacturers_id = zen_get_products_manufacturers_id($products_id);
        if (in_array($products_manufacturers_id, $manufacturers_array)) {
          return true;
        } else {
          return false;
        }
      }
    }
    
    function google_base_get_category($products_id) {
      global $db;
      
      // get the master_categories_id
      $master_categories_id = $db->Execute("SELECT master_categories_id FROM " . TABLE_PRODUCTS . " WHERE products_id = " . $products_id . " LIMIT 1;");
      $master_categories_id = $master_categories_id->fields['master_categories_id'];
      
      // build the cPath
      $cPath_array = zen_generate_category_path($master_categories_id);
      $category_names = array();
      $cPath = array();
      $cPath_array[0] = array_reverse($cPath_array[0]);
      foreach ($cPath_array[0] as $category) {
        $category_names[] = zen_get_category_name($category['id'], (int)GOOGLE_PRODUCTS_LANGUAGE); // have to use this function just in case of a different language
        $cPath[] = $category['id'];
      }
      return array($category_names, $cPath);  
    }
    
    // returns an array containing the category name and cPath
    /*
    function google_base_get_category($products_id) {
      global $categories_array, $db;
      static $p2c;
      if(!$p2c) {
        $q = $db->Execute("SELECT *
                          FROM " . TABLE_PRODUCTS_TO_CATEGORIES);
        while (!$q->EOF) {
          if(!isset($p2c[$q->fields['products_id']]))
            $p2c[$q->fields['products_id']] = $q->fields['categories_id'];
          $q->MoveNext();
        }
      }
      if(isset($p2c[$products_id])) {
        $retval = $categories_array[$p2c[$products_id]]['name'];
        $cPath = $categories_array[$p2c[$products_id]]['cPath'];
      } else {
        $cPath = $retval =  "";
      }
      return array($retval, $cPath);
    }
    */
    
    // builds the category tree
    function google_base_category_tree($id_parent=0, $cPath='', $cName='', $cats=array()){
      global $db, $languages;
      $cat = $db->Execute("SELECT c.categories_id, c.parent_id, cd.categories_name
                           FROM " . TABLE_CATEGORIES . " c
                             LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd on c.categories_id = cd.categories_id
                           WHERE c.parent_id = '" . (int)$id_parent . "'
                           AND cd.language_id='" . (int)$languages->fields['languages_id'] . "'
                           AND c.categories_status= '1'",
                           '', false, 150);
      while (!$cat->EOF) {
        $cats[$cat->fields['categories_id']]['name'] = (zen_not_null($cName) ? $cName . ', ' : '') . trim($cat->fields['categories_name']); // previously used zen_froogle_sanita instead of trim
        $cats[$cat->fields['categories_id']]['cPath'] = (zen_not_null($cPath) ? $cPath . ',' : '') . $cat->fields['categories_id'];
        if (zen_has_category_subcategories($cat->fields['categories_id'])) {
          $cats = $this->google_base_category_tree($cat->fields['categories_id'], $cats[$cat->fields['categories_id']]['cPath'], $cats[$cat->fields['categories_id']]['name'], $cats);
        }
        $cat->MoveNext();
      }
      return $cats;
    }
    
    // create a product that doesn't use stock by attributes
    function create_regular_product($products, $dom) {
      global $id, $price, $tax_rate, $productstitle, $percategory, $freerules;
      $item = $dom->createElement('item');
      $products_title = $dom->createElement('title');
      $products_title->appendChild($dom->createCDATASection($productstitle));
      $item->appendChild($products_title);
      $iD = $dom->createElement('g:id');
      $iD->appendChild($dom->createCDATASection($id));
      $item->appendChild($iD);      
      
		$item->appendChild($dom->createElement('g:price', number_format($price, 2, '.', '') . ' ' . GOOGLE_PRODUCTS_CURRENCY));  
      if (GOOGLE_PRODUCTS_TAX_DISPLAY == 'true' && GOOGLE_PRODUCTS_TAX_COUNTRY == 'US' && $tax_rate != '') {
        $tax = $dom->createElement('g:tax');
        $tax->appendChild($dom->createElement('g:country', GOOGLE_PRODUCTS_TAX_COUNTRY));
        if (GOOGLE_PRODUCTS_TAX_REGION != '') {
          $tax->appendChild($dom->createElement('g:region', GOOGLE_PRODUCTS_TAX_REGION));
        }
        if (GOOGLE_PRODUCTS_TAX_SHIPPING == 'y') {
          $tax->appendChild($dom->createElement('g:tax_ship', GOOGLE_PRODUCTS_TAX_SHIPPING));
        }
        $tax->appendChild($dom->createElement('g:rate', $tax_rate));
        $item->appendChild($tax);
      }
      if (STOCK_CHECK == 'true') {
        if ($products->fields['products_quantity'] > 0) {
          $item->appendChild($dom->createElement('g:availability', 'in stock'));
        } else {
          // are back orders allowed?
          if (STOCK_ALLOW_CHECKOUT == 'true') {
            //"available for order" is not longer an option changing to "preorder"
            $item->appendChild($dom->createElement('g:availability', 'preorder'));
		  
            //if ($products->fields['products_date_available'] != 'NULL') {
            //  $item->appendChild($dom->createElement('g:availability', 'available for order'));
            //} else {
            //  $item->appendChild($dom->createElement('g:availability', 'preorder'));
            //}
          } else {
            $item->appendChild($dom->createElement('g:availability', 'out of stock'));
          }
        }
      } else {
        $item->appendChild($dom->createElement('g:availability', 'in stock'));                  
      }
      if(GOOGLE_PRODUCTS_WEIGHT == 'true' && $products->fields['products_weight'] != '') {
        $item->appendChild($dom->createElement('g:shipping_weight', $products->fields['products_weight'] . ' ' . str_replace(array('pounds', 'kilograms'), array('lb', 'kg'), GOOGLE_PRODUCTS_UNITS)));
      } 
      if (defined('GOOGLE_PRODUCTS_SHIPPING_METHOD') && (GOOGLE_PRODUCTS_SHIPPING_METHOD != '') && (GOOGLE_PRODUCTS_SHIPPING_METHOD != 'none')) {   
        $shipping_rate = $this->shipping_rate(GOOGLE_PRODUCTS_SHIPPING_METHOD, $percategory, $freerules, GOOGLE_PRODUCTS_RATE_ZONE, $products->fields['products_weight'], $price, $products->fields['products_id']);
        if ((float)$shipping_rate >= 0) {
          $shipping = $dom->createElement('g:shipping');
          if (GOOGLE_PRODUCTS_SHIPPING_COUNTRY != '') {
            $shipping->appendChild($dom->createElement('g:country', $this->get_countries_iso_code_2(GOOGLE_PRODUCTS_SHIPPING_COUNTRY)));
          }
          
          if (GOOGLE_PRODUCTS_SHIPPING_REGION != '') {
            $shipping->appendChild($dom->createElement('g:region', GOOGLE_PRODUCTS_SHIPPING_REGION));
          }
          if (GOOGLE_PRODUCTS_SHIPPING_SERVICE != '') {
            $shipping->appendChild($dom->createElement('g:service', GOOGLE_PRODUCTS_SHIPPING_SERVICE));
          }
          $shipping->appendChild($dom->createElement('g:price', (float)$shipping_rate));
          $item->appendChild($shipping);
        }
      }
                       
      return $item;
    }
    
    // takes already created $item and adds universal attributes from $products
    function universal_attributes($products, $item, $dom) {
      global $link, $product_type, $payments_accepted, $google_product_category_check, $default_google_product_category, $products_description;
      if ($products->fields['manufacturers_name'] != '') {
        $manufacturers_name = $dom->createElement('g:brand');
        $manufacturers_name->appendChild($dom->createCDATASection($this->google_base_xml_sanitizer($products->fields['manufacturers_name'])));
        $item->appendChild($manufacturers_name);
      }
      if (GOOGLE_PRODUCTS_PRODUCT_CONDITION == 'true' && $products->fields['products_condition'] != '') {
        $item->appendChild($dom->createElement('g:condition', $products->fields['products_condition']));
      } else {
        $item->appendChild($dom->createElement('g:condition', GOOGLE_PRODUCTS_CONDITION));
      }
      
      if ($product_type) {
        $item->appendChild($dom->createElement('g:product_type', $product_type));
      }
      if ($products->fields['products_image'] != '') {
        $item->appendChild($dom->createElement('g:image_link', $this->google_base_image_url($products->fields['products_image'])));
        $additional_images = $this->additional_images($products->fields['products_image'], $products->fields['products_id']);
        if (is_array($additional_images) && sizeof($additional_images) > 0) {
          $count = 0;
          foreach ($additional_images as $additional_image) {
            $count++;
            $item->appendChild($dom->createElement('g:additional_image_link', $additional_image));
            if ($count == 9) break; // max 10 images including main image 
          }
        }
      }
	
      //new required field, for now set to the expiration date	    
      $item->appendChild($dom->createElement('g:priceValidUntil', $this->google_base_expiration_date($products->fields['base_date'])));
      // only include if less then 30 days as 30 is the max and leaving blank will default to the max
      if (GOOGLE_PRODUCTS_EXPIRATION_DAYS <= 29) {
        $item->appendChild($dom->createElement('g:expiration_date', $this->google_base_expiration_date($products->fields['base_date'])));
      }
      $item->appendChild($dom->createElement('link', $link));
      if ($products->fields['products_model'] != '') {
        $mpn = $dom->createElement('g:mpn');
        $mpn->appendChild($dom->createCDATASection($this->google_base_xml_sanitizer($products->fields['products_model'])));
        $item->appendChild($mpn);
      }
      if (GOOGLE_PRODUCTS_ASA_UPC == 'true') {
        if ($products->fields['products_upc'] != '') {
          $upc = $dom->createElement('g:upc');
          $upc->appendChild($dom->createCDATASection($this->google_base_xml_sanitizer($products->fields['products_upc'])));
          $item->appendChild($upc);
        } elseif ($products->fields['products_isbn'] != '') {
          $isbn = $dom->createElement('g:isbn');
          $isbn->appendChild($dom->createCDATASection($this->google_base_xml_sanitizer($products->fields['products_isbn'])));
          $item->appendChild($isbn);
        } elseif ($products->fields['products_ean'] != '') {
          $ean = $dom->createElement('g:ean');
          $ean->appendChild($dom->createCDATASection($this->google_base_xml_sanitizer($products->fields['products_ean'])));
          $item->appendChild($ean);                  
        }
      }
      if (GOOGLE_PRODUCTS_CURRENCY_DISPLAY == 'true') {
        $item->appendChild($dom->createElement('g:currency', GOOGLE_PRODUCTS_CURRENCY));
      }
      if(GOOGLE_PRODUCTS_PICKUP != 'do not display') {
        $item->appendChild($dom->createElement('g:pickup', GOOGLE_PRODUCTS_PICKUP));
      }
      if (defined('GOOGLE_PRODUCTS_PAYMENT_METHODS') && GOOGLE_PRODUCTS_PAYMENT_METHODS != '') { 
        foreach($payments_accepted as $payment_accepted) {
          $item->appendChild($dom->createElement('g:payment_accepted', trim($payment_accepted)));
        }
      }
      if (defined('GOOGLE_PRODUCTS_PAYMENT_NOTES') && GOOGLE_PRODUCTS_PAYMENT_NOTES != '') {
        $item->appendChild($dom->createElement('g:payment_notes', trim(GOOGLE_PRODUCTS_PAYMENT_NOTES)));
      }
      $productsDescription = $dom->createElement('description');
      $productsDescription->appendChild($dom->createCDATASection(substr($products_description, 0, 9988))); // 10000 - 12 to account for cData
      $item->appendChild($productsDescription);
      if ($google_product_category_check == false && GOOGLE_PRODUCTS_DEFAULT_PRODUCT_CATEGORY != '') {
        $google_product_category = $dom->createElement('g:google_product_category');
        $google_product_category->appendChild($dom->createCDATASection($default_google_product_category));
        $item->appendChild($google_product_category);
      }     
      return $item;
    }
    
    function google_base_sanita($str, $rt=false) {
      //global $products;
      $str = str_replace(array("\r\n", "\r", "\n", "&nbsp;"), ' ', $str);
      $str = str_replace('’', "'", $str);
      $str = strip_tags($str);
      //$charset = 'UTF-8';
      //if (defined(CHARSET)) {
        //$charset = strtoupper(CHARSET);
      //}
      $str = html_entity_decode($str, ENT_QUOTES);//, $charset);
      //$str = html_entity_decode($str, ENT_QUOTES, $charset);
      //$str = htmlspecialchars($str, ENT_QUOTES, '', false);
      //$str = htmlentities($str, ENT_QUOTES, $charset, false); 
      return $str;
    }
             
    function google_base_xml_sanitizer($str, $products_id = '') { // products id added for debugging purposes
      $str = $this->google_base_sanita($str);
      if (GOOGLE_PRODUCTS_XML_SANITIZATION == 'true') {
        $str = $this->transcribe_cp1252_to_latin1($str); // transcribe windows characters
        $strout = null;

        for ($i = 0; $i < strlen($str); $i++) {
          $ord = ord($str[$i]);
          if (($ord > 0 && $ord < 32) || ($ord >= 127)) {
            $strout .= "&#{$ord};";
          }
          else {
            switch ($str[$i]) {
              case '<':
                $strout .= '&lt;';
                break;
              case '>':
                $strout .= '&gt;';
                break;
              //case '&':
                //$strout .= '&amp;';
                //break;
              case '"':
                $strout .= '&quot;';
                break;
              default:
                $strout .= $str[$i];
            }
          }
        }
        $str = null;
        return $strout;
      } else {
        return $str;
      }
    }
    
    function transcribe_cp1252_to_latin1($cp1252) {
      return strtr(
        $cp1252,
        array(
          "\x80" => "e",  "\x81" => " ",    "\x82" => "'", "\x83" => 'f',
          "\x84" => '"',  "\x85" => "...",  "\x86" => "+", "\x87" => "#",
          "\x88" => "^",  "\x89" => "0/00", "\x8A" => "S", "\x8B" => "<",
          "\x8C" => "OE", "\x8D" => " ",    "\x8E" => "Z", "\x8F" => " ",
          "\x90" => " ",  "\x91" => "`",    "\x92" => "'", "\x93" => '"',
          "\x94" => '"',  "\x95" => "*",    "\x96" => "-", "\x97" => "--",
          "\x98" => "~",  "\x99" => "(TM)", "\x9A" => "s", "\x9B" => ">",
          "\x9C" => "oe", "\x9D" => " ",    "\x9E" => "z", "\x9F" => "Y"
          )
      );
    }
    
    // creates the url for the products_image
    function google_base_image_url($products_image) {
      if($products_image == "") return "";
      if (defined('GOOGLE_PRODUCTS_ALTERNATE_IMAGE_URL') && GOOGLE_PRODUCTS_ALTERNATE_IMAGE_URL != '') {
        if (strpos(GOOGLE_PRODUCTS_ALTERNATE_IMAGE_URL, HTTP_SERVER . '/' . DIR_WS_IMAGES) !== false) {
          $products_image = substr(GOOGLE_PRODUCTS_ALTERNATE_IMAGE_URL, strlen(HTTP_SERVER . '/' . DIR_WS_IMAGES)) . $products_image;
        } else {
          return GOOGLE_PRODUCTS_ALTERNATE_IMAGE_URL . rawurlencode($products_image);
        } 
      }
      $products_image_extention = substr($products_image, strrpos($products_image, '.'));
      $products_image_base = preg_replace("/" . $products_image_extention . "/", '', $products_image);
      $products_image_medium = $products_image_base . IMAGE_SUFFIX_MEDIUM . $products_image_extention;
      $products_image_large = $products_image_base . IMAGE_SUFFIX_LARGE . $products_image_extention;
      
      // check for a large image else use medium else use small
      if (!file_exists(DIR_WS_IMAGES . 'large/' . $products_image_large)) {
        if (!file_exists(DIR_WS_IMAGES . 'medium/' . $products_image_medium)) {
          $products_image_large = DIR_WS_IMAGES . $products_image;
        } else {
          $products_image_large = DIR_WS_IMAGES . 'medium/' . $products_image_medium;
        }
      } else {
        $products_image_large = DIR_WS_IMAGES . 'large/' . $products_image_large;
      }
      if ((function_exists('handle_image')) && (GOOGLE_PRODUCTS_IMAGE_HANDLER == 'true')) {
        $image_ih = handle_image($products_image_large, '', LARGE_IMAGE_MAX_WIDTH, LARGE_IMAGE_MAX_HEIGHT, '');
        $retval = (HTTP_SERVER . DIR_WS_CATALOG . $image_ih[0]);
      } else {
        $retval = (HTTP_SERVER . DIR_WS_CATALOG . rawurlencode($products_image_large));
      }   
      $retval = str_replace('%2F', '/', $retval);
      $retval = str_replace('%28', '(', $retval);
	  return str_replace('%29', ')', $retval);
    }
    
    // creates the url for a News and Articles Manager article
    function google_base_news_link($article_id) {
      $link = zen_href_link(FILENAME_NEWS_ARTICLE, 'article_id=' . (int)$article_id . $product_url_add, 'NONSSL', false);
      return $link;
    }
    
    function google_base_expiration_date($base_date) {
      if(GOOGLE_PRODUCTS_EXPIRATION_BASE == 'now')
        $expiration_date = time();
      else
        $expiration_date = strtotime($base_date);
      $expiration_date += GOOGLE_PRODUCTS_EXPIRATION_DAYS*24*60*60;
      $retval = (date('Y-m-d', $expiration_date));
      return $retval;
    }
    
// SHIPPING FUNCTIONS //

  function get_countries_iso_code_2($countries_id) {
    global $db;

    $countries_query = "select countries_iso_code_2
                        from " . TABLE_COUNTRIES . "
                        where countries_id = '" . $countries_id . "'
                        limit 1";
    $countries = $db->Execute($countries_query);
    $countries_iso_code_2 = $countries->fields['countries_iso_code_2'];
    return $countries_iso_code_2;
  }

  function shipping_rate($method, $percategory='', $freerules='', $table_zone = '', $products_weight = '', $products_price = '', $products_id = '') {
    global $currencies, $percategory, $freerules;
    // skip the calculation for products that are always free shipping
    if (zen_get_product_is_always_free_shipping($products_id)) {
      $rate = 0;
    } else {
      switch ($method) {
        case "zones table rate":
          $rate = $this->numinix_zones_table_rate($products_weight, $table_zone);
          break;
        case "flat rate":
          $rate = MODULE_SHIPPING_FLAT_COST;
          break;
        case "per item":
          $rate = MODULE_SHIPPING_ITEM_COST + MODULE_SHIPPING_ITEM_HANDLING;
          break;
        case "per weight unit":
          $rate = (MODULE_SHIPPING_PERWEIGHTUNIT_COST * $products_weight) + MODULE_SHIPPING_PERWEIGHTUNIT_HANDLING;
          break;
        case "table rate":
          $rate = $this->numinix_table_rate($products_weight, $products_price);
          break;
        case "zones":
          $rate = $this->numinix_zones_rate($products_weight, $products_price, $table_zone);
          break;
        case "percategory":
          if (is_object($percategory)) {
            $products_array = array();
            $products_array[0]['id'] = $products_id;
            $rate = $percategory->calculation($products_array, $table_zone, (int)MODULE_SHIPPING_PERCATEGORY_GROUPS);
          }
          break;
        case "free shipping":
          $rate = 0;
          break;
        case "free rules shipping":
          if (is_object($freerules)) {
            if ($freerules->test($products_id)) {
              $rate = 0;
            } else {
              $rate = -1;
            }
          }
          break;
        // this shouldn't be possible
        case "none":
          $rate = -1;
          break; 
        default:
          $rate = -1;
          break;
      }
    }
    if ($rate >= 0 && GOOGLE_PRODUCTS_CURRENCY != '' && $currencies->get_value(GOOGLE_PRODUCTS_CURRENCY) != '') {
       $rate = $currencies->value($rate, true, GOOGLE_PRODUCTS_CURRENCY, $currencies->get_value(GOOGLE_PRODUCTS_CURRENCY)); 
    }
    return $rate;
  }
  
  function numinix_table_rate($products_weight, $products_price) {
    global $currencies;
    
     switch (MODULE_SHIPPING_TABLE_MODE) {
      case ('price'):
        $order_total = $products_price;
        break;
      case ('weight'):
        $order_total = $products_weight;
        break;
      case ('item'):
        $order_total = 1;
        break;
    }

    $table_cost = $this->google_multi_explode(',', ':', MODULE_SHIPPING_TABLE_COST);
    $size = sizeof($table_cost);
    for ($i=0, $n=$size; $i<$n; $i+=2) {
      if (round($order_total,9) <= $table_cost[$i]) {
        if (strstr($table_cost[$i+1], '%')) {
          $shipping = ($table_cost[$i+1]/100) * $products_price;
        } else {
          $shipping = $table_cost[$i+1];
        }
        break;
      }
    }
    $shipping = $shipping + MODULE_SHIPPING_TABLE_HANDLING;
    return $shipping;
  }
    
  function numinix_zones_table_rate($products_weight, $table_zone) {
    global $currencies;
    
    switch (MODULE_SHIPPING_ZONETABLE_MODE) {
      case ('price'):
        $order_total = $products_price;
        break;
      case ('weight'):
        $order_total = $products_weight;
        break;
      case ('item'):
        $order_total = 1;
        break;
    }
    
    $table_cost = $this->google_multi_explode(',', ':', constant('MODULE_SHIPPING_ZONETABLE_COST_' . $table_zone));
    $size = sizeof($table_cost);
    for ($i=0, $n=$size; $i<$n; $i+=2) {
      if (round($order_total,9) <= $table_cost[$i]) {
        $shipping = $table_cost[$i+1];
        break;
      }
    }
    $shipping = $shipping + constant('MODULE_SHIPPING_ZONETABLE_HANDLING_' . $table_zone);
    return $shipping;
  }
  
  function numinix_zones_rate($products_weight, $products_price, $table_zone) {
    global $currencies;
    
    switch (MODULE_SHIPPING_ZONES_METHOD) {
      case ('Price'):
        $order_total = $products_price;
        break;
      case ('Weight'):
        $order_total = $products_weight;
        break;
      case ('Item'):
        $order_total = 1;
        break;
    }
    
    $zones_cost = constant('MODULE_SHIPPING_ZONES_COST_' . $table_zone);
    $zones_table = $this->google_multi_explode(',', ':', $zones_cost);
    $size = sizeof($zones_table);
    for ($i=0; $i<$size; $i+=2) {
      if (round($order_total,9) <= $zones_table[$i]) {
        if (strstr($zones_table[$i+1], '%')) {
          $shipping = ($zones_table[$i+1]/100) * $products_price;
        } else {
          $shipping = $zones_table[$i+1];
        }
         break;
      }
    }
    $shipping = $shipping + constant('MODULE_SHIPPING_ZONES_HANDLING_' . $table_zone);
    return $shipping;
  }
  
  function google_multi_explode($delim1, $delim2, $string) {
  	$new_data = array();
  	$data = explode($delim1, $string);
  	foreach ($data as $key => $value) {
  	  $new_data = array_merge($new_data, explode($delim2, $value));
	}
	return $new_data;
  }
// PRICE FUNCTIONS

// Actual Price Retail
// Specials and Tax Included
  function google_get_products_actual_price($products_id) {
    global $db, $currencies;
    $product_check = $db->Execute("select products_tax_class_id, products_price, products_priced_by_attribute, product_is_free, product_is_call from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'" . " limit 1");

    $show_display_price = '';
    $display_normal_price = $this->google_get_products_base_price($products_id);
    //echo $display_normal_price . '<br />';
    $display_special_price = $this->google_get_products_special_price($products_id, $display_normal_price, true);
    //echo $display_special_price . '<br />';
    $display_sale_price = $this->google_get_products_special_price($products_id, $display_normal_price, false);
    //echo $display_sale_price . '<br />';
    $products_actual_price = $display_normal_price;

    if ($display_special_price) {
      $products_actual_price = $display_special_price;
    }
    if ($display_sale_price) {
      $products_actual_price = $display_sale_price;
    }

    // If Free, Show it
    if ($product_check->fields['product_is_free'] == '1') {
      $products_actual_price = 0;
    }
    //die();

    return $products_actual_price;
  }

// computes products_price + option groups lowest attributes price of each group when on
  function google_get_products_base_price($products_id) {
    global $db;
      $product_check = $db->Execute("select products_price, products_priced_by_attribute from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");

// is there a products_price to add to attributes
      $products_price = $product_check->fields['products_price'];

      // do not select display only attributes and attributes_price_base_included is true
      $product_att_query = $db->Execute("select options_id, price_prefix, options_values_price, attributes_display_only, attributes_price_base_included from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . (int)$products_id . "' and attributes_display_only != '1' and attributes_price_base_included='1' and options_values_price > 0". " order by options_id, price_prefix, options_values_price");
      //echo $products_id . ' ';
      //print_r($product_att_query);
      //die();
      $the_options_id= 'x';
      $the_base_price= 0;
// add attributes price to price
      if ($product_check->fields['products_priced_by_attribute'] == '1' and $product_att_query->RecordCount() >= 1) {
        while (!$product_att_query->EOF) {
          if ( $the_options_id != $product_att_query->fields['options_id']) {
            $the_options_id = $product_att_query->fields['options_id'];
            $the_base_price += $product_att_query->fields['options_values_price'];
            //echo $product_att_query->fields['options_values_price'];
            //die();
          }
          $product_att_query->MoveNext();
        }

        $the_base_price = $products_price + $the_base_price;
      } else {
        $the_base_price = $products_price;
      }
      //echo $the_base_price;
      return $the_base_price;
  }
  
//get specials price or sale price
  function google_get_products_special_price($product_id, $product_price, $specials_price_only=false) {
    global $db;
    $product = $db->Execute("select products_price, products_model, products_priced_by_attribute from " . TABLE_PRODUCTS . " where products_id = '" . (int)$product_id . "'");

    //if ($product->RecordCount() > 0) {
//      $product_price = $product->fields['products_price'];
      //$product_price = zen_get_products_base_price($product_id);
    //} else {
      //return false;
    //}

    $specials = $db->Execute("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$product_id . "' and status='1'");
    if ($specials->RecordCount() > 0) {
//      if ($product->fields['products_priced_by_attribute'] == 1) {
        $special_price = $specials->fields['specials_new_products_price'];
    } else {
      $special_price = false;
    }

    if(substr($product->fields['products_model'], 0, 4) == 'GIFT') {    //Never apply a salededuction to Ian Wilson's Giftvouchers
      if (zen_not_null($special_price)) {
        return $special_price;
      } else {
        return false;
      }
    }

// return special price only
    if ($specials_price_only==true) {
      if (zen_not_null($special_price)) {
        return $special_price;
      } else {
        return false;
      }
    } else {
// get sale price

// changed to use master_categories_id
//      $product_to_categories = $db->Execute("select categories_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '" . (int)$product_id . "'");
//      $category = $product_to_categories->fields['categories_id'];

      $product_to_categories = $db->Execute("select master_categories_id from " . TABLE_PRODUCTS . " where products_id = '" . $product_id . "'");
      $category = $product_to_categories->fields['master_categories_id'];

      $sale = $db->Execute("select sale_specials_condition, sale_deduction_value, sale_deduction_type from " . TABLE_SALEMAKER_SALES . " where sale_categories_all like '%," . $category . ",%' and sale_status = '1' and (sale_date_start <= now() or sale_date_start = '0001-01-01') and (sale_date_end >= now() or sale_date_end = '0001-01-01') and (sale_pricerange_from <= '" . $product_price . "' or sale_pricerange_from = '0') and (sale_pricerange_to >= '" . $product_price . "' or sale_pricerange_to = '0')");
      if ($sale->RecordCount() < 1) {
         return $special_price;
      }

      if (!$special_price) {
        $tmp_special_price = $product_price;
      } else {
        $tmp_special_price = $special_price;
      }
      switch ($sale->fields['sale_deduction_type']) {
        case 0:
          $sale_product_price = $product_price - $sale->fields['sale_deduction_value'];
          $sale_special_price = $tmp_special_price - $sale->fields['sale_deduction_value'];
          break;
        case 1:
          $sale_product_price = $product_price - (($product_price * $sale->fields['sale_deduction_value']) / 100);
          $sale_special_price = $tmp_special_price - (($tmp_special_price * $sale->fields['sale_deduction_value']) / 100);
          break;
        case 2:
          $sale_product_price = $sale->fields['sale_deduction_value'];
          $sale_special_price = $sale->fields['sale_deduction_value'];
          break;
        default:
          return $special_price;
      }

      if ($sale_product_price < 0) {
        $sale_product_price = 0;
      }

      if ($sale_special_price < 0) {
        $sale_special_price = 0;
      }

      if (!$special_price) {
        return number_format($sale_product_price, 4, '.', '');
      } else {
        switch($sale->fields['sale_specials_condition']){
          case 0:
            return number_format($sale_product_price, 4, '.', '');
            break;
          case 1:
            return number_format($special_price, 4, '.', '');
            break;
          case 2:
            return number_format($sale_special_price, 4, '.', '');
            break;
          default:
            return number_format($special_price, 4, '.', '');
        }
      }
    }
  }

// FTP FUNCTIONS //
    
    function ftp_file_upload($url, $login, $password, $local_file, $ftp_dir='', $ftp_file=false, $ssl=false, $ftp_mode=FTP_ASCII) {
      if(!is_callable('ftp_connect')) {
        echo FTP_FAILED . NL;
        return false;
      }
      if(!$ftp_file)
        $ftp_file = basename($local_file);
      ob_start();
      if($ssl)
        $cd = ftp_ssl_connect($url);
      else
        $cd = ftp_connect($url);
      if (!$cd) {
        $out = $this->ftp_get_error_from_ob();
        echo FTP_CONNECTION_FAILED . ' ' . $url . NL;
        echo $out . NL;
        return false;
      }
      echo FTP_CONNECTION_OK . ' ' . $url . NL;
      $login_result = ftp_login($cd, $login, $password);
      if (!$login_result) {
        $out = $this->ftp_get_error_from_ob();
  //      echo FTP_LOGIN_FAILED . FTP_USERNAME . ' ' . $login . FTP_PASSWORD . ' ' . $password . NL;
        echo FTP_LOGIN_FAILED . NL;
        echo $out . NL;
        ftp_close($cd);
        return false;
      } else {
  //    echo FTP_LOGIN_OK . FTP_USERNAME . ' ' . $login . FTP_PASSWORD . ' ' . $password . NL;
        echo FTP_LOGIN_OK . NL;
        if ($ftp_dir != "") {
          if (!ftp_chdir($cd, $ftp_dir)) {
            $out = $this->ftp_get_error_from_ob();
            echo FTP_CANT_CHANGE_DIRECTORY . '&nbsp;' . $url . NL;
            echo $out . NL;
            ftp_close($cd);
            return false;
          }
        }
        echo FTP_CURRENT_DIRECTORY . '&nbsp;' . ftp_pwd($cd) . NL;
        if (GOOGLE_PRODUCTS_PASV == 'true') {
          $pasv = true;
        } else {
          $pasv = false;
        }
        ftp_pasv($cd, $pasv);
        $upload = ftp_put($cd, $ftp_file, $local_file, $ftp_mode);
        $out = $this->ftp_get_error_from_ob();
        $raw = ftp_rawlist($cd, $ftp_file, true);
        for($i=0,$n=sizeof($raw);$i<$n;$i++){
          $out .= $raw[$i] . '<br/>';
        }
        if (!$upload) {
          echo FTP_UPLOAD_FAILED . NL;
          if(isset($raw[0])) echo $raw[0] . NL;
          echo $out . NL;
          ftp_close($cd);
          return false;
        } else {
          echo FTP_UPLOAD_SUCCESS . NL;
          echo $raw[0] . NL;
          echo $out . NL;
        }
        ftp_close($cd);
        return true;
      }
    }

    function ftp_get_error_from_ob() {
      $out = ob_get_contents();
      ob_end_clean();
      $out = str_replace(array('\\', '<!--error-->', '<br>', '<br />', "\n", 'in <b>'),array('/', '', '', '', '', ''),$out);
      if(strpos($out, DIR_FS_CATALOG) !== false){
        $out = substr($out, 0, strpos($out, DIR_FS_CATALOG));
      }
      return $out;
    }

    function microtime_float() {
       list($usec, $sec) = explode(" ", microtime());
       return ((float)$usec + (float)$sec);
    }
  }

  //eof
