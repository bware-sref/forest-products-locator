var mills = {

    'map': null,
    'clusterer': null,
    'millStateHandler': null,
    'millState': null,
    'millStateCount': 0,
    'millStateViewstate': null,
    'millCountyHandler': null,
    'millCounty': null,
    'millCountyCount': 0,
    'millCountyViewstate': null,
    'millTypeHandler': null,
    'millType': null,
    'millTypeCount': 0,
    'millTypeViewstate': null,
    'millSpeciesHandler': null,
    'millSpecies': null,
    'millSpeciesCount': null,
    'millSpeciesViewstate': null,
    'millLoader': null,
    'millLoading': false,
    'markers': [],
    'currentMarkers': [],
    'searchMillName': 'Mill Name...',
    'lastSearchValue': '',
    'icons': {},
    'sprites': {},
    'query': {},
    'initialLocation': '',
    'defaultLocation': { lat: 32.872259, lng: -87.9 },
    'georgia': { lat: 32.872259, lng: -83.868209 },
    'browserSupportFlag': new Boolean(),

    'init': async function () {
        // Toggle Map Search Options
        const { Map } = await google.maps.importLibrary("maps");
        var map_toggle = jq('#map-toggle');
        var map_toolbar = jq('#map-toolbar');
        map_toggle.bind('click', function () {
            if (map_toolbar.hasClass('closed')) {
                new Effect.SlideDown('map-search', { duration: 1, transition: Effect.Transitions.spring });
                map_toolbar.removeClass('closed')
            }
            else {
                new Effect.SlideUp('map-search', { duration: 0.25 });
                map_toolbar.addClass('closed')
            }
        });

        // Show options bar above map
        map_toolbar.show();

        var map_options_bar = jq('#map-options');

        map_options_bar.find('#full-enter').children('a').bind('click', function () {
            mills.fullscreen();
            return false;
        });

        map_options_bar.find('#full-exit').children('a').bind('click', function () {
            mills.close_fullscreen();
            return false;
        });

        options = {
            zoom: 5,
            center: this.defaultLocation,
            mapId: "MILL_MAP",
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: 'dropdown_menu',
                mapTypeIds: ["roadmap", "satellite"],
            },
            navigationControl: true,
            navigationControlOptions: {
                style: 'small'
            },
            scaleControl: true
        };

        mills.map = new Map(document.getElementById("map-canvas"), options);

        var marker_plywood = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 33),
          new google.maps.Point(0, 7),
          new google.maps.Point(20, 17));
        this.icons['plywood'] = marker_plywood;
        this.sprites['Veneer Plywood Panels'] = '/sprite-plywood.gif';

        var marker_paper = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 28),
          new google.maps.Point(0, 43),
          new google.maps.Point(20, 14));
        this.icons['paper'] = marker_paper;
        this.sprites['Pulp Paper'] = '/sprite-paper.gif';

        var marker_chip = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 26),
          new google.maps.Point(0, 83),
          new google.maps.Point(20, 13));
        this.icons['chip'] = marker_chip;
        this.sprites['Chip'] = '/sprite-chip.gif';

        var marker_post = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 33),
          new google.maps.Point(0, 110),
          new google.maps.Point(20, 17));
        this.icons['post'] = marker_post;
        this.sprites['Post Pole'] = '/sprite-pole.gif';

        var marker_firewood = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 36),
          new google.maps.Point(0, 144),
          new google.maps.Point(20, 18));
        this.icons['firewood'] = marker_firewood;
        this.sprites['Firewood'] = '/sprite-firewood.gif';

        var marker_log = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 33),
          new google.maps.Point(0, 184),
          new google.maps.Point(20, 17));
        this.icons['log'] = marker_log;
        this.sprites['Log Home'] = '/sprite-log.gif';

        var marker_other = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 30),
          new google.maps.Point(0, 223),
          new google.maps.Point(20, 15));
        this.icons['other'] = marker_other;
        this.sprites['Other'] = '/sprite-other.gif';

        var marker_sawmill = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 30),
          new google.maps.Point(0, 257),
          new google.maps.Point(20, 15));
        this.icons['sawmill'] = marker_sawmill;
        this.sprites['Sawmill'] = '/sprite-saw.gif';

        var marker_mulch = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 20),
          new google.maps.Point(0, 303),
          new google.maps.Point(20, 10));
        this.icons['mulch'] = marker_mulch;
        this.sprites['Mulch'] = '/sprite-mulch.gif';

        var marker_shavings = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 36),
          new google.maps.Point(0, 325),
          new google.maps.Point(20, 18));
        this.icons['shavings'] = marker_shavings;
        this.sprites['Shavings'] = '/sprite-shavings.gif';

        var marker_energy = new google.maps.MarkerImage('/markers.png',
          new google.maps.Size(40, 30),
          new google.maps.Point(0, 366),
          new google.maps.Point(20, 15));
        this.icons['energy'] = marker_energy;
        this.sprites['Energy Product'] = '/sprite-energy.png';

        jq('#mill-submit').hide();
        this.millLoader = jq('#mill-loading');

        var searchMillNameBox = jq('#mill-name');
        searchMillNameBox.attr('value', this.searchMillName);
        searchMillNameBox.addClass('grayText');

        searchMillNameBox.bind('click', function () {
            val = jq(this).val();
            if (val == mills.searchMillName) {
                jq(this).attr('value', '');
                jq(this).removeClass('grayText');
            }
        });

        searchMillNameBox.bind('blur', function () {
            val = jq(this).val();
            if (val == '') {
                jq(this).attr('value', mills.searchMillName);
                jq(this).addClass('grayText');
            }
        });

        searchMillNameBox.bind('keyup search', jq.debounce(300, function () {
            val = jq(this).val();
            if (val == mills.lastSearchValue) return;

            mills.lastSearchValue = val;
            mills.query['SearchableText'] = val;
            mills.clusterer.clearMarkers();
            mills.millLoader.show();
            mills.findMarkers();
        })
        );

        // Init 'State' DropDown
        this.millState = jq('#mill-state');
        this.millStateHandler = mills.millState.msDropDown().data("dd");
        this.millState.bind('change', function () {
            if (mills.millLoading == true)
                return;

            mills.millStateViewstate = mills.millState.val();
            mills.loadMillCounties();
            mills.loadMillSpecies();
            mills.loadMillTypes();
            mills.loadMarkers();
        });

        // Init 'County' DropDown
        this.millCounty = jq('#mill-county');
        this.millCountyHandler = mills.millCounty.msDropDown({ visibleRows: 10 }).data("dd");
        this.millCounty.bind('change', function () {
            if (mills.millLoading == true)
                return;

            mills.millCountyViewstate = mills.millCounty.val();
            mills.loadMillSpecies();
            mills.loadMillTypes();
            mills.loadMarkers();
        });

        // Init 'Mill Species' DropDown
        this.millSpecies = jq('#mill-species');
        this.millSpeciesHandler = mills.millSpecies.msDropDown().data("dd");
        this.millSpecies.bind('change', function () {
            if (mills.millLoading == true)
                return;

            mills.millSpeciesViewstate = mills.millSpecies.val();
            mills.loadMillStates();
            mills.loadMillCounties();
            mills.loadMillTypes();
            mills.loadMarkers();
        });

        // Init 'Mill Type' DropDown
        this.millType = jq('#mill-type');
        this.millTypeHandler = mills.millType.msDropDown({ showIcon: true }).data("dd");
        this.millType.bind('change', function () {
            if (mills.millLoading == true)
                return;

            mills.millTypeViewstate = mills.millType.val();
            mills.loadMillStates();
            mills.loadMillCounties();
            mills.loadMillSpecies();
            mills.loadMarkers();
        });

        this.detectBrowser();
        this.loadMillStates();
        this.loadMillCounties();
        this.loadMillSpecies();
        this.loadMillTypes();
        this.loadMarkers();
        this.findMarkers();
    },

    'findLocation': function () {
        // Try W3C Geolocation (Preferred)
        if (navigator.geolocation) {
            this.browserSupportFlag = true;
            navigator.geolocation.getCurrentPosition(function (position) {
                this.initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                this.map.setCenter(this.initialLocation);
            }, function () {
                handleNoGeolocation(this.browserSupportFlag);
            });

            // Try Google Gears Geolocation
        } else if (google.gears) {
            this.browserSupportFlag = true;
            var geo = google.gears.factory.create('beta.geolocation');
            geo.getCurrentPosition(function (position) {
                this.initialLocation = new google.maps.LatLng(position.latitude, position.longitude);
                this.map.setCenter(this.initialLocation);
            }, function () {
                handleNoGeoLocation(this.browserSupportFlag);
            });

            // Browser doesn't support Geolocation
        } else {
            this.browserSupportFlag = false;
            handleNoGeolocation(this.browserSupportFlag);
        }

        function handleNoGeolocation(errorFlag) {
            this.initialLocation = this.defaultLocation;

            if (this.map != null)
                this.map.setCenter(this.initialLocation);
        }
    },


    'detectBrowser': function () {
        var useragent = navigator.userAgent;
        var mapdiv = jq("#map-canvas");

        if (useragent.indexOf('iPhone') != -1 || useragent.indexOf('Android') != -1) {
            mapdiv.style.width = '100%';
            mapdiv.style.height = '100%';
        }
    },


    'fullscreen': function () {
        var wrapper = document.getElementById("map-area");
        jq(wrapper).addClass('fullscreen');

        jq('#full-enter').hide();
        jq('#full-exit').show();

        google.maps.event.trigger(mills.map, 'resize');
        this.map.setCenter(this.defaultLocation);
        jq("body").css("overflow", "hidden");

        jq(window).scrollTop(0);
    },

    'close_fullscreen': function () {
        var toolbar = document.getElementById("map-area");
        jq(toolbar).removeClass('fullscreen');

        jq('#full-exit').hide();
        jq('#full-enter').show();

        google.maps.event.trigger(mills.map, 'resize');
        this.map.setCenter(this.defaultLocation);
        jq("body").css("overflow", "auto");
    },
    // 'loadMarkers': async function () {
    'loadMarkers': function () {
       
        // const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");
        if (mills.millLoading == true)
            return;

        mills.markers = [];
        mills.millLoading = true;
        mills.millLoader.show();

        var reqState = (mills.millStateViewstate == null) ? mills.millState.val() : mills.millStateViewstate;
        mills.query['getState'] = reqState;
        jq('#mill-state-exp').val(reqState);

        var reqCounty = (mills.millCountyViewstate == null) ? mills.millCounty.val() : mills.millCountyViewstate;
        mills.query['getCounty'] = reqCounty;
        jq('#mill-county-exp').val(reqCounty);

        var reqSpecies = (mills.millSpeciesViewstate == null) ? mills.millSpecies.val() : mills.millSpeciesViewstate;
        mills.query['getSpecies'] = reqSpecies;
        jq('#mill-species-exp').val(reqSpecies);

        var reqType = (mills.millTypeViewstate == null) ? mills.millType.val() : mills.millTypeViewstate;
        mills.query['getMillType'] = reqType;
        jq('#mill-type-exp').val(reqType);

        jq.post("/searchMills.json", this.query, function (output) {

            var infowindow;
            var bounds = new google.maps.LatLngBounds();

            jq.each(output.mills, function (i, mill) {
                var latLng = new google.maps.LatLng(parseFloat(mill.lat), parseFloat(mill.long));

                bounds.extend(latLng);
                // experimenting with using the sprite sheet to get singular image objects for the icons below
                // var glyph_icon = document.createElement("img");
                // glyph_icon.src = "/markers.png";
                // glyph_icon.style = "object-fit: none; object-position: -64px 0; width: 32px; height: 32px"; <-- example, would need to store and retrieve the proper sizes and offsets somewhere above, instead of the current MarkerImage objects stored in the icons array. 
                // Would be best to just create the images up there too, and store them in the icon array, probably.
                // var pin = new PinElement({ glyph: glyph_icon });
                // var marker = new AdvancedMarkerElement({ "MILL_MAP", position: latLng, content: pin.element, title: mill.title });
                var marker = new google.maps.Marker({
                    position: latLng,
                    title: mill.title,
                    icon: mills.icons[mill.marker]
                });

                mills.markers.push(marker);

                var contentString = '<div class="info">' +
                  '<h3 class="firstHeading">' + mill.title + '</h3>' +
                  '<div id="bodyContent">' +
                  '<p>' + mill.add + '<br />' +
                  mill.city + ', ' + mill.state + ' ' + mill.zip +
                  '</p>';
                contentString += '<p><b>Mill Type:</b> ' + mill.type + "</p>";
                contentString += '<a target="_blank" href="' + mill.url + '">More Information...</a>';
                contentString += '</div>';

                google.maps.event.addListener(marker, 'click', function () {
                    if (!mills.infowindow) {
                        mills.infowindow = new google.maps.InfoWindow();
                    }

                    mills.infowindow.setContent(contentString);
                    mills.infowindow.open(mills.map, marker);
                });

                //mills.map.fitBounds(bounds);
            });

            //mills.filterMarkersByLocation();
            if (mills.clusterer != null)
                mills.clusterer.clearMarkers();
            mills.clusterer = new MarkerClusterer(mills.map, mills.markers, {/*gridSize: 60, maxZoom: 8*/ });
            mills.millLoading = false;
            mills.millLoader.hide();
        }, "json");
    },


    'findMarkers': function () {
        var markers, max, title, shouldRegEx, re;

        mills.millLoader.show();

        markers = this.markers;
        max = (markers != null) ? markers.length : 0

        if (this.infowindow) { this.infowindow.close(); }

        title = this.query['SearchableText'];

        // Only search if there is something to search for
        if (title != null && title != '') { shouldRegEx = true; }
        else { shouldRegEx = false; }

        if (shouldRegEx) {
            re = new RegExp(title, 'i');
            this.currentMarkers = [];
            this.searchRegEx(re, markers, 0, max);
        }
        else {
            for (var i = 0; i < max; i++) {
                this.clusterer.addMarker(markers[i], false);
            }
            this.millLoader.hide();
        }
    },


    'searchRegEx': function (re, markers, counter, max) {

        if (counter >= max) {
            this.millLoader.hide();
            this.millType.bind('change', mills.millTypeChanged);
            return;
        }

        if (markers[counter].title != null && markers[counter].title != '' && markers[counter].title.match(re)) {
            this.clusterer.addMarker(markers[counter], false);
            this.currentMarkers.push(markers[counter]);
        }

        setTimeout(function () { mills.searchRegEx(re, markers, counter + 1, max); }, 1);
    },

    'emptyMillStates': function () {
        if (mills.millStateViewstate != null)
            mills.millStateViewstate = mills.millState.val();
        while (mills.millStateCount > 0) {
            mills.millStateHandler.remove(mills.millStateCount);
            mills.millStateCount--;
        }
    },

    'loadMillStates': function () {
        mills.emptyMillStates();

        //    jq.post("/getMillStates.json", { 'getSpecies': mills.millSpecies.val(), 'getMillType': mills.millType.val() }, function(output) {

        jq.post("/getMillStates.json", {}, function (output) {
            if (output.states.length == 0) {
                mills.millStateHandler.disabled(true);
                return;
            }

            var index = 0;
            mills.millStateHandler.disabled(false);
            jq.each(output.states, function (i, state) {
                mills.millStateHandler.add({ value: state['value'], text: state['text'] });
                mills.millStateCount++;
                if (state['value'] == mills.millStateViewstate)
                    index = i + 1;
            });

            mills.millStateHandler.set('selectedIndex', index);
        }, "json");
    },

    'emptyMillCounties': function () {
        if (mills.millCountyViewstate != null)
            mills.millCountyViewstate = mills.millCounty.val();
        while (mills.millCountyCount > 0) {
            mills.millCountyHandler.remove(mills.millCountyCount);
            mills.millCountyCount--;
        }
    },

    'loadMillCounties': function () {
        mills.emptyMillCounties();
        jq.post("/getMillCounties.json", { 'getState': mills.millState.val(), 'getSpecies': mills.millSpecies.val(), 'getMillType': mills.millType.val() }, function (output) {
            if (output.counties.length == 0) {
                mills.millCountyHandler.disabled(true);
                return;
            }

            var index = 0;
            mills.millCountyHandler.disabled(false);
            jq.each(output.counties, function (i, county) {
                mills.millCountyHandler.add({ value: county, text: county });
                mills.millCountyCount++;
                if (county == mills.millCountyViewstate)
                    index = i + 1;
            });

            mills.millCounty.msDropDown({ visibleRows: 10 });
            mills.millCountyHandler.set('selectedIndex', index);
        }, "json");
    },

    'emptyMillSpecies': function () {
        if (mills.millSpeciesViewstate != null)
            mills.millSpeciesViewstate = mills.millSpecies.val();
        while (mills.millSpeciesCount > 0) {
            mills.millSpeciesHandler.remove(mills.millSpeciesCount);
            mills.millSpeciesCount--;
        }
    },

    'loadMillSpecies': function () {
        mills.emptyMillSpecies();
        jq.post("/getMillSpecies.json", { 'getState': mills.millState.val(), 'getCounty': mills.millCounty.val(), 'getMillType': mills.millType.val() }, function (output) {
            if (output.species.length == 0) {
                mills.millSpeciesHandler.disabled(true);
                return;
            }

            var index = 0;
            mills.millSpeciesHandler.disabled(false);
            jq.each(output.species, function (i, specie) {
                mills.millSpeciesHandler.add({ value: specie, text: specie });
                mills.millSpeciesCount++;
                if (specie == mills.millSpeciesViewstate)
                    index = i + 1;
            });

            mills.millSpeciesHandler.set('selectedIndex', index);
        }, "json");
    },

    'emptyMillTypes': function () {
        if (mills.millTypeViewstate != null)
            mills.millTypeViewstate = mills.millType.val();
        while (mills.millTypeCount > 0) {
            mills.millTypeHandler.remove(mills.millTypeCount);
            mills.millTypeCount--;
        }
    },

    'loadMillTypes': function () {
        mills.emptyMillTypes();
        jq.post("/getMillTypes.json", { 'getState': mills.millState.val(), 'getCounty': mills.millCounty.val(), 'getMillSpecies': mills.millSpecies.val() }, function (output) {
            if (output.types.length == 0) {
                mills.millTypeHandler.disabled(true);
                return;
            }

            var index = 0;
            mills.millTypeHandler.disabled(false);
            jq.each(output.types, function (i, type) {
                mills.millTypeHandler.add({ value: type, text: type, title: mills.sprites[type] });
                mills.millTypeCount++;
                if (type == mills.millTypeViewstate)
                    index = i + 1;
            });

            mills.millTypeHandler.set('selectedIndex', index);
        }, "json");
    },

    'loadExportLink': function () {
        var exportItem = jq('#export-xls');
        var millState = mills.millState.val();
        if (millState == '') {
            exportItem.hide();
            exportItem.children('a').attr('href', '');
            exportItem.children('a').html('');
        }
        else {
            exportItem.show();
            exportItem.children('a').attr('href', 'export.xls?getState=' + millState);
            exportItem.children('a').html('Export ' + millState + ' Mills');
        }
    }
};

jq(document).ready(function () { mills.init(); } )