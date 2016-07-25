(function ($) {
    Drupal.behaviors.bxslider = {
        attach: function (context, settings) {
            if (settings.urls && (settings.urls != undefined)) {
                var slideIndex = function (slideIndex) {

                    for (var i = 0; i < settings.urls.length; i++) {
                        switch (slideIndex) {
                            case i:
                                return '<img src=' + settings.urls[i] + '>';
                        }
                    }
                };
            }
            if (!settings.bxslider && (settings.bxslider == undefined)) {
                return;
            }
            for (var sliderId in settings.bxslider) {
                if (settings.bxslider[sliderId].sliderSettings.buildPager) {
                    settings.bxslider[sliderId].sliderSettings.buildPager = slideIndex;
                    settings.bxslider[sliderId].sliderSettings.pagerCustom = null;
                }
                $('.bxslider', context).show().bxSlider(settings.bxslider[sliderId].sliderSettings);
            }
        }
    };
}(jQuery));