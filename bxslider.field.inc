<?php
/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Prepare variables for image formatter templates.
 *
 * Default template: image-bxslider.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - image_style: An optional image style.
 *   - url: An optional \Drupal\Core\Url object.
 */
function template_preprocess_image_bxslider(&$variables) {
  $variables['image'] = !empty($variables['image_style']) ?
    array('#theme' => 'image_style', '#style_name' => $variables['image_style']) : array('#theme' => 'image');
  $variables['image']['#attributes'] = $variables['item_attributes'];
  $item = $variables['item'];
  // Do not output an empty 'title' attribute.
  if (Unicode::strlen($item->title) != 0) {
    $variables['image']['#title'] = $item->title;
  }
  $variables['image']['#uri'] = (($entity = $item->entity) && empty($item->uri)) ?
    $entity->getFileUri() : $item->uri;
  $image_attributes = array('width', 'height', 'alt');
  foreach ($image_attributes as $key) {
    $variables['image']['#' . $key] = $item->$key;
  }
}

/**
 * Prepare variables for container templates.
 *
 * Default template: images-bxslider.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #children.
 */
function template_preprocess_images_bxslider(&$variables) {
  $element = $variables['element'];
  $variables['children'] = $element['#children'];
  $variables['pager'] = $element['#pager'];
  $variables['method1'] = $element['#method1'];
  $variables['image_urls'] = $element['#image_urls'];
}
