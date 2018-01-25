(function($) {

    /* --- SHOW-HIDE SELECTORS --- */
    var $showHideSelectors = $('.show-hide-selector');

    $showHideSelectors.change(function () {
        var $this = $(this);
        var identifier = $this.data('identifier');
        var type = $this.val();
        var $parent = $this.closest('.show-hide-container[data-identifier="' + identifier + '"]');
        var $rows = $parent.find('.show-hide-element[data-identifier="' + identifier + '"]');

        if (type == 'none') $parent.addClass('show-none');
        else $parent.removeClass('show-none');

        $rows.each(function() {
            var $row = $(this);

            if ($row.data('types') == '') {
                $row.removeClass('hide');
                return;
            }

            if ($row.data('types').indexOf(type) == -1) $row.addClass('hide');
            else $row.removeClass('hide');
        });
    });

    $showHideSelectors.filter(':not([name$="[type]"])').change();

    /* --- NAV-BAR HELPERS --- */
    var $navBarItemsContainer = $('#nav_bar_items');
    var $navBarTitles = $navBarItemsContainer.find('input[name$="[title]"]').addClass('nav-bar-title');
    var $navBarURLs = $navBarItemsContainer.find('input[name$="[url]"]').addClass('nav-bar-url');

    var $resetButton = $('.reset-item');

    var $autoPopulateButton = $('.auto-populate-action');

    var autoPopulateContainerClass = 'auto-populate-container';
    var autoPopulateListClass = 'auto-populate-list';
    var autoPopulateStatusClass = 'auto-populate-status';
    var autoPopulateIdClass = 'auto-populate-id';
    var autoPopulateMaxClass = 'auto-populate-max';
    var autoPopulateElementClass = 'auto-populate-element';

    var autoPopulateLevelData = 'level';
    var autoPopulateLabelData = 'label';

    $navBarTitles.add($navBarURLs).on('input propertychange paste', function() {
        var $this = $(this);
        var $parentTypeSelector = $this.closest('table').closest('tbody').find('> tr > td > input.show-hide-selector');
        var $typeSelector = $this.closest('tbody').find('> tr > td > input.show-hide-selector');

        if ($parentTypeSelector) {
            if ($this.val() != '') {
                if ($typeSelector.val() == 'none') $parentTypeSelector.val('dropdown').change();
            } else {
                var $typeSelectors = $this.closest('ol').find('> li > table > tbody > tr > td > input.show-hide-selector');
                var allEmpty = true;
                $typeSelectors.each(function() {
                    var val = $(this).val();
                    if (val != 'hunt' && val != 'none') {
                        allEmpty = false;
                        return false;
                    }
                });
                if (allEmpty) $parentTypeSelector.val('hunt').change();
            }
        }

        if ($this.hasClass('nav-bar-title')) {
            if ($this.val() != '') {
                if ($typeSelector.val() == 'none') $typeSelector.val('hunt').change();
            } else {
                if ($typeSelector.val() == 'hunt') $typeSelector.val('none').change();
            }
        } else if ($this.hasClass('nav-bar-url')) {
            if ($this.val() != '') {
                if ($typeSelector.val() == 'hunt') $typeSelector.val('single').change();
            } else {
                $typeSelector.val('hunt').change();
            }
        }
    });

    $resetButton.click(function() {
        var $this = $(this);
        var $parent = $this.hasClass('reset-all') ?
            $navBarItemsContainer.find('> .inside > ol > li > table')
            : $this.closest('table');
        var $inputs = $parent.find('> tbody > tr > td > input[type=text]');
        if (!($this.hasClass('reset-titles') || $this.hasClass('reset-all'))) $inputs = $inputs.not('.nav-bar-title');
        var $buttons = $parent.find('> tbody > tr > td > ol > li > table > tbody > tr > td > .reset-item');
        var $typeSelectors = $parent.find('> tbody > tr > td > ol > li > table > tbody > tr > td > .show-hide-selector');

        $buttons.addClass('reset-titles').click().removeClass('reset-titles');
        $typeSelectors.val('none').change();
        $inputs.val('').trigger('input');
    });

    $autoPopulateButton.click(function() {
        var $this = $(this);
        var $parent = $this.data('level') == 0 ?
            $navBarItemsContainer.find('> .inside')
            : $this.closest('table').parent();
        console.log('$parent');
        console.log($parent);
        var $status = $parent.find('.' + autoPopulateStatusClass + '[data-' + autoPopulateLevelData
            + '="' + $this.data(autoPopulateLevelData) + '"]');
        var $id = $parent.find('.' + autoPopulateIdClass + '[data-' + autoPopulateLevelData
            + '="' + $this.data(autoPopulateLevelData) + '"]');
        var $max = $parent.find('.' + autoPopulateMaxClass + '[data-' + autoPopulateLevelData
            + '="' + $this.data(autoPopulateLevelData) + '"]');
        console.log('$id');
        console.log($id);

        function do_populate(level, items, $currentParent) {
            var $list = $currentParent;
            console.log('$list');
            console.log($list);

            console.log('items');
            console.log(items);

            items.forEach(function(item, index) {
                var $element = $list.find('> li').eq(index);
                console.log('$element');
                console.log($element);

                for (var key in item) {
                    if (key == 'items') {
                        do_populate(level + 1, item.items, $element.find('> table > tbody > tr > td > ol'));
                        continue;
                    }

                    if (!item.hasOwnProperty(key)) continue;

                    $element.find('> table > tbody > tr > td > input[name$="[' + key + ']"]').val(item[key]).trigger('input');
                }
            });
        }

        $status.show().html('Working...');

        jQuery.post(
            ajaxurl,
            {
                'action': 'mm_nav_bar_auto_populate',
                'page-id': $id.val(),
                'level': $this.data(autoPopulateLevelData),
                'use-as-home': $this.data('use-as-home'),
                'max-level': $max.val()
            },
            function(response){
                var data = response;

                if(data.error) {
                    $status.show().html('An error occurred: ' + data.error);
                    return;
                }

                var $inputs = $parent.find('input[type=text]');
                $inputs.val('');

                do_populate($this.data(autoPopulateLevelData), data, $parent.find('> ol'));

                $status.hide();
            }
        ).fail(function() {
            $status.show().html('An error occurred.');
        });
    });

    /* --- SORTABLE ARRAYS --- */
    // Note: that this implementation of sortable arrays uses the show-hide variables from above.
    // Also: start and stop functions have custom functionality to aid tinymce editors within the list elements. These
    //   can be removed if there are no tinymce (wp_editor) instances in the lists.
    var $sortableArrays = $('.sortable-array');

    var sortIndexClass = 'sort-index';

    $sortableArrays.sortable({
        handle: '> .handle-container .handle',
        axis: 'y',
        start: function (e, ui) {
            $(ui.item).find('textarea').each(function () {
                if (typeof(tinymce) === 'undefined') return;
                tinymce.execCommand('mceRemoveEditor', false, $(this).attr('id'));
            });
        },
        stop: function (e, ui) {
            $(ui.item).find('textarea').each(function () {
                if (typeof(tinymce) === 'undefined') return;
                tinymce.execCommand('mceAddEditor', true, $(this).attr('id'));
            });
        },
        update: function (e, ui) {
            $(ui.item).parent().find('> li').each(function (index, el) {
                $(el).find('> .' + sortIndexClass).val(index);
            });
        }
    });

    /* --- BACKGROUND PREVIEWS --- */
    var bgPositions = ['center', 'top', 'bottom'];

    var bgPreviewContainerClass = 'background-preview-container';
    var bgPlaceholderClass = 'placeholder';
    var bgPreviewClass = 'background-preview';
    var bgPositionPrefix = 'position-';
    var bgIDClass = 'background-id';

    var $bgPositionSelectors = $('.background-position');

    $bgPositionSelectors.change(function () {
        var $preview = $(this).closest('.' + bgPreviewContainerClass).find('.' + bgPreviewClass);
        bgPositions.forEach(function (position) {
            $preview.removeClass(bgPositionPrefix + position);
        });
        $preview.addClass(bgPositionPrefix + $(this).val());
    });

    /* --- ICON PREVIEWS --- */
    var defaultIcon = 'square-o';

    var iconExampleClass = 'icon-example';
    var iconExampleContainerClass = 'icon-example-container';
    var iconExampleBaseClass = 'icon-example-base';

    var $iconTextBoxes = $('.icon-example-text');

    $iconTextBoxes.change(function () {
        var $parent = $(this).closest('.' + iconExampleContainerClass);
        var $icon = $parent.find('.' + iconExampleClass);
        var $baseIcon = $parent.find('.' + iconExampleBaseClass);

        $baseIcon.clone().insertAfter($baseIcon).removeClass(iconExampleBaseClass)
            .addClass(iconExampleClass).addClass('fa-' + ($(this).val() || defaultIcon));
        $icon.remove();
    });

    /* --- BACKGROUND UPDATER --- */
    var backgroundTaxonomyName = 'header_background';

    var $backgroundUploadButtons = $('.background-upload-button');
    var $backgroundClearButtons = $('.background-clear-button');

    var attachmentFilters = wp.media.view.AttachmentFilters;

    wp.media.view.AttachmentFilters[backgroundTaxonomyName] = wp.media.view.AttachmentFilters.extend({
        createFilters: function () {
            attachmentFilters.prototype.createFilters.apply(this, arguments);

            var filters = {};

            _.each(backgroundTaxonomyData || [], function(value) {
                filters[value.slug] = {};
                filters[value.slug]['text'] = 'Used As ' + value.name + ' Background';
                filters[value.slug]['props'] = {};
                filters[value.slug]['props'][backgroundTaxonomyName] = value.slug;

                if (value.slug == 'any') filters[value.slug]['priority'] = 10;
            });

            var broadProps = {};
            broadProps[backgroundTaxonomyName] = 0;

            filters['all'] = {
                text: 'All Images (filter by background use)',
                props: broadProps,
                priority: 9
            };

            this.filters = filters;
        }
    });

    var attachmentBrowser = wp.media.view.AttachmentsBrowser;

    wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
        createToolbar: function () {
            attachmentBrowser.prototype.createToolbar.apply(this, arguments);

            this.toolbar.set(backgroundTaxonomyName, new wp.media.view.AttachmentFilters[backgroundTaxonomyName]({
                controller: this.controller,
                model: this.collection.props,
                priority: -80
            }).render());
        }
    });

    var frame;
    var currentElement;

    $backgroundUploadButtons.on('click', function (e) {
        currentElement = $(this).closest('.' + bgPreviewContainerClass);

        if (frame === undefined) {
            frame = wp.media({
                title: 'Choose a Background',
                library: {type: 'image'},
                multiple: false,
                button: {text: 'Select Background'}
            });
        }

        frame.on('select', function () {
            var json = frame.state().get('selection').first().toJSON();
            currentElement.find('.' + bgIDClass).val(json.id);
            currentElement.find('.' + bgPreviewClass)
                .removeClass(bgPlaceholderClass).css('background-image', 'url("' + json.url + '")');
        });

        frame.open();

        return false;
    });

    $backgroundClearButtons.on('click', function (e) {
        currentElement = $(this).closest('.' + bgPreviewContainerClass);

        currentElement.find('.' + bgIDClass).val('');
        currentElement.find('.' + bgPreviewClass).addClass(bgPlaceholderClass).css('background-image', '');

        return false;
    });

})(jQuery);
