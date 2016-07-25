<?php
namespace Drupal\bxslider\Plugin\Field\FieldFormatter;
/**
 * @file
 * Contains \Drupal\bxslider\Plugin\Field\FieldFormatter\BxsliderImageFormatter.
 */

use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'bxslider_image_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "bxslider_image_formatter",
 *   label = @Translation("Bxslider"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class BxsliderImageFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The entity storage for the image.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition,
                              array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user,
                              LinkGeneratorInterface $link_generator, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->linkGenerator = $link_generator;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('link_generator'),
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => 'large',
      'general' => array(
        'mode' => 'horizontal',
        'speed' => 500,
        'slideMargin' => 0,
        'startSlide' => 0,
        'randomStart' => FALSE,
        'infiniteLoop' => TRUE,
        'hideControlOnEnd' => TRUE,
        'easing' => '',
        'captions' => FALSE,
        'ticker' => FALSE,
        'tickerHover' => FALSE,
        'adaptiveHeight' => FALSE,
        'adaptiveHeightSpeed' => 500,
        'video' => FALSE,
        'responsive' => TRUE,
        'useCSS' => TRUE,
        'preloadImages' => 'visible',
        'touchEnabled' => TRUE,
        'swipeThreshold' => 50,
        'oneToOneTouch' => TRUE,
        'preventDefaultSwipeX' => TRUE,
        'preventDefaultSwipeY' => FALSE,
      ),
      'pager' => array(
        'pager' => TRUE,
        'pagerType' => 'full',
        'pagerShortSeparator' => ' / ',
        'pagerSelector' => '',
        'pager_custom_type' => 'none',
        'pagerCustom' => 'null',
        'pager_custom_image_style' => 'thumbnail',
      ),
      'controls' => array(
        'controls' => TRUE,
        'nextText' => 'Next',
        'prevText' => 'Prev',
        'nextSelector' => '',
        'prevSelector' => '',
        'autoControls' => FALSE,
        'startText' => 'Start',
        'stopText' => 'Stop',
        'autoControlsCombine' => FALSE,
        'autoControlsSelector' => '',
      ),
      'auto' => array(
        'auto' => FALSE,
        'pause' => 4000,
        'autoStart' => TRUE,
        'autoDirection' => 'next',
        'autoHover' => FALSE,
        'autoDelay' => 0,
      ),
      'carousel' => array(
        'minSlides' => 1,
        'maxSlides' => 1,
        'moveSlides' => 0,
        'slideWidth' => 0,
      ),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $field = $this->fieldDefinition->getName();
    $image_styles = image_style_options(FALSE);
    $element['image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => array(
        '#markup' => $this->linkGenerator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ),
    );
    $element['general'] = array(
      '#type' => 'details',
      '#title' => t('General'),
      '#weight' => 1,
      '#open' => FALSE,
    );
    $element['general']['mode'] = array(
      '#title' => t('Mode'),
      '#type' => 'select',
      '#default_value' => $settings['general']['mode'],
      '#options' => array(
        'horizontal' => 'horizontal',
        'vertical' => 'vertical',
        'fade' => 'fade',
      ),
    );
    $element['general']['speed'] = array(
      '#title' => t('Speed'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['general']['speed'],
    );
    $element['general']['slideMargin'] = array(
      '#title' => t('slideMargin'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['general']['slideMargin'],
    );
    $element['general']['startSlide'] = array(
      '#title' => t('startSlide'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['general']['startSlide'],
    );
    $element['general']['randomStart'] = array(
      '#type' => 'checkbox',
      '#title' => t('randomStart'),
      '#default_value' => $settings['general']['randomStart'],
    );
    $element['general']['infiniteLoop'] = array(
      '#type' => 'checkbox',
      '#title' => t('infiniteLoop'),
      '#default_value' => $settings['general']['infiniteLoop'],
    );
    $element['general']['hideControlOnEnd'] = array(
      '#type' => 'checkbox',
      '#title' => t('hideControlOnEnd'),
      '#default_value' => $settings['general']['hideControlOnEnd'],
    );
    $element['general']['easing'] = array(
      '#title' => t('easing'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['general']['easing'],
    );
    $element['general']['captions'] = array(
      '#type' => 'checkbox',
      '#title' => t('captions'),
      '#default_value' => $settings['general']['captions'],
    );
    $element['general']['ticker'] = array(
      '#type' => 'checkbox',
      '#title' => t('ticker'),
      '#default_value' => $settings['general']['ticker'],
    );
    $element['general']['tickerHover'] = array(
      '#type' => 'checkbox',
      '#title' => t('tickerHover'),
      '#default_value' => $settings['general']['tickerHover'],
    );
    $element['general']['adaptiveHeight'] = array(
      '#type' => 'checkbox',
      '#title' => t('adaptiveHeight'),
      '#default_value' => $settings['general']['adaptiveHeight'],
    );
    $element['general']['adaptiveHeightSpeed'] = array(
      '#title' => t('adaptiveHeightSpeed'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['general']['adaptiveHeightSpeed'],
    );
    $element['general']['responsive'] = array(
      '#type' => 'checkbox',
      '#title' => t('responsive'),
      '#default_value' => $settings['general']['responsive'],
    );
    $element['general']['useCSS'] = array(
      '#type' => 'checkbox',
      '#title' => t('useCSS'),
      '#default_value' => $settings['general']['useCSS'],
    );
    $element['general']['preloadImages'] = array(
      '#title' => t('preloadImages'),
      '#type' => 'select',
      '#default_value' => $settings['general']['preloadImages'],
      '#options' => array(
        'all' => 'all',
        'visible' => 'visible',
      ),
    );
    $element['general']['preloadImages'] = array(
      '#type' => 'checkbox',
      '#title' => t('preloadImages'),
      '#default_value' => $settings['general']['preloadImages'],
    );
    $element['general']['swipeThreshold'] = array(
      '#title' => t('swipeThreshold'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['general']['swipeThreshold'],
    );
    $element['general']['oneToOneTouch'] = array(
      '#type' => 'checkbox',
      '#title' => t('oneToOneTouch'),
      '#default_value' => $settings['general']['oneToOneTouch'],
    );
    $element['general']['preventDefaultSwipeX'] = array(
      '#type' => 'checkbox',
      '#title' => t('preventDefaultSwipeX'),
      '#default_value' => $settings['general']['preventDefaultSwipeX'],
    );
    $element['general']['preventDefaultSwipeY'] = array(
      '#type' => 'checkbox',
      '#title' => t('preventDefaultSwipeY'),
      '#default_value' => $settings['general']['preventDefaultSwipeY'],
    );
    $element['pager'] = array(
      '#type' => 'details',
      '#title' => t('Pager'),
      '#weight' => 2,
      '#open' => FALSE,
    );
    $element['pager']['pager'] = array(
      '#type' => 'checkbox',
      '#title' => t('pager'),
      '#default_value' => $settings['pager']['pager'],
    );
    $element['pager']['pagerType'] = array(
      '#title' => t('pagerType'),
      '#type' => 'select',
      '#default_value' => $settings['pager']['pagerType'],
      '#options' => array(
        'full' => 'full',
        'short' => 'short',
      ),
      '#states' => array(
        'enabled' => array(
          ':input[name="fields[' . $field . '][settings_edit_form][settings][pager][pager]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $element['pager']['pagerShortSeparator'] = array(
      '#title' => t('pagerShortSeparator'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['pager']['pagerShortSeparator'],
      '#states' => array(
        'enabled' => array(
          ':input[name="fields[' . $field . '][settings_edit_form][settings][pager][pager]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $element['pager']['pagerSelector'] = array(
      '#title' => t('pagerSelector'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['pager']['pagerSelector'],
      '#states' => array(
        'enabled' => array(
          ':input[name="fields[' . $field . '][settings_edit_form][settings][pager][pager]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $element['pager']['pager_custom_type_markup'] = array(
      '#markup' => '<hr>',
    );
    $element['pager']['pager_custom_type'] = array(
      '#title' => t('Custom Pager'),
      '#type' => 'select',
      '#default_value' => $settings['pager']['pager_custom_type'],
      '#options' => array(
        'none' => 'None',
        'thumbnail_pager_method1' => t('Thumbnail pager - method 1'),
        'thumbnail_pager_method2' => t('Thumbnail pager - method 2'),
      ),
      '#states' => array(
        'enabled' => array(
          ':input[name="fields[' . $field . '][settings_edit_form][settings][pager][pager]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $element['pager']['pager_custom_image_style'] = array(
      '#title' => t('Custom Pager - Image style'),
      '#type' => 'select',
      '#default_value' => $settings['pager']['pager_custom_image_style'],
      '#empty_option' => t('None (thumbnail)'),
      '#options' => $image_styles,
      '#description' => t('Used only when some the "Custom Pager" option is selected.'),
      '#states' => array(
        'enabled' => array(
          array(
            array(':input[name="fields[' . $field . '][settings_edit_form][settings][pager][pager_custom_type]"]' => array('value' => 'thumbnail_pager_method1')),
            'xor',
            array(':input[name="fields[' . $field . '][settings_edit_form][settings][pager][pager_custom_type]"]' => array('value' => 'thumbnail_pager_method2')),
          ),
          ':input[name="fields[' . $field . '][settings_edit_form][settings][pager][pager]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $element['controls'] = array(
      '#type' => 'details',
      '#title' => t('Controls'),
      '#weight' => 3,
      '#open' => FALSE,
    );
    $element['controls']['controls'] = array(
      '#type' => 'checkbox',
      '#title' => t('controls'),
      '#default_value' => $settings['controls']['controls'],
    );
    $element['controls']['nextText'] = array(
      '#title' => t('nextText'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['controls']['nextText'],
    );
    $element['controls']['prevText'] = array(
      '#title' => t('prevText'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['controls']['prevText'],
    );
    $element['controls']['nextSelector'] = array(
      '#title' => t('nextSelector'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['controls']['nextSelector'],
    );
    $element['controls']['prevSelector'] = array(
      '#title' => t('prevSelector'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['controls']['prevSelector'],
    );
    $element['controls']['autoControls'] = array(
      '#type' => 'checkbox',
      '#title' => t('autoControls'),
      '#default_value' => $settings['controls']['autoControls'],
    );
    $element['controls']['startText'] = array(
      '#title' => t('startText'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['controls']['startText'],
    );
    $element['controls']['stopText'] = array(
      '#title' => t('stopText'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['controls']['stopText'],
    );
    $element['controls']['autoControlsCombine'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto'),
      '#default_value' => $settings['controls']['autoControlsCombine'],
    );
    $element['controls']['autoControlsSelector'] = array(
      '#title' => t('autoControlsSelector'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['controls']['autoControlsSelector'],
    );
    $element['auto'] = array(
      '#type' => 'details',
      '#title' => t('Auto'),
      '#weight' => 4,
      '#open' => FALSE,
    );
    $element['auto']['auto'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto'),
      '#default_value' => $settings['auto']['auto'],
    );
    $element['auto']['pause'] = array(
      '#title' => t('pause'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['auto']['pause'],
    );
    $element['auto']['autoStart'] = array(
      '#type' => 'checkbox',
      '#title' => t('autoStart'),
      '#default_value' => $settings['auto']['autoStart'],
    );
    $element['auto']['autoDirection'] = array(
      '#title' => t('autoDirection'),
      '#type' => 'select',
      '#default_value' => $settings['auto']['autoDirection'],
      '#options' => array(
        'next' => 'next',
        'prev' => 'prev',
      ),
    );
    $element['auto']['autoHover'] = array(
      '#type' => 'checkbox',
      '#title' => t('autoHover'),
      '#default_value' => $settings['auto']['autoHover'],
    );
    $element['auto']['autoDelay'] = array(
      '#title' => t('autoDelay'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['auto']['autoDelay'],
    );
    $element['carousel'] = array(
      '#type' => 'details',
      '#title' => t('Carousel'),
      '#weight' => 5,
      '#open' => FALSE,
    );
    $element['carousel']['minSlides'] = array(
      '#title' => t('minSlides'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['carousel']['minSlides'],
    );
    $element['carousel']['maxSlides'] = array(
      '#title' => t('maxSlides'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['carousel']['maxSlides'],
    );
    $element['carousel']['moveSlides'] = array(
      '#title' => t('moveSlides'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['carousel']['moveSlides'],
    );
    $element['carousel']['slideWidth'] = array(
      '#title' => t('slideWidth'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $settings['carousel']['slideWidth'],
    );
    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (!empty($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t('Original image');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = $settings = $url_files = $cache_tags = $method = array();
    $files = $this->getEntitiesToView($items, $langcode);
    $pager_custom_type = $this->getSetting('pager')['pager_custom_type'];
    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }
    $settings['sliderId'] = $items->getName();
    $image_style_setting = $this->getSetting('image_style');
    // Collect cache tags to be added for each item in the field.
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }
    foreach ($files as $delta => $file) {
      if (!empty($link_file)) {
        $image_uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($image_uri));
      }
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());
      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $image_uri = $file->getFileUri();
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);
      $elements[$delta] = array(
        '#theme' => 'image_bxslider',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#cache' => array(
          'tags' => $cache_tags,
        ),
      );
      if (!empty($pager_custom_type) && $pager_custom_type == 'thumbnail_pager_method1') {
        $url = ImageStyle::load($this->getSetting('pager')['pager_custom_image_style'])
          ->buildUrl($image_uri);
        $url_files[$delta] = $url;
        $method1 = '#' . $items->getName();
        $method = array('pagerCustom' => '#' . $items->getName());
      }
      elseif (!empty($pager_custom_type) && $pager_custom_type == 'thumbnail_pager_method2') {
        $url = ImageStyle::load($this->getSetting('pager')['pager_custom_image_style'])
          ->buildUrl($image_uri);
        $url_files[$delta] = $url;
        $method = array('buildPager' => 'thumbnail_pager_method2');
      }
    }
    $container = array(
      '#theme' => 'images_bxslider',
      '#children' => $elements,
      '#pager' => 'bx-pager',
      '#image_urls' => $url_files,
      '#method1' => !empty($method1) ? $method1 : NULL,
    );
    // Attach library.
    $container['#attached']['library'][] = 'bxslider/bxslider-library';
    if (!empty($pager_custom_type) && $pager_custom_type == 'thumbnail_pager_method2') {
      $container['#attached']['library'][] = 'bxslider/bxslider-method2';
    }
    $settings['sliderSettings'] = array_merge(
      $this->getSetting('general'),
      $this->getSetting('pager'),
      $this->getSetting('controls'),
      $this->getSetting('auto'),
      $this->getSetting('carousel'),
      $method);
    $container['#attached']['drupalSettings']['bxslider'][$items->getName()] = $settings;
    $container['#attached']['drupalSettings']['urls'] = $url_files;
    return $container;
  }

}